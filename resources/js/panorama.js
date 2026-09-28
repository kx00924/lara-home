/**
 * Minimal WebGL viewer for equirectangular (2:1) 360° panoramas.
 *
 * A full-screen quad is drawn and each pixel's view ray is mapped back to
 * longitude/latitude on the panorama, so there is no sphere mesh and no
 * third-party dependency. Supports drag (mouse, pen, touch), inertia, wheel
 * and pinch zoom, keyboard arrows, auto-rotate and live resize.
 *
 * Views are expressed in degrees: yaw (0 = centre of the image, positive =
 * turn right), pitch (positive = look up) and fov (vertical field of view).
 */

const VERT = `
attribute vec2 a_pos;
varying vec2 v_uv;
void main() { v_uv = a_pos; gl_Position = vec4(a_pos, 0.0, 1.0); }`;

const FRAG = `
precision highp float;
uniform sampler2D u_tex;
uniform float u_yaw;
uniform float u_pitch;
uniform float u_tanHalf;
uniform float u_aspect;
varying vec2 v_uv;
const float PI = 3.141592653589793;
void main() {
  vec3 d = normalize(vec3(v_uv.x * u_tanHalf * u_aspect, v_uv.y * u_tanHalf, -1.0));
  float cp = cos(u_pitch); float sp = sin(u_pitch);
  d = vec3(d.x, d.y * cp - d.z * sp, d.y * sp + d.z * cp);
  float cy = cos(u_yaw); float sy = sin(u_yaw);
  d = vec3(d.x * cy - d.z * sy, d.y, d.x * sy + d.z * cy);
  float lon = atan(d.x, -d.z);
  float lat = asin(clamp(d.y, -1.0, 1.0));
  gl_FragColor = texture2D(u_tex, vec2(lon / (2.0 * PI) + 0.5, 0.5 - lat / PI));
}`;

const DEG = Math.PI / 180;
const MIN_FOV = 30 * DEG;
const MAX_FOV = 100 * DEG;
const MAX_PITCH = 85 * DEG;
const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));

export function webglAvailable() {
    try {
        const c = document.createElement('canvas');
        return !!(c.getContext('webgl') || c.getContext('experimental-webgl'));
    } catch {
        return false;
    }
}

export class PanoramaViewer {
    constructor(canvas, { yaw = 0, pitch = 0, fov = 75, autoRotate = false, onChange = null, onInteract = null } = {}) {
        this.canvas = canvas;
        this.onChange = onChange;
        this.onInteract = onInteract;
        this.autoRotate = autoRotate;
        this.ready = false;
        this.dirty = true;
        this.dragging = false;
        this.pointers = new Map();
        this.velocity = { yaw: 0, pitch: 0 };
        this.setView({ yaw, pitch, fov }, false);

        const gl = canvas.getContext('webgl', { antialias: false, alpha: false }) || canvas.getContext('experimental-webgl');
        if (!gl) throw new Error('WebGL is not available in this browser.');
        this.gl = gl;
        this.maxTexture = gl.getParameter(gl.MAX_TEXTURE_SIZE);
        this.program = this.#program(VERT, FRAG);
        gl.useProgram(this.program);

        const buffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, -1, 1, 1, -1, 1, 1]), gl.STATIC_DRAW);
        const loc = gl.getAttribLocation(this.program, 'a_pos');
        gl.enableVertexAttribArray(loc);
        gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

        this.uniforms = Object.fromEntries(['u_tex', 'u_yaw', 'u_pitch', 'u_tanHalf', 'u_aspect'].map((n) => [n, gl.getUniformLocation(this.program, n)]));
        this.texture = gl.createTexture();

        this.#bindEvents();
        this.resizeObserver = new ResizeObserver(() => { this.dirty = true; });
        this.resizeObserver.observe(canvas);
        this.frame = requestAnimationFrame(this.#loop);
    }

    /** Load a panorama image; resolves when it is on screen. */
    load(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.decoding = 'async';
            img.onload = () => {
                let source = img;
                // Downscale anything larger than the GPU can hold (keeps the 2:1 ratio).
                if (img.naturalWidth > this.maxTexture || img.naturalHeight > this.maxTexture) {
                    const scale = Math.min(this.maxTexture / img.naturalWidth, this.maxTexture / img.naturalHeight);
                    const c = document.createElement('canvas');
                    c.width = Math.floor(img.naturalWidth * scale);
                    c.height = Math.floor(img.naturalHeight * scale);
                    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                    source = c;
                }
                const gl = this.gl;
                gl.bindTexture(gl.TEXTURE_2D, this.texture);
                gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, false);
                gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGB, gl.RGB, gl.UNSIGNED_BYTE, source);
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
                this.ready = true;
                this.dirty = true;
                resolve({ width: img.naturalWidth, height: img.naturalHeight });
            };
            img.onerror = () => reject(new Error('The panorama image could not be loaded.'));
            img.src = url;
        });
    }

    setView({ yaw = 0, pitch = 0, fov = 75 } = {}, notify = true) {
        this.yaw = Number(yaw) * DEG;
        this.pitch = clamp(Number(pitch) * DEG, -MAX_PITCH, MAX_PITCH);
        this.fov = clamp(Number(fov) * DEG, MIN_FOV, MAX_FOV);
        this.velocity = { yaw: 0, pitch: 0 };
        this.dirty = true;
        if (notify) this.#changed();
    }

    getView() {
        let yaw = (this.yaw / DEG) % 360;
        if (yaw > 180) yaw -= 360;
        if (yaw < -180) yaw += 360;
        return { yaw: Math.round(yaw * 10) / 10, pitch: Math.round((this.pitch / DEG) * 10) / 10, fov: Math.round(this.fov / DEG) };
    }

    zoom(factor) {
        this.fov = clamp(this.fov * factor, MIN_FOV, MAX_FOV);
        this.dirty = true;
        this.#changed();
    }

    destroy() {
        cancelAnimationFrame(this.frame);
        this.resizeObserver?.disconnect();
        this.abort?.abort();
        this.gl?.getExtension('WEBGL_lose_context')?.loseContext();
    }

    /* ------------------------------------------------------------------ */

    #program(vs, fs) {
        const gl = this.gl;
        const compile = (type, src) => {
            const s = gl.createShader(type);
            gl.shaderSource(s, src);
            gl.compileShader(s);
            if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(s));
            return s;
        };
        const p = gl.createProgram();
        gl.attachShader(p, compile(gl.VERTEX_SHADER, vs));
        gl.attachShader(p, compile(gl.FRAGMENT_SHADER, fs));
        gl.linkProgram(p);
        if (!gl.getProgramParameter(p, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(p));
        return p;
    }

    #loop = () => {
        const idle = !this.dragging && this.pointers.size === 0;
        if (idle && (Math.abs(this.velocity.yaw) > 1e-5 || Math.abs(this.velocity.pitch) > 1e-5)) {
            this.yaw += this.velocity.yaw;
            this.pitch = clamp(this.pitch + this.velocity.pitch, -MAX_PITCH, MAX_PITCH);
            this.velocity.yaw *= 0.92;
            this.velocity.pitch *= 0.92;
            this.dirty = true;
            this.#changed();
        } else if (idle && this.autoRotate) {
            this.yaw += 0.0012;
            this.dirty = true;
        }
        if (this.dirty && this.ready) this.#render();
        this.frame = requestAnimationFrame(this.#loop);
    };

    #render() {
        const gl = this.gl;
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const w = Math.max(1, Math.round(this.canvas.clientWidth * dpr));
        const h = Math.max(1, Math.round(this.canvas.clientHeight * dpr));
        if (this.canvas.width !== w || this.canvas.height !== h) {
            this.canvas.width = w;
            this.canvas.height = h;
        }
        gl.viewport(0, 0, w, h);
        gl.uniform1i(this.uniforms.u_tex, 0);
        gl.uniform1f(this.uniforms.u_yaw, this.yaw);
        gl.uniform1f(this.uniforms.u_pitch, this.pitch);
        gl.uniform1f(this.uniforms.u_tanHalf, Math.tan(this.fov / 2));
        gl.uniform1f(this.uniforms.u_aspect, w / h);
        gl.drawArrays(gl.TRIANGLES, 0, 6);
        this.dirty = false;
    }

    #changed() {
        this.onChange?.(this.getView());
    }

    #interacted() {
        if (this.autoRotate) {
            this.autoRotate = false;
            this.onInteract?.();
        }
    }

    #bindEvents() {
        this.abort = new AbortController();
        const opts = { signal: this.abort.signal };
        const c = this.canvas;
        let last = null;
        let pinch = null;
        let lastMove = 0;

        c.addEventListener('pointerdown', (e) => {
            this.#interacted();
            c.setPointerCapture(e.pointerId);
            this.pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            this.dragging = this.pointers.size === 1;
            this.velocity = { yaw: 0, pitch: 0 };
            last = { x: e.clientX, y: e.clientY };
            pinch = null;
            c.style.cursor = 'grabbing';
        }, opts);

        c.addEventListener('pointermove', (e) => {
            if (!this.pointers.has(e.pointerId)) return;
            this.pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (this.pointers.size === 2) {
                const [a, b] = [...this.pointers.values()];
                const dist = Math.hypot(a.x - b.x, a.y - b.y);
                if (pinch) this.zoom(pinch / dist);
                pinch = dist;
                return;
            }
            if (!this.dragging || !last) return;
            const vFov = this.fov;
            const hFov = 2 * Math.atan(Math.tan(vFov / 2) * (c.clientWidth / Math.max(1, c.clientHeight)));
            const dYaw = -((e.clientX - last.x) / Math.max(1, c.clientWidth)) * hFov;
            const dPitch = ((e.clientY - last.y) / Math.max(1, c.clientHeight)) * vFov;
            this.yaw += dYaw;
            this.pitch = clamp(this.pitch + dPitch, -MAX_PITCH, MAX_PITCH);
            // Inertia comes from the latest movement, capped so one jumpy event cannot spin the view.
            const cap = 1.5 * DEG;
            this.velocity = { yaw: clamp(dYaw, -cap, cap), pitch: clamp(dPitch, -cap, cap) };
            lastMove = performance.now();
            last = { x: e.clientX, y: e.clientY };
            this.dirty = true;
            this.#changed();
        }, opts);

        const release = (e) => {
            this.pointers.delete(e.pointerId);
            if (this.pointers.size < 2) pinch = null;
            if (this.pointers.size === 0) {
                // Only glide if the pointer was still moving when it was released.
                if (performance.now() - lastMove > 60) this.velocity = { yaw: 0, pitch: 0 };
                this.dragging = false;
                last = null;
                c.style.cursor = '';
            }
        };
        c.addEventListener('pointerup', release, opts);
        c.addEventListener('pointercancel', release, opts);

        c.addEventListener('wheel', (e) => {
            e.preventDefault();
            this.#interacted();
            this.zoom(1 + clamp(e.deltaY, -100, 100) * 0.0015);
        }, { ...opts, passive: false });

        c.addEventListener('keydown', (e) => {
            const step = 5 * DEG;
            const map = {
                ArrowLeft: () => { this.yaw -= step; },
                ArrowRight: () => { this.yaw += step; },
                ArrowUp: () => { this.pitch = clamp(this.pitch + step, -MAX_PITCH, MAX_PITCH); },
                ArrowDown: () => { this.pitch = clamp(this.pitch - step, -MAX_PITCH, MAX_PITCH); },
                '+': () => this.zoom(0.9),
                '=': () => this.zoom(0.9),
                '-': () => this.zoom(1.1),
            };
            if (!map[e.key]) return;
            e.preventDefault();
            this.#interacted();
            map[e.key]();
            this.dirty = true;
            this.#changed();
        }, opts);
    }
}

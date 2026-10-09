import Alpine from 'alpinejs';
import { PanoramaViewer, webglAvailable } from './panorama';

/* ---------------- Theme (site defaults + visitor overrides) ---------------- */
const STORAGE_KEY = 'home.theme';
const ACCENT_PRESETS = [
    { name: 'White', value: '#ffffff' }, { name: 'Terracotta', value: '#b45f3c' }, { name: 'Moss', value: '#5f7a5b' }, { name: 'Indigo', value: '#3f4f8a' },
    { name: 'Ink', value: '#2b2b2b' }, { name: 'Plum', value: '#7a4a6b' }, { name: 'Ochre', value: '#b8862b' },
    { name: 'Teal', value: '#2f7a7a' }, { name: 'Vermilion', value: '#c9402f' },
];
const FONT_OPTIONS = ['Noto Serif', 'Noto Serif KR', 'Noto Serif JP', 'Georgia', 'Noto Sans', 'Noto Sans KR', 'Noto Sans JP', 'system-ui'];

const hexToRgb = (hex) => {
    const h = (hex || '').replace('#', '');
    if (![3, 6].includes(h.length)) return null;
    const full = h.length === 3 ? h.split('').map((c) => c + c).join('') : h;
    const n = parseInt(full, 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
};
const luminance = ([r, g, b]) => (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
const mix = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));
const readStored = () => { try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch { return {}; } };

Alpine.store('theme', {
    defaults: window.SITE_THEME || {},
    override: readStored(),
    systemDark: window.matchMedia('(prefers-color-scheme: dark)').matches,
    presets: ACCENT_PRESETS,
    fonts: FONT_OPTIONS,
    get allowOverride() { return this.defaults.allowUserThemeOverride !== false; },
    get hasOverride() { return Object.keys(this.override).length > 0; },
    get theme() {
        const d = this.defaults;
        const base = {
            mode: d.defaultTheme || 'dark', accent: d.accentColor, textLight: d.textColorLight, textDark: d.textColorDark,
            bgLight: d.backgroundLight, bgDark: d.backgroundDark, headingFont: d.headingFont, bodyFont: d.bodyFont,
            radius: d.borderRadius ?? 16, fontScale: 1,
        };
        const o = this.allowOverride ? this.override : {};
        for (const k of Object.keys(o)) if (o[k] !== '' && o[k] != null) base[k] = o[k];
        return base;
    },
    get isDark() { return this.theme.mode === 'dark' || (this.theme.mode === 'system' && this.systemDark); },
    set(patch) {
        this.override = { ...this.override, ...patch };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.override));
        this.apply();
    },
    reset() { this.override = {}; localStorage.removeItem(STORAGE_KEY); this.apply(); },
    toggle() { this.set({ mode: this.isDark ? 'light' : 'dark' }); },
    apply() {
        const t = this.theme, dark = this.isDark, root = document.documentElement;
        root.classList.toggle('dark', dark);
        const text = hexToRgb(dark ? t.textDark : t.textLight) || (dark ? [236, 235, 232] : [27, 26, 25]);
        let accent = hexToRgb(t.accent) || [255, 255, 255];
        // A near-white accent on a light page (or near-black on a dark one) would
        // disappear, so monochrome accents follow the text colour in that mode.
        if ((!dark && luminance(accent) > 0.85) || (dark && luminance(accent) < 0.15)) accent = text;
        const bg = hexToRgb(dark ? t.bgDark : t.bgLight) || (dark ? [17, 17, 19] : [248, 247, 245]);
        const surface = dark ? mix(bg, [255, 255, 255], 0.05) : mix(bg, [255, 255, 255], 0.9);
        const surface2 = dark ? mix(bg, [255, 255, 255], 0.1) : mix(bg, text, 0.045);
        root.style.setProperty('--c-accent', accent.join(' '));
        root.style.setProperty('--c-accent-fg', luminance(accent) > 0.6 ? '20 18 16' : '255 255 255');
        root.style.setProperty('--c-text', text.join(' '));
        root.style.setProperty('--c-bg', bg.join(' '));
        root.style.setProperty('--c-surface', surface.join(' '));
        root.style.setProperty('--c-surface-2', surface2.join(' '));
        root.style.setProperty('--radius', `${t.radius}px`);
        root.style.setProperty('--font-heading', `'${t.headingFont}'`);
        root.style.setProperty('--font-body', `'${t.bodyFont}'`);
        root.style.fontSize = `${Math.round(t.fontScale * 100)}%`;
    },
    init() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => { this.systemDark = e.matches; this.apply(); });
        this.apply();
    },
});

/* ---------------- Search box with suggestions ---------------- */
Alpine.data('searchBox', (suggestUrl, listUrl) => ({
    q: '', items: [], open: false, active: -1, timer: null,
    onInput() {
        clearTimeout(this.timer);
        if (!this.q.trim()) { this.items = []; this.open = false; return; }
        this.timer = setTimeout(async () => {
            try {
                const r = await fetch(`${suggestUrl}?q=${encodeURIComponent(this.q)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }); // AJAX header keeps this out of Laravel's "previous URL"
                this.items = await r.json();
                this.open = this.items.length > 0;
                this.active = -1;
            } catch { this.items = []; }
        }, 180);
    },
    go(item) {
        window.location.href = item ? item.url : `${listUrl}?q=${encodeURIComponent(this.q.trim())}`;
    },
    onKey(e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); this.active = Math.min(this.active + 1, this.items.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.active = Math.max(this.active - 1, -1); }
        else if (e.key === 'Enter') { e.preventDefault(); this.go(this.active >= 0 ? this.items[this.active] : null); }
        else if (e.key === 'Escape') { this.open = false; }
    },
}));

/* ---------------- Design page: like + lightbox ---------------- */
const LIKES_KEY = 'home.likes';
const readLikes = () => { try { return JSON.parse(localStorage.getItem(LIKES_KEY) || '[]'); } catch { return []; } };

Alpine.data('designPage', (likeUrl, initialLikes, images, designSlug) => ({
    likes: initialLikes, liked: readLikes().includes(designSlug), lightbox: -1, images,
    async like() {
        if (this.liked) return;
        this.liked = true;
        try {
            const r = await fetch(likeUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
            const j = await r.json();
            this.likes = j.likes;
            localStorage.setItem(LIKES_KEY, JSON.stringify([...new Set([...readLikes(), designSlug])]));
        } catch { this.liked = false; }
    },
    async share() {
        try { await navigator.clipboard.writeText(window.location.href); window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Link copied.', type: 'success' } })); }
        catch { window.prompt('Copy this link', window.location.href); }
    },
    // Panoramas open in the 360° tour instead of the flat lightbox.
    openLightbox(i) {
        const img = this.images[i];
        if (!img?.url) return;
        if (img.kind === 'panorama') { window.dispatchEvent(new CustomEvent('open-panorama', { detail: { id: img.id } })); return; }
        this.lightbox = i;
        document.body.style.overflow = 'hidden';
    },
    closeLightbox() { this.lightbox = -1; document.body.style.overflow = ''; },
    isPhoto(j) { return !!this.images[j]?.url && this.images[j].kind !== 'panorama'; },
    prev() { for (let j = this.lightbox - 1; j >= 0; j--) if (this.isPhoto(j)) { this.lightbox = j; return; } },
    next() { for (let j = this.lightbox + 1; j < this.images.length; j++) if (this.isPhoto(j)) { this.lightbox = j; return; } },
}));

/* ---------------- 360° tour: one viewer per floor ---------------- */
// The viewer lives in a closure, not in Alpine's reactive state: WebGL objects break inside proxies.
Alpine.data('panoTour', (scenes) => {
    let viewer = null;
    return {
        scenes, active: 0, started: false, loading: false, failed: false, auto: true,
        init() {
            const io = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && !this.started) { this.start(0); io.disconnect(); }
            }, { rootMargin: '200px' });
            io.observe(this.$el);
            window.addEventListener('open-panorama', (e) => {
                const i = this.scenes.findIndex((s) => s.id === e.detail.id);
                if (i < 0) return;
                this.$el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                this.started ? this.select(i) : this.start(i);
            });
        },
        start(i) {
            this.started = true;
            if (!webglAvailable()) { this.failed = true; return; }
            try {
                viewer = new PanoramaViewer(this.$refs.canvas, { autoRotate: this.auto, onInteract: () => { this.auto = false; } });
            } catch { this.failed = true; return; }
            this.select(i);
        },
        async select(i) {
            this.active = i;
            const scene = this.scenes[i];
            if (!viewer || !scene?.url) return;
            this.loading = true;
            try {
                await viewer.load(scene.url);
                viewer.setView(scene, false);
            } catch { this.failed = true; } finally { this.loading = false; }
        },
        toggleAuto() { this.auto = !this.auto; if (viewer) viewer.autoRotate = this.auto; },
        reset() { viewer?.setView(this.scenes[this.active], false); },
        fullscreen() {
            if (document.fullscreenElement) document.exitFullscreen();
            else this.$refs.stage.requestFullscreen?.();
        },
        destroy() { viewer?.destroy(); viewer = null; },
    };
});

/* ---------------- Admin: gallery manager ---------------- */
const looksLikePanorama = (w, h) => h > 0 && w >= 2000 && Math.abs(w / h - 2) < 0.08;

/**
 * Crops an image to a pixel box at full resolution and returns it as a Blob:
 * PNG for PNG sources (keeps transparency), otherwise JPEG. Rejects when the
 * canvas may not read the image (cross-origin without CORS).
 */
function cropToBlob(url, { x, y, width, height }) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(img, x, y, width, height, 0, 0, width, height);
                const type = /\.png(\?|$)/i.test(url) ? 'image/png' : 'image/jpeg';
                canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('Crop failed'))), type, 0.92);
            } catch (e) { reject(e); }
        };
        img.onerror = () => reject(new Error('Image could not be loaded'));
        img.src = url;
    });
}

Alpine.data('galleryManager', (initial, angles, uploadUrl, floors = ['Ground floor'], cropUrl = '') => {
    let previewViewer = null;
    let drag = null; // active crop drag, kept out of reactive state
    const clamp01 = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
    const toast = (message, type = 'info') => window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
    const blank = (extra) => ({ id: '', title: '', description: '', kind: 'photo', floor: 1, pano_yaw: 0, pano_pitch: 0, pano_fov: 75, w: 0, h: 0, fresh: true, ...extra });
    return {
    images: initial.map((i) => ({
        id: i.id || '', url: i.url, title: i.title || '', description: i.description || '', angle: i.angle || 'Overview',
        kind: i.kind || 'photo', floor: i.floor || 1, pano_yaw: i.pano_yaw ?? 0, pano_pitch: i.pano_pitch ?? 0, pano_fov: i.pano_fov ?? 75, w: 0, h: 0, fresh: false,
    })),
    floors: floors.length ? [...floors] : ['Ground floor'],
    angles, urlInput: '', uploading: false, cover: '',
    preview: null, previewLoading: false, previewError: '', previewView: { yaw: 0, pitch: 0, fov: 75 },
    // Crop box as fractions (0..1) of the displayed image, so it survives resizing.
    cropAfterUpload: true, cropQueue: [], cropBusy: false,
    crop: { index: null, x: 0, y: 0, w: 1, h: 1, aspect: null, natW: 0, natH: 0, ready: false },
    cropPresets: [['Free', null], ['1:1', 1], ['4:3', 4 / 3], ['3:2', 3 / 2], ['16:9', 16 / 9], ['2:1 · 360°', 2]],
    init() { this.cover = this.$root.dataset.cover || ''; },

    /* ---------- crop ---------- */
    openCrop(i) {
        const im = this.images[i];
        if (!im) return;
        this.crop = { index: i, x: 0, y: 0, w: 1, h: 1, aspect: im.kind === 'panorama' ? 2 : null, natW: 0, natH: 0, ready: false };
    },
    cropImageLoaded(el) {
        this.crop.natW = el.naturalWidth;
        this.crop.natH = el.naturalHeight;
        this.crop.ready = true;
        this.setCropAspect(this.crop.aspect);
    },
    /** 360° panoramas must stay 2:1 to work in the viewer, so they only get that preset. */
    cropPresetOptions() {
        return this.images[this.crop.index]?.kind === 'panorama' ? this.cropPresets.filter(([, a]) => a === 2) : this.cropPresets;
    },
    /** Pixel aspect ratio converted to the fraction space of this image. */
    fracAspect(a) { return a * (this.crop.natH / this.crop.natW); },
    setCropAspect(a) {
        this.crop.aspect = a;
        if (!a) { Object.assign(this.crop, { x: 0, y: 0, w: 1, h: 1 }); return; }
        const af = this.fracAspect(a);
        let w = 1; let h = w / af;
        if (h > 1) { h = 1; w = h * af; }
        Object.assign(this.crop, { w, h, x: (1 - w) / 2, y: (1 - h) / 2 });
    },
    cropPixels() {
        const c = this.crop;
        return { x: Math.round(c.x * c.natW), y: Math.round(c.y * c.natH), width: Math.max(1, Math.round(c.w * c.natW)), height: Math.max(1, Math.round(c.h * c.natH)) };
    },
    startCropDrag(e, mode) {
        e.preventDefault();
        const box = this.$refs.cropStage.getBoundingClientRect();
        drag = { mode, sx: e.clientX, sy: e.clientY, bw: box.width, bh: box.height, r: { x: this.crop.x, y: this.crop.y, w: this.crop.w, h: this.crop.h } };
        const move = (ev) => this.cropDrag(ev);
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', () => { window.removeEventListener('pointermove', move); drag = null; }, { once: true });
    },
    cropDrag(e) {
        if (!drag) return;
        const { mode, r } = drag;
        const dx = (e.clientX - drag.sx) / drag.bw;
        const dy = (e.clientY - drag.sy) / drag.bh;
        const minW = 24 / drag.bw; const minH = 24 / drag.bh;
        let { x, y, w, h } = r;
        if (mode === 'move') {
            x = clamp01(r.x + dx, 0, 1 - r.w);
            y = clamp01(r.y + dy, 0, 1 - r.h);
        } else if (this.crop.aspect) {
            // Aspect-locked: resize from the dragged corner, keeping the opposite corner fixed.
            const af = this.fracAspect(this.crop.aspect);
            const west = mode.includes('w'); const north = mode.includes('n');
            const ax = west ? r.x + r.w : r.x; const ay = north ? r.y + r.h : r.y;
            const maxW = Math.min(west ? ax : 1 - ax, (north ? ay : 1 - ay) * af);
            w = clamp01(r.w + (west ? -dx : dx), Math.max(minW, minH * af), maxW);
            h = w / af;
            x = west ? ax - w : ax;
            y = north ? ay - h : ay;
        } else {
            if (mode.includes('e')) w = clamp01(r.w + dx, minW, 1 - r.x);
            if (mode.includes('s')) h = clamp01(r.h + dy, minH, 1 - r.y);
            if (mode.includes('w')) { x = clamp01(r.x + dx, 0, r.x + r.w - minW); w = r.w + (r.x - x); }
            if (mode.includes('n')) { y = clamp01(r.y + dy, 0, r.y + r.h - minH); h = r.h + (r.y - y); }
        }
        Object.assign(this.crop, { x, y, w, h });
    },
    async applyCrop() {
        const im = this.images[this.crop.index];
        if (!im || !this.crop.ready) return;
        const px = this.cropPixels();
        if (px.x === 0 && px.y === 0 && px.width === this.crop.natW && px.height === this.crop.natH) { this.closeCrop(); return; }
        this.cropBusy = true;
        const headers = { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' };
        try {
            // Crop in the browser and upload the result, so the server needs no image extension.
            // Images the canvas may not read (external, no CORS) are cropped on the server instead.
            const blob = await cropToBlob(im.url, px).catch(() => null);
            let body;
            if (blob) {
                body = new FormData();
                body.append('image', blob, blob.type === 'image/png' ? 'crop.png' : 'crop.jpg');
            } else {
                headers['Content-Type'] = 'application/json';
                body = JSON.stringify({ url: im.url, ...px });
            }
            const r = await fetch(cropUrl, { method: 'POST', headers, body });
            const j = await r.json();
            if (!r.ok) throw new Error(j.message || 'Crop failed');
            if (this.cover === im.url) this.cover = j.url;
            Object.assign(im, { url: j.url, w: j.width, h: j.height });
            toast(`Cropped to ${j.width}×${j.height}. Save the design to keep it.`, 'success');
            this.closeCrop();
        } catch (e) { toast(e.message, 'error'); } finally { this.cropBusy = false; }
    },
    closeCrop() {
        this.crop.index = null;
        const next = this.cropQueue.shift();
        if (next !== undefined) this.$nextTick(() => this.openCrop(next));
    },
    skipAllCrops() { this.cropQueue = []; this.crop.index = null; },
    addUrl() {
        const url = this.urlInput.trim();
        if (!url) return;
        this.images.push(blank({ url, angle: this.angles[this.images.length % this.angles.length] }));
        this.urlInput = '';
    },
    /** Called when a thumbnail loads: remember its size and auto-flag fresh 2:1 images as panoramas. */
    measure(im, el) {
        im.w = el.naturalWidth;
        im.h = el.naturalHeight;
        if (im.fresh && looksLikePanorama(im.w, im.h)) { im.kind = 'panorama'; im.fresh = false; }
    },
    isTwoToOne(im) { return im.w > 0 && Math.abs(im.w / im.h - 2) < 0.08; },
    setKind(im, kind) { im.kind = kind; im.fresh = false; if (kind === 'panorama' && (!im.floor || im.floor > this.floors.length)) im.floor = 1; },
    addFloor() { this.floors.push(this.floors.length === 1 ? 'Upper floor' : `Floor ${this.floors.length + 1}`); },
    removeFloor(n) {
        if (this.floors.length <= 1) return;
        this.images.forEach((im) => {
            if (im.kind !== 'panorama') return;
            if (im.floor === n) im.floor = 1; else if (im.floor > n) im.floor -= 1;
        });
        this.floors.splice(n - 1, 1);
    },
    panoramaCount(n) { return this.images.filter((im) => im.kind === 'panorama' && Number(im.floor) === n).length; },
    async openPreview(i) {
        this.preview = i;
        const im = this.images[i];
        this.previewView = { yaw: im.pano_yaw, pitch: im.pano_pitch, fov: im.pano_fov };
        await this.$nextTick();
        previewViewer?.destroy();
        // Each viewer gets its own canvas; destroy() releases the WebGL context, which a canvas cannot get back.
        const canvas = document.createElement('canvas');
        canvas.tabIndex = 0;
        canvas.className = 'block h-full w-full cursor-grab touch-none outline-none';
        this.$refs.previewStage.replaceChildren(canvas);
        this.previewError = '';
        this.previewLoading = true;
        try {
            previewViewer = new PanoramaViewer(canvas, { ...this.previewView, onChange: (v) => { this.previewView = v; } });
            await previewViewer.load(im.url);
            previewViewer.setView(this.previewView);
        } catch (e) {
            this.previewError = `${e.message} The file may have been moved or deleted: ${im.url}`;
        } finally { this.previewLoading = false; }
    },
    useView() {
        const im = this.images[this.preview];
        if (im) Object.assign(im, { pano_yaw: this.previewView.yaw, pano_pitch: this.previewView.pitch, pano_fov: this.previewView.fov });
        this.closePreview();
    },
    closePreview() { previewViewer?.destroy(); previewViewer = null; this.$refs.previewStage?.replaceChildren(); this.preview = null; },
    async upload(files) {
        if (!files?.length) return;
        this.uploading = true;
        const fd = new FormData();
        [...files].forEach((f) => fd.append('images[]', f));
        try {
            const r = await fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
            if (!r.ok) throw new Error((await r.json()).message || 'Upload failed');
            const j = await r.json();
            const firstNew = this.images.length;
            j.files.forEach((f, i) => this.images.push(blank({
                url: f.url, title: f.name.replace(/\.[a-z0-9]+$/i, ''), angle: this.angles[(this.images.length + i) % this.angles.length],
                kind: f.is_panorama ? 'panorama' : 'photo', w: f.width, h: f.height, fresh: false,
            })));
            const panoramas = j.files.filter((f) => f.is_panorama).length;
            if (panoramas) window.dispatchEvent(new CustomEvent('toast', { detail: { message: `${panoramas} image(s) look like 360° panoramas and were added to the tour.`, type: 'info' } }));
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: `${j.files.length} image(s) uploaded.`, type: 'success' } }));
            // Offer a crop step for each uploaded photo, one after another.
            if (this.cropAfterUpload) {
                const photos = j.files.map((f, i) => (f.is_panorama ? null : firstNew + i)).filter((i) => i !== null);
                if (photos.length) { this.cropQueue = photos.slice(1); this.openCrop(photos[0]); }
            }
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
        } finally { this.uploading = false; this.$refs.file.value = ''; }
    },
    move(i, dir) { const j = i + dir; if (j < 0 || j >= this.images.length) return; [this.images[i], this.images[j]] = [this.images[j], this.images[i]]; },
    remove(i) {
        // Removing the cover image lets the cover fall back to the first photo.
        if (this.images[i]?.url === this.cover) this.cover = '';
        this.images.splice(i, 1);
    },
    effectiveCover() { return this.cover || this.images.find((im) => im.kind !== 'panorama')?.url || this.images[0]?.url || ''; },
    };
});

/* ---------------- Carousel (scroll-snap track with arrows, dots, autoplay) ---------------- */
Alpine.data('carousel', (autoplayMs = 5000) => ({
    index: 0, pages: 1, atStart: true, atEnd: false, timer: null, paused: false,
    init() {
        this.measure();
        this.$refs.track.addEventListener('scroll', () => this.measure(), { passive: true });
        window.addEventListener('resize', () => this.measure());
        if (autoplayMs > 0) this.timer = setInterval(() => { if (!this.paused && !document.hidden) this.next(true); }, autoplayMs);
    },
    stepWidth() {
        const t = this.$refs.track, first = t.firstElementChild;
        if (!first) return t.clientWidth;
        const gap = parseFloat(getComputedStyle(t).columnGap || getComputedStyle(t).gap) || 0;
        return first.getBoundingClientRect().width + gap;
    },
    measure() {
        const t = this.$refs.track, step = this.stepWidth();
        const perView = Math.max(1, Math.round(t.clientWidth / step));
        const total = t.children.length;
        this.pages = Math.max(1, total - perView + 1);
        this.index = Math.min(this.pages - 1, Math.round(t.scrollLeft / step));
        this.atStart = t.scrollLeft <= 2;
        this.atEnd = t.scrollLeft + t.clientWidth >= t.scrollWidth - 2;
    },
    goTo(i, loop = false) {
        const t = this.$refs.track;
        if (loop && i >= this.pages) i = 0;
        i = Math.max(0, Math.min(this.pages - 1, i));
        t.scrollTo({ left: i * this.stepWidth(), behavior: 'smooth' });
    },
    next(loop = false) { this.goTo(this.index + 1, loop); },
    prev() { this.goTo(this.index - 1); },
}));

/* ---------------- Toasts ---------------- */
/* ---------------- Toasts: success / error / warning / info, colours from Site settings ---------------- */
const TOAST_FALLBACK = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
Alpine.data('toasts', () => ({
    items: [],
    push(message, type = 'info') {
        if (!(type in TOAST_FALLBACK)) type = 'info';
        const id = Math.random().toString(36).slice(2);
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), type === 'error' || type === 'warning' ? 6500 : 4200);
    },
    remove(id) { this.items = this.items.filter((t) => t.id !== id); },
    color(type) { return (window.SITE_THEME?.toastColors || {})[type] || TOAST_FALLBACK[type]; },
}));

/* ---------------- Confirm dialog: window.confirmDialog({...}) resolves true/false ---------------- */
Alpine.store('confirm', {
    open: false, title: '', message: '', okLabel: 'Confirm', danger: false, resolver: null,
    ask({ title = 'Are you sure?', message = '', okLabel = 'Confirm', danger = false } = {}) {
        this.resolver?.(false);
        Object.assign(this, { open: true, title, message, okLabel, danger });
        return new Promise((resolve) => { this.resolver = resolve; });
    },
    close(result) { this.open = false; const r = this.resolver; this.resolver = null; r?.(result); },
});
window.confirmDialog = (opts) => Alpine.store('confirm').ask(opts);

// Status toggles: revert the switch, ask, then submit the row's form on yes.
window.confirmToggle = async (input, label = 'this item') => {
    const next = input.checked;
    input.checked = !next;
    const ok = await window.confirmDialog({
        title: next ? `Activate ${label}?` : `Deactivate ${label}?`,
        message: next ? 'It becomes visible and usable on the site straight away.' : 'It is hidden from the site until you activate it again.',
        okLabel: next ? 'Activate' : 'Deactivate', danger: !next,
    });
    if (!ok) return;
    input.checked = next;
    input.form.requestSubmit();
};
// Status / role selects: remember the previous value on focus, confirm the change, revert on cancel.
window.confirmSelect = async (select, label = 'this item') => {
    const prev = select.dataset.prev ?? '';
    const chosen = select.options[select.selectedIndex]?.textContent.trim() || select.value;
    const ok = await window.confirmDialog({ title: `Change ${label}?`, message: `${label.charAt(0).toUpperCase() + label.slice(1)} will be set to “${chosen}”.`, okLabel: 'Change' });
    if (!ok) { select.value = prev; return; }
    select.form.submit();
};

/* ---------------- Page loader: shown while the next page loads ---------------- */
Alpine.store('loader', {
    visible: false, timer: null,
    show() { clearTimeout(this.timer); this.timer = setTimeout(() => { this.visible = true; }, 150); },
    hide() { clearTimeout(this.timer); this.visible = false; },
});
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if ((a.target && a.target !== '_self') || a.hasAttribute('download') || a.dataset.noLoader !== undefined) return;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
    Alpine.store('loader').show();
});
document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.dataset.noLoader !== undefined || (form.target && form.target !== '_self')) return;
    // Let Alpine's own @submit.prevent handlers run first.
    setTimeout(() => { if (!e.defaultPrevented) Alpine.store('loader').show(); }, 0);
});
window.addEventListener('pageshow', () => Alpine.store('loader').hide());
window.addEventListener('pagehide', () => Alpine.store('loader').hide());

/* ---------------- Bulk selection for admin tables ---------------- */
Alpine.data('bulkTable', () => ({
    selected: [],
    get ids() { return [...this.$root.querySelectorAll('input[data-row-id]')].map((el) => el.dataset.rowId); },
    get allSelected() { return this.ids.length > 0 && this.ids.every((id) => this.selected.includes(id)); },
    toggleAll(on) { this.selected = on ? this.ids : []; },
    clear() { this.selected = []; },
    async run(form) {
        if (!this.selected.length) return;
        const select = form.querySelector('[name=action]');
        const action = select.value;
        const label = select.options[select.selectedIndex]?.textContent.trim() || action;
        const ok = await window.confirmDialog({
            title: `${label}: ${this.selected.length} selected`,
            message: action === 'delete' ? 'Deleted items cannot be recovered.' : 'This applies to every selected row.',
            okLabel: label, danger: action === 'delete',
        });
        if (!ok) return;
        Alpine.store('loader').show();
        form.submit();
    },
}));

/* ---------------- Single-image upload field (admin forms) ---------------- */
Alpine.data('imageUpload', (uploadUrl) => ({
    busy: false,
    async send(files) {
        if (!files?.length) return null;
        this.busy = true;
        const fd = new FormData();
        fd.append('images[]', files[0]);
        try {
            const r = await fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
            const j = await r.json();
            if (!r.ok) throw new Error(j.message || 'Upload failed');
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Image uploaded.', type: 'success' } }));
            return j.files[0].url;
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
            return null;
        } finally { this.busy = false; }
    },
}));

window.Alpine = Alpine;
Alpine.start();

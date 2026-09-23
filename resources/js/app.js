import Alpine from 'alpinejs';

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
                const r = await fetch(`${suggestUrl}?q=${encodeURIComponent(this.q)}`, { headers: { Accept: 'application/json' } });
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
    openLightbox(i) { if (this.images[i]?.url) { this.lightbox = i; document.body.style.overflow = 'hidden'; } },
    closeLightbox() { this.lightbox = -1; document.body.style.overflow = ''; },
    prev() { this.lightbox = Math.max(0, this.lightbox - 1); },
    next() { this.lightbox = Math.min(this.images.length - 1, this.lightbox + 1); },
}));

/* ---------------- Admin: gallery manager ---------------- */
Alpine.data('galleryManager', (initial, angles, uploadUrl) => ({
    images: initial.map((i) => ({ id: i.id || '', url: i.url, title: i.title || '', description: i.description || '', angle: i.angle || 'Overview' })),
    angles, urlInput: '', uploading: false, cover: '',
    init() { this.cover = this.$root.dataset.cover || ''; },
    addUrl() {
        const url = this.urlInput.trim();
        if (!url) return;
        this.images.push({ id: '', url, title: '', description: '', angle: this.angles[this.images.length % this.angles.length] });
        this.urlInput = '';
    },
    async upload(files) {
        if (!files?.length) return;
        this.uploading = true;
        const fd = new FormData();
        [...files].forEach((f) => fd.append('images[]', f));
        try {
            const r = await fetch(uploadUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
            if (!r.ok) throw new Error((await r.json()).message || 'Upload failed');
            const j = await r.json();
            j.files.forEach((f, i) => this.images.push({ id: '', url: f.url, title: f.name.replace(/\.[a-z0-9]+$/i, ''), description: '', angle: this.angles[(this.images.length + i) % this.angles.length] }));
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: `${j.files.length} image(s) uploaded.`, type: 'success' } }));
        } catch (e) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: e.message, type: 'error' } }));
        } finally { this.uploading = false; this.$refs.file.value = ''; }
    },
    move(i, dir) { const j = i + dir; if (j < 0 || j >= this.images.length) return; [this.images[i], this.images[j]] = [this.images[j], this.images[i]]; },
    remove(i) { this.images.splice(i, 1); },
    effectiveCover() { return this.cover || this.images[0]?.url || ''; },
}));

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
Alpine.data('toasts', () => ({
    items: [],
    push(message, type = 'info') {
        const id = Math.random().toString(36).slice(2);
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), 4200);
    },
    remove(id) { this.items = this.items.filter((t) => t.id !== id); },
}));

window.Alpine = Alpine;
Alpine.start();

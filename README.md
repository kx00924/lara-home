# Home Studio

Stack: Laravel 12 · PHP 8.2+ · Blade · Tailwind CSS 4 · Alpine.js · Vite 6 · SQLite (or MySQL).

## Requirements

- **PHP 8.2 or newer** (composer is pinned to the 8.2 platform so production hosting on 8.2 works).
- **Node 20.14 or newer** (`.nvmrc` says `20.14.0`; `npm install` refuses older versions).
- Composer 2.

### PHP extensions

Enable these in `php.ini` (on Windows, uncomment the `extension=` line; on shared hosting,
tick them under *Select PHP version → Extensions*). Most are on by default.

| Extension | Needed for | |
|-----------|------------|---|
| `openssl`, `mbstring`, `tokenizer`, `ctype`, `filter`, `hash`, `session`, `json`, `pcre` | Laravel itself (encryption, sessions, strings) | required |
| `fileinfo` | Image uploads: file type detection and the `image` validation rule | required |
| `pdo_sqlite` **or** `pdo_mysql` | Database (SQLite by default, MySQL optional) | required |
| `zip` | *Download all images* (zip of a design's photos and panoramas) | required |
| `curl` | Stripe payments and fetching external image URLs | required |
| `dom`, `xml`, `libxml` | HTML/CSS inlining for mail, and the test suite | required |
| `gd` | `php artisan images:localize` thumbnails, server-side crop of external images, and the image fakes in the tests | recommended |
| `xmlwriter`, `phar` | PHPUnit / dev tools only | dev only |

Cropping itself does **not** need `gd`: the admin's browser crops the image at full
resolution and uploads the result. Without `gd` only images hosted on other websites
can't be cropped (upload them to the site first).

The minimal `php.ini` lines (Windows / Laragon naming):

```ini
extension=curl
extension=fileinfo
extension=gd
extension=mbstring
extension=openssl
extension=pdo_sqlite   ; or extension=pdo_mysql
extension=zip
```

Recommended limits, because 360° panoramas are large (uploads accept files up to 50 MB):

```ini
upload_max_filesize = 50M
post_max_size = 100M
memory_limit = 256M
max_execution_time = 120
```

Check what the current PHP has, and what the locked packages demand:

```bash
php -m
composer check-platform-reqs --no-dev
```

## Quick start

```bash
composer install
npm install
cp .env.example .env        # already done in this checkout
php artisan key:generate    # only on a fresh clone
php artisan migrate:fresh --seed
php artisan storage:link
npm run build               # or `npm run dev` for hot reload
php artisan serve           # http://localhost:8000
```

| Account  | Email               | Password  |
|----------|---------------------|-----------|
| Admin    | admin@home.studio  | Admin123! |
| Customer | demo@example.com    | Demo123!  |

### Database

`.env` uses SQLite (`database/database.sqlite`) so nothing needs installing. For MySQL set
`DB_CONNECTION=mysql` plus the usual `DB_*` values (Laragon's MySQL works out of the box)
and run the migrate command again.

### Images

All catalogue photos live in the project at `public/images/library/` (one 1600px JPEG per
photo plus a `-640.jpg` thumbnail used by cards). The seeder points at those files, and
admin uploads and crops go to `storage/app/public/designs` (served via `/storage`). Images
on this site are always read from disk, never fetched over HTTP. If any image is ever
referenced by a remote URL (for example an admin pastes an Unsplash link), run:

```bash
php artisan images:localize
```

It downloads every remote image into the library, writes the thumbnail and rewrites the
database to the local path. It is safe to re-run.

The library also holds five CC0 test panoramas from Poly Haven (`pano-*.jpg`, 4096×2048).
They were attached through the admin to *Verdant Haven Lounge* (two floors) and *Tatami
Rest Suite*, so a fresh `migrate:fresh --seed` does not include them. Attach them again
from the gallery manager if you reseed.

### Payments

Without `STRIPE_SECRET` in `.env` the app uses a built-in **demo checkout** (no card, no
money). Set the key to switch to Stripe Checkout; the success URL verifies the session and
unlocks the design.

### Tests

```bash
php artisan test --compact
```

32 feature tests cover downloads and their authorisation, the 360° tour, image cropping,
theme previews and activation, the admin Themes page and the seeder.

## Features

**Customer site**
- Landing page: hero with search, a carousel of design styles, trending designs, shop by room,
  editor's picks, how it works, testimonials, newsletter.
- Catalogue with filters (style, room, free/premium, tags), search with suggestions,
  sorting and pagination; every filter lives in the URL so it is shareable.
- Design detail: cover, designer notes, a gallery where every image is its own card with
  title, angle and description (e-commerce style), lightbox, like, share, and
  "You may also like" similar designs (same room + style ranked first).
- **360° tour**: one panorama viewer per floor (drag, pinch or scroll to look around,
  auto-rotate, fullscreen), with a chip per room. Each room opens at the starting view the
  admin chose.
- **Download all images**: a zip with `Photos/` and `360 panoramas/Floor N - <name>/`
  folders plus a README; the original file types are kept.
- Free designs are fully visible without login. Premium designs show only the cover and
  lock the other images and the tour until purchase (locked image URLs are never rendered).
- Auth (register/login/remember), My Studio dashboard (unlocked designs, orders, profile,
  appearance).
- Two themes, **Calm** (default) and **Neon**, switched in the admin. In Calm, visitors can
  pick light / dark / system mode, accent, text and background colour, heading font, text
  size and corner radius. Languages: English and Simplified Chinese (EN / ZH).

**Admin panel** (`/admin`)
- Dashboard: revenue chart, orders, views, catalogue mix, top designs, recent orders.
- Designs: create/edit, publish/feature toggles, price (0 = free), delete.
- **Gallery manager**: add by URL or upload, reorder, per-image title, angle and
  description, cover star. Each image is either a *Photo* or a *360° panorama*; panoramas
  are assigned to a floor and get a *Set starting view* picker. Images that aren't 2:1 are
  flagged and kept out of the tour.
- **Crop**: a crop button on every image, and an optional crop step right after
  uploading. Drag to move, resize from the corners or edges, and use the presets Free, 1:1,
  4:3, 3:2, 16:9 and 2:1 · 360° (panoramas are locked to 2:1). Crops are made at full
  resolution and saved as a new file; the original is never overwritten.
- Design styles and room types CRUD.
- Orders (status changes) and customers (role, active, delete).
- Themes: preview any theme for your own session, or activate it for all visitors.
- Site settings: branding, hero, announcement, theme defaults (colours, fonts, radius,
  whether visitors may override), currency, contact and socials.

## Project layout

```
app/Models              User, Category (style), RoomType, Design, DesignImage, Order, Setting
app/Http/Controllers    Home, Design (catalogue, suggest, like, download), Auth, Checkout, Dashboard
app/Http/Controllers/Admin   Dashboard, Design, Category, RoomType, Order, User, Theme, Setting, Upload, ImageCrop
app/Http/Middleware     EnsureAdmin, SetLocale, ApplyTheme
app/Support             helpers.php (price(), thumb(), setting(), available_themes() …), LocalImage (disk paths, sizes)
app/Console/Commands    images:localize
database/seeders        DatabaseSeeder + SeedData (taxonomy from the reference sites)
resources/views         layouts/, partials/, components/, home, designs/, auth/, checkout/, dashboard, admin/
resources/views/themes/neon   Neon theme overrides (only the views it changes)
resources/css           app.css (Calm) and theme-neon.css, Tailwind 4 themes driven by CSS variables
resources/js/app.js     Alpine: theme store, search box, design page, 360° tour, gallery manager + cropper, toasts
resources/js/panorama.js     Dependency-free WebGL equirectangular panorama viewer
lang/{en,zh}/ui.php     UI strings
```

## Routes

| Method | Path | Notes |
|--------|------|-------|
| GET | / | landing |
| GET | /designs | `q, category, roomType, price=free|paid, tag, sort, featured, page` |
| GET | /designs/suggest | JSON suggestions |
| GET | /designs/{slug} | images and tour locked unless free / purchased / admin |
| GET | /designs/{slug}/download | zip of photos + panoramas (if unlocked) |
| POST | /designs/{slug}/like | JSON |
| GET/POST | /login, /register · POST /logout | |
| POST | /designs/{slug}/checkout | Stripe redirect or demo order |
| GET | /checkout/{order} · POST /checkout/{order}/pay · GET /checkout/success | |
| GET | /dashboard?tab=purchases|orders|profile|appearance | |
| GET | /lang/{code} · /theme/{name} | switch language · preview a theme (`site` = back to live) |
| * | /admin/... | admin role; designs, categories, room-types, orders, users, themes, settings |
| POST | /admin/upload | gallery image upload (JSON) |
| POST | /admin/images/crop | save a cropped image as a new file (JSON) |

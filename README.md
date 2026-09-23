# Home Studio · home interior design marketplace (Laravel + Blade)

A web service that sells home interior design image collections. Customers browse designs
by style and room, preview free designs without an account, and sign in and pay once to
unlock every angle of a premium design. Admins manage the whole catalogue, orders,
customers and the look of the site from an admin panel.

Stack: Laravel 13 · Blade · Tailwind CSS 4 · Alpine.js · Vite · SQLite (or MySQL).

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
admin uploads go to `storage/app/public/designs` (served via `/storage`). If any image is
ever referenced by a remote URL (for example an admin pastes an Unsplash link), run:

```bash
php artisan images:localize
```

It downloads every remote image into the library, writes the thumbnail and rewrites the
database to the local path. It is safe to re-run.

### Payments

Without `STRIPE_SECRET` in `.env` the app uses a built-in **demo checkout** (no card, no
money). Set the key to switch to Stripe Checkout; the success URL verifies the session and
unlocks the design.

## Features

**Customer site**
- Landing page: hero with search, browse by style, trending designs, shop by room,
  editor's picks, how it works, testimonials, newsletter.
- Catalogue with filters (style, room, free/premium, tags), search with suggestions,
  sorting and pagination; every filter lives in the URL so it is shareable.
- Design detail: cover, designer notes, palette, gallery where every image is its own
  card with title, angle and description (e-commerce style), lightbox, like, share,
  "You may also like" similar designs (same room + style ranked first).
- Free designs are fully visible without login. Premium designs show the cover and lock
  the remaining images until purchase (locked image URLs are never rendered).
- Auth (register/login/remember), My Studio dashboard (unlocked designs, orders, profile,
  appearance).
- Customisable UI: light / dark / system mode, accent colour, text and background colour,
  heading font, text size and corner radius, saved per visitor. Languages: EN / KO / JA / ZH.

**Admin panel** (`/admin`)
- Dashboard: revenue, orders, views, catalogue mix, top designs, recent orders.
- Designs: create/edit, gallery manager (add by URL or upload, reorder, per-image title,
  angle and description, cover), price (0 = free), publish/feature toggles, delete.
- Design styles and room types CRUD.
- Orders (status changes) and customers (role, active, delete).
- Site settings: branding, hero, announcement, theme defaults (colours, fonts, radius,
  whether visitors may override), currency, contact and socials.

## Project layout

```
app/Models              User, Category (style), RoomType, Design, DesignImage, Order, Setting
app/Http/Controllers    Home, Design (catalogue + suggest), Auth, Checkout, Dashboard
app/Http/Controllers/Admin   Dashboard, Design, Category, RoomType, Order, User, Setting, Upload
app/Http/Middleware     EnsureAdmin, SetLocale
app/Support/helpers.php price(), thumb(), setting(), compact_number()
database/seeders        DatabaseSeeder + SeedData (taxonomy from the reference sites)
resources/views         layouts/, partials/, components/, home, designs/, auth/, checkout/, dashboard, admin/
resources/css/app.css   Tailwind 4 theme driven by CSS variables (runtime themable)
resources/js/app.js     Alpine: theme store, search box, design page, gallery manager, toasts
lang/{en,ko,ja,zh}/ui.php   UI strings
```

## Routes

| Method | Path | Notes |
|--------|------|-------|
| GET | / | landing |
| GET | /designs | `q, category, roomType, price=free|paid, tag, sort, featured, page` |
| GET | /designs/suggest | JSON suggestions |
| GET | /designs/{slug} | images locked unless free / purchased / admin |
| POST | /designs/{slug}/like | JSON |
| GET/POST | /login, /register · POST /logout | |
| POST | /designs/{slug}/checkout | Stripe redirect or demo order |
| GET | /checkout/{order} · POST /checkout/{order}/pay · GET /checkout/success | |
| GET | /dashboard?tab=purchases|orders|profile|appearance | |
| * | /admin/... | admin role; designs, categories, room-types, orders, users, settings, upload |

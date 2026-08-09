<div align="center">

# 📸 Private Photo Gallery Platform

**A self-hosted, white-label photography delivery platform** — a drop-in alternative to [Wfolio](https://wfolio.com) for photographers who want full control over their client galleries.

Upload entire shoot folders, let the system build galleries automatically, set an expiration date, share a link with your client. That's it.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel 13](https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![React 18](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=black)](https://react.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com)

[Features](#-features) • [Quick Start](#-quick-start) • [Tech Stack](#-tech-stack) • [Architecture](#-architecture) • [Deployment](#-deployment) • [Roadmap](#-roadmap)

</div>

---

## ✨ Features

### For the photographer (admin)

- **📁 Drag-and-drop folder uploads** — drop a folder of RAW/JPGs and each subfolder becomes a gallery tab automatically. No more manual organization.
- **⚡ Background image processing** — originals stay untouched. Web-optimized WebP (≤2000px) and 400px thumbnails are generated asynchronously in a queue.
- **🖼️ Auto + manual hero selection** — first photo of the first gallery is picked automatically. Override it with a single click on any photo.
- **🔒 Per-project password protection** — bcrypt-hashed passwords, cookie-based access for 7 days, deep links respect the gate.
- **⏰ Expiration dates** — set a deadline, the scheduled task archives the project automatically and the link starts returning 410.
- **📦 Bulk ZIP downloads (server-side, lossless)** — either compile on the server (≤2 GB) or upload a pre-made archive via FTP for huge projects. ZIPs are stored uncompressed so originals stay byte-identical.
- **📊 Real statistics** — unique daily views (de-duped by IP+day) and download counts. Sparkline chart of the last 10 days in the admin panel.
- **🎛️ Anti-theft mode** — disable downloads per project → buttons vanish, right-click and drag are blocked, original files are never publicly listed.
- **🔗 Share & deep-link** — every photo gets a copyable `?photo={id}` URL that opens the lightbox on that exact frame.

### For the client (gallery view)

- **🌒 Dark, photo-first UI** — Outfit + Inter, ambient gold accents, fully responsive, zero branding on the client side.
- **🧱 Justified grid layout** — same engine as Flickr (no cropping, every row matches the previous in height).
- **🔍 PhotoSwipe 5 lightbox** — pinch-zoom, swipe, keyboard nav, fullscreen, custom download & share buttons.
- **🔐 Password gate** — full-screen modal with shake animation on a wrong password.
- **📅 Expiration badge** — `Available until: July 15, 2026` shown next to the download button.
- **⬇️ Download single originals** — temporary signed URLs (24h), original filename preserved.
- **📥 Download all as ZIP** — with real progress polling, ready/downloading/error states.
- **📱 Mobile-first** — tested on iOS Safari and Android Chrome (long-press save blocked when anti-theft is on).

---

## 🖼️ Screenshots

> *Drop your own screenshots into `docs/screenshots/` and link them here.*

| Admin dashboard | Project detail |
|---|---|
| `docs/screenshots/admin-dashboard.png` | `docs/screenshots/admin-project.png` |

| Public hero + grid | Lightbox | Password gate |
|---|---|---|
| `docs/screenshots/gallery-hero.png` | `docs/screenshots/lightbox.png` | `docs/screenshots/password-gate.png` |

---

## 🚀 Quick start

### Requirements

| Component | Version |
|-----------|---------|
| PHP | 8.3 or 8.4 |
| Composer | 2.x |
| Node.js | 20.x (for the React build) |
| Database | MySQL 8.0+ / MariaDB 10.6+ |
| PHP extensions | `gd` (or `imagick`), `zip`, `mbstring`, `xml`, `curl`, `pdo_mysql` |

### Local development

```bash
# 1. Clone & install
git clone https://github.com/<your-username>/photo-gallery.git
cd photo-gallery
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (edit .env first)
php artisan migrate --seed

# 4. Run the dev stack (artisan serve + queue worker + Vite)
composer run dev
```

Open:

- **Public landing:** http://127.0.0.1:8000
- **Client gallery (try a seeded project):** http://127.0.0.1:8000/your-slug
- **Admin panel:** http://127.0.0.1:8000/admin/login

Default seeded admin credentials (change them immediately):

```
email:    admin@gallery.nagiyev.com
password: changeme
```

You can also create a new admin from the CLI:

```bash
php artisan admin:reset-password
```

---

## 🧱 Tech stack

### Backend

- **[Laravel 13](https://laravel.com)** — routing, ORM, queue, scheduler, signed URLs
- **[Intervention Image v4](https://image.intervention.io/)** — image processing (Imagick driver preferred, GD fallback)
- **PHP `ZipArchive`** — packaging originals (no compression = no quality loss)
- **Database queue driver** — no Redis needed for small studios

### Frontend

- **[React 18](https://react.dev) + [Vite 6](https://vitejs.dev)** — public gallery SPA
- **[PhotoSwipe 5](https://photoswipe.com)** — lightbox
- **[justified-layout](https://github.com/flickr/justified-layout)** — Flickr's row-justified grid
- **[Alpine.js 3](https://alpinejs.dev)** — admin interactivity (toggles, modals)
- **[SortableJS](https://sortablejs.github.io/Sortable/)** — drag-to-reorder galleries & photos
- Vanilla CSS, design tokens via custom properties (no Tailwind, no Bootstrap)

### Database

- **MySQL 8 / MariaDB 10.6+** — 5 tables (`projects`, `galleries`, `photos`, `project_views`, `download_logs`) + Laravel's built-in `users`, `jobs`, `cache`, `sessions`

---

## 🏗️ Architecture

```
Local dev (optional)              CI / deploy
─────────────                    ───────────
                                 git push ─▶ GitHub Actions
                                                │
                                                ▼
                       Rocky / Ubuntu + Apache + PHP 8.4
                       ┌──────────────────────────────┐
                       │  public/  (document root)    │
                       │   ├── index.php              │
                       │   ├── build/  (Vite output)  │
                       │   └── storage  (symlink)     │
                       ├──────────────────────────────┤
                       │  app/  (Laravel)             │
                       │   ├── Admin/*   Blade + Alpine │
                       │   ├── Api/*     JSON for SPA  │
                       │   ├── Jobs/     queue workers │
                       │   └── Services/ ImageService │
                       ├──────────────────────────────┤
                       │  storage/app/                │
                       │   ├── originals/  (private)  │
                       │   ├── web/        (public)   │
                       │   ├── thumbnails/ (public)   │
                       │   └── zips/       (private)  │
                       └──────────────────────────────┘
                                │           │
                            MySQL       Scheduler
                            (5 tables)  (cron)
```

### Request flow

1. **Admin** opens `/admin/{project}` — Blade view, sidebar layout, dark theme.
2. Admin drags `Wedding/2026-06-12/Ceremony/*.jpg` into the drop zone.
3. Browser sends `webkitdirectory` files to `POST /admin/projects/{id}/upload` with `gallery_name="Ceremony"`.
4. Laravel saves originals to `storage/app/originals/{project}/{gallery}/`, creates a `Photo` row, and dispatches a `ProcessImage` job.
5. Queue worker resizes to WebP (≤2000px) + 400px thumbnail, updates paths.
6. Photographer shares `https://gallery.example.com/wedding-smith-johnson` with the client.
7. Client opens the link → React SPA fetches `GET /api/projects/{slug}` → renders hero + tabs + grid.
8. Client clicks a photo → PhotoSwipe lightbox → can download single or all-as-ZIP.

### Scheduled jobs (`routes/console.php`)

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `queue:work --stop-when-empty --max-time=55` | every minute | processes image / ZIP jobs |
| `projects:expire` | hourly | archives projects past their `expires_at` |
| `zips:cleanup` | every 6 hours | deletes ZIP files older than 24h |

---

## ⚙️ Configuration

### `.env` essentials

```env
APP_NAME="Photo Gallery"
APP_URL=https://gallery.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=gallery
DB_USERNAME=gallery
DB_PASSWORD=secret

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=local
```

### GitHub Actions / FTP deploy

The repo ships with `.github/workflows/deploy.yml` (FTP variant). On push to `main` it builds the React frontend and syncs the project to your server.

Add the following **repository secrets** in **Settings → Secrets and variables → Actions**:

| Secret | Example |
|--------|---------|
| `FTP_SERVER` | `ftp.gallery.example.com` |
| `FTP_USERNAME` | `gallery` |
| `FTP_PASSWORD` | `********` |

> Prefer SSH/rsync over FTP? The included workflow is a 1-file swap — see [Deployment → SSH variant](#-deployment) below.

### Server-side cron (required)

```cron
* * * * * cd /home/you/gallery.example.com && php artisan schedule:run >> /dev/null 2>&1
```

Without it, images won't process and expired projects won't archive.

### Apache document root

Point your vhost to `/path/to/project/public/`. If you can't change it, drop a `.htaccess` at the project root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 📁 Project structure

```
.
├── app/
│   ├── Console/Commands/           # projects:expire, zips:cleanup, admin:reset-password
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/              # Blade-rendered admin pages
│   │   │   └── Api/                # JSON endpoints for the React SPA
│   │   ├── Middleware/             # AdminAuth, CheckProjectAccess, TrackProjectView
│   │   └── Requests/               # Form validation
│   ├── Jobs/                       # ProcessImage, GenerateZip
│   ├── Models/                     # Project, Gallery, Photo, ProjectView, DownloadLog, Admin
│   └── Services/ImageService.php   # Intervention Image wrapper
├── database/
│   ├── migrations/                 # 5-table schema
│   └── seeders/DatabaseSeeder.php  # creates the initial admin
├── resources/
│   ├── css/{admin,gallery}.css     # design tokens + components
│   ├── js/
│   │   ├── admin/app.js            # Alpine + SortableJS + upload logic
│   │   └── gallery/                # React SPA
│   │       ├── App.jsx
│   │       ├── components/         # HeroSection, JustifiedGrid, Lightbox, ...
│   │       └── hooks/useAntiTheft.js
│   └── views/
│       ├── admin/                  # Blade admin pages
│       ├── gallery/app.blade.php   # SPA shell
│       └── landing.blade.php       # public homepage
├── routes/
│   ├── web.php                     # admin + API + SPA catch-all
│   └── console.php                 # scheduler
├── tests/
│   ├── Feature/SetProjectCoverTest.php
│   └── Unit/{ImageServiceTest,PublicStorageTest}.php
└── docs/                           # implementation plan, deployment guide, TZ
```

---

## 🔌 API reference

All endpoints live under `/api/`. JSON in, JSON out.

### `GET /api/projects/{slug}`

Returns project metadata + gallery list. Logs a unique view.

`200` → `{ project: { title, slug, hero_image_url, allow_download, expires_at, galleries: [...] } }`
`401` → `{ requires_password: true }` (when password-gated and no valid cookie)
`410` → `{ error: "expired" }`

### `POST /api/projects/{slug}/verify-password`

Body: `{ "password": "secret" }` → sets `project_access_{id}` cookie for 7 days on success.

### `GET /api/projects/{slug}/galleries/{gallerySlug}`

Returns the gallery's photos with WebP `web_url`, `thumbnail_url`, and a 24h signed `download_url` (only if `allow_download`).

### `POST /api/projects/{slug}/download-all`

Triggers the `GenerateZip` job, returns `{ token }` immediately. Use the next two endpoints to poll.

### `GET /api/downloads/{token}/status` · `GET /api/downloads/{token}/file`

Status is cached for 2 hours.

### `GET /api/photos/{photo}/download` (signed)

Laravel signed URL, valid for 24h, served via `?signature=...`. Used by the lightbox.

Full schema with request/response examples is in [`docs/implementation_plan.md`](docs/implementation_plan.md).

---

## 🧪 Testing

```bash
composer test                 # or: php artisan test
```

The test suite covers:

- `SetProjectCoverTest` — admin can set a project cover from a processed photo, foreign-project photos are rejected
- `ImageServiceTest` — WebP/thumbnail generation round-trips
- `PublicStorageTest` — `public/storage` symlink exposes the right paths

Add new tests next to the ones above and run them in CI.

---

## 🚢 Deployment

### Variant A — FTP (default, what the included workflow uses)

The shipped `.github/workflows/deploy.yml` uses [`SamKirkland/FTP-Deploy-Action`](https://github.com/SamKirkland/FTP-Deploy-Action). On every push to `main` it:

1. Installs Node deps
2. Runs `npm run build` (Vite → `public/build/`)
3. Uploads the project via FTP, **excluding** `.git/`, `node_modules/`, `storage/app/originals/`, `storage/app/web/`, `storage/app/thumbnails/`, `storage/app/zips/`

Add `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` to repo secrets and you're done.

### Variant B — SSH + rsync (recommended for bigger servers)

Swap the workflow's deploy step for:

```yaml
- name: rsync to server
  uses: burnett01/rsync-deployments@5.2
  with:
    switches: -avz --delete --exclude='.git' --exclude='node_modules' --exclude='storage/app/originals' --exclude='storage/app/web' --exclude='storage/app/thumbnails' --exclude='storage/app/zips'
    remote_path: ${{ secrets.DEPLOY_PATH }}/
    remote_host: ${{ secrets.SSH_HOST }}
    remote_user: ${{ secrets.SSH_USER }}
    remote_key: ${{ secrets.SSH_KEY }}

- name: Post-deploy on server
  uses: appleboy/ssh-action@v1
  with:
    host: ${{ secrets.SSH_HOST }}
    username: ${{ secrets.SSH_USER }}
    key: ${{ secrets.SSH_KEY }}
    script: |
      cd ${{ secrets.DEPLOY_PATH }}
      composer install --no-dev --optimize-autoloader
      php artisan migrate --force
      php artisan config:cache route:cache view:cache
      php artisan storage:link
```

### Post-deploy checklist

1. SSH to the server, `cd` into the project root.
2. `php artisan db:seed --class=DatabaseSeeder` to create the first admin.
3. `php artisan admin:reset-password` and set a real password.
4. Add the cron job (see above).
5. Confirm `public/storage` symlink exists (`ls -la public/storage`).
6. Upload a test project, verify thumbnails, hit `?photo=` deep link, trigger a ZIP download.

---

## 🗺️ Roadmap

- [ ] Multi-admin with role-based access control
- [ ] S3 / Spaces / B2 disks (currently local-only)
- [ ] Client-side favorites / selection
- [ ] Per-photo comments / proofing workflow
- [ ] Watermark toggle
- [ ] Self-service account creation for studios (multi-tenant)
- [ ] Stripe-based print ordering integration
- [ ] Native iOS / Android viewer apps

Have an idea? Open an issue.

---

## 🤝 Contributing

PRs are welcome. For larger changes please open an issue first so we can discuss the direction.

1. Fork the repo.
2. Create a feature branch: `git checkout -b feat/my-change`.
3. Write tests for any new logic.
4. Make sure `composer test` and `npm run build` pass.
5. Open a pull request describing the change and linking any related issue.

Please follow the existing code style: PSR-12 on the PHP side, ESLint defaults on the React side, and keep components small & composable.

---

## 📄 License

The MIT License applies — see [LICENSE](LICENSE). TL;DR: do whatever you want, just don't blame us if something breaks. Attribution is appreciated but not required.

---

## 🙏 Acknowledgments

- Inspired by [Wfolio](https://wfolio.com) and [Pixieset](https://pixieset.com) for the UX patterns.
- [PhotoSwipe](https://photoswipe.com) by Dmitry Semenov.
- [justified-layout](https://github.com/flickr/justified-layout) by Flickr.
- [Intervention Image](https://image.intervention.io/) by Oliver Vogel.
- Built on [Laravel](https://laravel.com) by Taylor Otwell & contributors.

---

<div align="center">
<sub>Made for photographers who'd rather own their platform than rent it.</sub>
</div>

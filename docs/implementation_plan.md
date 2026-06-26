# Private Photography Delivery Platform — Implementation Plan

**Domain:** `gallery.nagiyev.com` (SSL enabled)  
**Server:** Rocky Linux 8 · PHP 8.4 · MySQL 8.4 · Apache · SSH · Cron  
**Server IP:** `162.210.97.28`  
**Single admin:** photographer (no roles, no multi-user)

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                  LOCAL (Mac)                         │
│  Write code only. No PHP/Laravel/DB running locally. │
│  Node.js used ONLY for React frontend build in CI.   │
│  Git push → triggers auto-deploy                     │
└──────────────────────┬──────────────────────────────┘
                       │ git push
                       ▼
┌─────────────────────────────────────────────────────┐
│               GitHub Actions (CI/CD)                 │
│  1. npm install + npm run build (React SPA)          │
│  2. rsync project files to server via SSH            │
│  3. SSH: composer install, migrate, cache            │
└──────────────────────┬──────────────────────────────┘
                       │ SSH + rsync
                       ▼
┌─────────────────────────────────────────────────────┐
│          SERVER (gallery.nagiyev.com)                 │
│  Apache → Laravel public/ (document root)            │
│  PHP 8.4 handles admin (Blade) + API (JSON)          │
│  React SPA served as static files from public/       │
│  MySQL 8.4 for data                                  │
│  Cron → Laravel scheduler (queues, expiration)       │
│  Storage: /originals, /web, /thumbnails              │
└─────────────────────────────────────────────────────┘
```

### Tech Stack (final)

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | 11.x |
| PHP | PHP | 8.4 |
| Database | MySQL | 8.4 |
| Admin UI | Blade + Alpine.js + Vanilla CSS | Alpine 3.x |
| Public Frontend | React + Vite (Laravel Vite Plugin) | React 18, Vite 6 |
| Image Processing | Intervention Image | v3 |
| Lightbox | PhotoSwipe | 5.x |
| Justified Layout | justified-layout (Flickr) | latest |
| Drag-and-Drop (admin) | SortableJS | latest |
| Queue Driver | database | — |
| Web Server | Apache | — |
| CI/CD | GitHub Actions | — |

---

## 2. Development Workflow & Deployment

### 2.1. What the User Needs to Set Up

> [!IMPORTANT]
> **GitHub Repository:** Create a **private** GitHub repo (e.g., `Photo` or `gallery-platform`). Initialize it with the existing `docs/` folder.

> [!IMPORTANT]  
> **GitHub Secrets:** Add these secrets in the repo → Settings → Secrets and variables → Actions:
>
> | Secret Name | Value |
> |-------------|-------|
> | `SSH_HOST` | `162.210.97.28` |
> | `SSH_USER` | *(your SSH username on the server)* |
> | `SSH_KEY` | *(contents of your SSH private key — the file in your user folder)* |
> | `DEPLOY_PATH` | *(absolute path to the project on the server, e.g., `/home/username/gallery.nagiyev.com`)* |

> [!IMPORTANT]
> **Server Preparation (via SSH):**
> 1. Confirm Apache is the web server: `httpd -v` or `apachectl -v`
> 2. Check PHP extensions: `php -m | grep -iE "gd|imagick|zip|mbstring|xml|curl|mysql"`
> 3. Check Composer: `composer --version` (if missing, install: `php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php --install-dir=/usr/local/bin --filename=composer`)
> 4. Confirm `DEPLOY_PATH` exists and is the document root for `gallery.nagiyev.com`
> 5. Confirm cron access: `crontab -l`

### 2.2. Git Setup (Local)

```bash
cd /Users/faignaghiyev/DEV/Photo
git init
git remote add origin git@github.com:<USERNAME>/<REPO>.git
git add .
git commit -m "Initial commit: project setup"
git push -u origin main
```

### 2.3. CI/CD Pipeline

#### [NEW] `.github/workflows/deploy.yml`

Triggered on push to `main`. Steps:
1. Checkout code
2. Install Node.js 20, `npm ci`, `npm run build` (builds React SPA → `public/build/`)
3. rsync project to server (excluding `.git/`, `node_modules/`, `.env`, `storage/app/originals/`, `storage/app/web/`, `storage/app/thumbnails/`, `storage/app/zips/`)
4. SSH into server and run:
   ```bash
   cd $DEPLOY_PATH
   composer install --no-dev --optimize-autoloader --no-interaction
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan storage:link
   ```

### 2.4. Server Cron Job (one-time setup via SSH)

```bash
crontab -e
# Add this line:
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 2.5. Apache Configuration

The document root for `gallery.nagiyev.com` must point to `{DEPLOY_PATH}/public/`.

Laravel ships with a `public/.htaccess` that handles URL rewriting. No extra Apache config needed on shared hosting if `mod_rewrite` is enabled (usually is).

If the hosting panel doesn't allow changing document root, we'll create a `.htaccess` in the hosting root that redirects to `public/`:

```apache
# .htaccess in DEPLOY_PATH root (only if document root ≠ public/)
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 3. Database Schema

### 3.1. Entity Relationship

```
Admin (1) ──── manages ────▶ Projects (many)
Project (1) ──── contains ──▶ Galleries (many)
Gallery (1) ──── contains ──▶ Photos (many)
Project (1) ──── has ──▶ ProjectViews (many)
Project (1) ──── has ──▶ DownloadLogs (many)
Project.hero_photo_id ──── FK ──▶ Photos.id
```

### 3.2. Complete SQL Schema

```sql
-- 001_create_admins_table
CREATE TABLE admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 002_create_projects_table
CREATE TABLE projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    is_password_protected BOOLEAN NOT NULL DEFAULT FALSE,
    password VARCHAR(255) NULL COMMENT 'bcrypt hashed',
    allow_download BOOLEAN NOT NULL DEFAULT TRUE,
    expires_at TIMESTAMP NOT NULL,
    status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    hero_photo_id BIGINT UNSIGNED NULL,
    total_views INT UNSIGNED NOT NULL DEFAULT 0,
    total_downloads INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_expires_at (expires_at)
);

-- 003_create_galleries_table
CREATE TABLE galleries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_project_gallery (project_id, slug),
    INDEX idx_sort_order (project_id, sort_order)
);

-- 004_create_photos_table
CREATE TABLE photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallery_id BIGINT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    original_path VARCHAR(500) NOT NULL,
    web_path VARCHAR(500) NULL,
    thumbnail_path VARCHAR(500) NULL,
    width INT UNSIGNED NOT NULL DEFAULT 0,
    height INT UNSIGNED NOT NULL DEFAULT 0,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_processed BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE,
    INDEX idx_sort_order (gallery_id, sort_order)
);

-- Add FK for hero_photo_id after photos table exists
ALTER TABLE projects ADD FOREIGN KEY (hero_photo_id) REFERENCES photos(id) ON DELETE SET NULL;

-- 005_create_project_views_table
CREATE TABLE project_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_daily_view (project_id, ip_address, created_at),
    INDEX idx_project_created (project_id, created_at)
);

-- 006_create_download_logs_table
CREATE TABLE download_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id BIGINT UNSIGNED NOT NULL,
    photo_id BIGINT UNSIGNED NULL,
    type ENUM('single', 'zip') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE SET NULL,
    INDEX idx_project (project_id)
);

-- 007_create_jobs_table (Laravel built-in)
-- 008_create_failed_jobs_table (Laravel built-in)
-- 009_create_cache_table (Laravel built-in, for cache driver)
-- 010_create_sessions_table (Laravel built-in, for session driver)
```

### 3.3. Seeder: Admin User

```php
// database/seeders/AdminSeeder.php
Admin::create([
    'email' => 'admin@gallery.nagiyev.com',  // User will change this
    'password' => Hash::make('changeme'),      // User will change this
]);
```

> [!WARNING]
> After first deploy, the user MUST change admin credentials via `php artisan tinker` on the server or we provide an artisan command `php artisan admin:reset-password`.

---

## 4. Backend Specification (Laravel)

### 4.1. File Structure

```
app/
├── Console/
│   └── Commands/
│       ├── ExpireProjects.php         # Archive expired projects
│       ├── CleanupZips.php            # Delete old ZIP files
│       └── ResetAdminPassword.php     # CLI tool to reset admin password
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── AuthController.php     # Login/logout
│   │   │   ├── DashboardController.php# Overview stats
│   │   │   ├── ProjectController.php  # CRUD projects
│   │   │   ├── GalleryController.php  # Manage galleries
│   │   │   └── PhotoController.php    # Upload, sort, delete photos
│   │   └── Api/
│   │       ├── ProjectController.php  # Public project data
│   │       ├── GalleryController.php  # Public gallery photos
│   │       ├── PasswordController.php # Verify project password
│   │       └── DownloadController.php # Download single/ZIP
│   ├── Middleware/
│   │   ├── AdminAuth.php              # Protect /admin/* routes
│   │   ├── CheckProjectAccess.php     # Check expiry + password
│   │   └── TrackProjectView.php       # Record unique views
│   └── Requests/
│       ├── StoreProjectRequest.php    # Validation for project create
│       └── UpdateProjectRequest.php   # Validation for project update
├── Jobs/
│   ├── ProcessImage.php               # Resize + WebP conversion
│   └── GenerateZip.php                # Create ZIP of originals
├── Models/
│   ├── Admin.php
│   ├── Project.php
│   ├── Gallery.php
│   ├── Photo.php
│   ├── ProjectView.php
│   └── DownloadLog.php
└── Services/
    ├── ImageService.php               # Intervention Image wrapper
    └── SlugService.php                # Generate unique slugs
```

### 4.2. Models — Relationships & Key Logic

#### `Project` model
```php
// Relationships:
hasMany(Gallery::class)->orderBy('sort_order')
hasMany(ProjectView::class)
hasMany(DownloadLog::class)
belongsTo(Photo::class, 'hero_photo_id')

// Scopes:
scopeActive($q)   → where('status', 'active')
scopeArchived($q) → where('status', 'archived')
scopeExpired($q)  → where('expires_at', '<', now())->where('status', 'active')

// Accessors:
isExpired(): bool → expires_at < now()
heroImageUrl(): ?string → hero photo's web_path or first photo of first gallery
```

#### `Gallery` model
```php
belongsTo(Project::class)
hasMany(Photo::class)->orderBy('sort_order')
```

#### `Photo` model
```php
belongsTo(Gallery::class)

// Accessors:
webUrl(): string      → URL to web-optimized version
thumbnailUrl(): string → URL to thumbnail
originalUrl(): string  → signed URL to original (for download)
aspectRatio(): float   → width / height
```

### 4.3. Routes

#### `routes/web.php` — Admin + SPA Shell
```php
// --- Admin Auth ---
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login']);
    Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

    // --- Protected Admin Routes ---
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        
        // Projects
        Route::resource('projects', Admin\ProjectController::class);
        
        // Galleries (nested under project)
        Route::prefix('projects/{project}')->name('projects.')->group(function () {
            Route::post('galleries/sort', [Admin\GalleryController::class, 'sort'])->name('galleries.sort');
            Route::resource('galleries', Admin\GalleryController::class)->except(['index', 'show']);
            
            // Photos
            Route::post('upload', [Admin\PhotoController::class, 'upload'])->name('upload');
            Route::post('photos/sort', [Admin\PhotoController::class, 'sort'])->name('photos.sort');
            Route::delete('photos/{photo}', [Admin\PhotoController::class, 'destroy'])->name('photos.destroy');
        });
    });
});

// --- SPA Catch-All (must be LAST) ---
Route::get('/{slug}', function () {
    return view('gallery.app');
})->where('slug', '[a-z0-9][a-z0-9\-]*')->name('project.show');
```

#### `routes/api.php` — Public API
```php
Route::prefix('projects/{slug}')->group(function () {
    Route::get('/', [Api\ProjectController::class, 'show']);
    Route::post('verify-password', [Api\PasswordController::class, 'verify']);
    Route::get('galleries/{gallerySlug}', [Api\GalleryController::class, 'show']);
    Route::post('download-all', [Api\DownloadController::class, 'requestZip']);
});

Route::get('photos/{photo}/download', [Api\DownloadController::class, 'downloadSingle'])
    ->name('photo.download')
    ->middleware('signed'); // Laravel signed URLs

Route::get('downloads/{token}/status', [Api\DownloadController::class, 'zipStatus']);
Route::get('downloads/{token}/file', [Api\DownloadController::class, 'downloadZip']);
```

### 4.4. Key Controllers — Behavior Specification

#### `Admin\PhotoController::upload()`
1. Accept multipart file upload with metadata: `file`, `gallery_name`, `project_id`
2. If gallery with `gallery_name` doesn't exist → create it (slug from name, next sort_order)
3. Save original file to `storage/app/originals/{project_id}/{gallery_id}/{filename}`
4. Create `Photo` record with `is_processed = false`
5. Dispatch `ProcessImage` job to queue
6. Return JSON response with upload status

> [!NOTE]
> **Folder-based upload:** The JavaScript on the admin upload page uses `webkitdirectory` File API to read folder structure. Each file is sent with its parent folder name as `gallery_name`. The backend auto-creates galleries from unique folder names.

#### `ProcessImage` Job
1. Load original from `original_path` using Intervention Image v3
2. Read `width` and `height` → update Photo record
3. Generate web version:
   - Format: WebP (fallback JPEG if WebP not supported)
   - Max dimension: 2000px on longest side (maintain aspect ratio)
   - Quality: 85
   - Save to: `storage/app/web/{project_id}/{gallery_id}/{filename}.webp`
4. Generate thumbnail:
   - Format: WebP
   - Height: 400px (maintain aspect ratio)
   - Quality: 80
   - Save to: `storage/app/thumbnails/{project_id}/{gallery_id}/{filename}.webp`
5. Update Photo: `web_path`, `thumbnail_path`, `width`, `height`, `file_size`, `is_processed = true`
6. If this is the first photo of the first gallery of the project AND `hero_photo_id` is null → set as hero

#### `GenerateZip` Job
1. Receive `project_id` and `token` (UUID)
2. Collect all original file paths from all galleries
3. Create ZIP using PHP `ZipArchive` extension:
   - Structure: `{project_title}/{gallery_title}/{original_filename}`
   - Compression: `ZipArchive::CM_STORE` (no compression, preserves originals 1:1, faster)
4. Save to `storage/app/zips/{token}.zip`
5. Store completion status in `cache` with key `zip:{token}` → `{status: 'ready', size: bytes}`
6. ZIP files auto-deleted after 24 hours by `CleanupZips` command

#### `Api\DownloadController::downloadSingle()`
1. Validate signed URL (Laravel `ValidateSignature` middleware)
2. Check project `allow_download === true`
3. Check project not expired
4. Log download to `download_logs` table
5. Increment `projects.total_downloads`
6. Return file download response (original file, `Content-Disposition: attachment`)

### 4.5. Middleware Details

#### `AdminAuth`
- Check `session('admin_id')` is set and exists in `admins` table
- If not → redirect to `/admin/login`
- Admin session lifetime: 7 days (configured in `config/session.php`)

#### `CheckProjectAccess` (used on API routes)
- Load project by slug
- If `status === 'archived'` or `expires_at < now()` → return `410 Gone`
- If `is_password_protected === true`:
  - Check cookie `project_access_{project_id}` exists and matches expected hash
  - If missing/invalid → return `401` with `{requires_password: true}`

#### `TrackProjectView` (used on `GET /api/projects/{slug}`)
- Extract client IP
- Check if `project_views` has a record with same `project_id` + `ip_address` in last 24 hours
- If no → create record + increment `projects.total_views`

### 4.6. Scheduled Commands

```php
// routes/console.php (Laravel 11) or app/Console/Kernel.php

// Every hour: archive expired projects
Schedule::command('projects:expire')->hourly();

// Every 6 hours: delete ZIP files older than 24h
Schedule::command('zips:cleanup')->everySixHours();

// Every minute: process queued jobs
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()
    ->withoutOverlapping();
```

---

## 5. Admin Panel UI Specification

### 5.1. Design System

- **Theme:** Dark (background: `#0f0f0f`, cards: `#1a1a1a`, borders: `#2a2a2a`)
- **Accent color:** `#7c5bf0` (muted purple)
- **Text:** `#f0f0f0` (primary), `#888` (secondary)
- **Font:** System font stack (`-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`)
- **Border radius:** `8px` (cards), `6px` (inputs/buttons)
- **Transitions:** `0.2s ease` on interactive elements

### 5.2. Layout

```
┌─────────────────────────────────────────────┐
│ ┌──────────┐ ┌────────────────────────────┐ │
│ │          │ │  Header (breadcrumb)        │ │
│ │ Sidebar  │ ├────────────────────────────┤ │
│ │          │ │                            │ │
│ │ • Home   │ │  Main Content Area         │ │
│ │ • Projects│ │                            │ │
│ │          │ │                            │ │
│ │ ──────── │ │                            │ │
│ │ Logout   │ │                            │ │
│ └──────────┘ └────────────────────────────┘ │
└─────────────────────────────────────────────┘
```

### 5.3. Pages

#### Login Page (`/admin/login`)
- Centered card on dark background
- Email + password fields
- "Sign In" button
- No "forgot password" (single admin, use artisan command)

#### Dashboard (`/admin`)
- Stats cards: Total Projects (active), Total Views (all time), Total Downloads
- Recent Projects list (last 5, with status badges)

#### Projects List (`/admin/projects`)
- Table/grid with: thumbnail (hero), title, status badge, gallery count, photo count, expires date
- Filters: Active / Archived / All
- "New Project" button
- Click row → project detail

#### Project Create (`/admin/projects/create`)
- Form: Title (required)
- Expiration date picker (required)
- Password toggle + password field (optional)
- Allow download toggle (default: on)
- "Create" button → redirects to project detail

#### Project Detail (`/admin/projects/{id}`)
Tabs: **Settings** | **Upload** | **Gallery** | **Stats**

**Settings tab:**
- Edit title, password, download, expiration
- Project URL display (copyable link)
- Delete project (with confirmation modal)

**Upload tab:**
- Large drag-and-drop zone (supports folder drop via `webkitdirectory`)
- Text: "Drag folders here. Each folder becomes a gallery tab."
- Upload progress bars per file
- Overall progress indicator

**Gallery tab:**
- Gallery tabs (draggable for reordering via SortableJS)
- Photo grid within active gallery (draggable for reordering)
- Each photo card: thumbnail + delete button
- Rename gallery inline

**Stats tab:**
- Views chart (simple bar chart, last 30 days, implemented in vanilla Canvas/SVG)
- Downloads count (single + ZIP)
- No external chart library needed — simple SVG bars

### 5.4. Blade Template Files

```
resources/views/
├── admin/
│   ├── layouts/
│   │   └── app.blade.php        # Base layout (sidebar + content slot)
│   ├── auth/
│   │   └── login.blade.php      # Login page
│   ├── dashboard.blade.php      # Dashboard
│   └── projects/
│       ├── index.blade.php      # Projects list
│       ├── create.blade.php     # Create project form
│       ├── edit.blade.php       # Edit project settings
│       └── show.blade.php       # Project detail (tabbed: upload, gallery, stats)
└── gallery/
    └── app.blade.php            # React SPA shell (just <div id="app"> + Vite assets)
```

### 5.5. Admin JS/CSS Assets

```
resources/
├── css/
│   └── admin.css               # Complete admin styles (dark theme, all components)
└── js/
    └── admin/
        └── app.js              # Alpine.js init + upload logic + SortableJS init
```

---

## 6. Public Frontend (React SPA) Specification

### 6.1. Design Philosophy

- **Photography-first:** Minimal UI, photos are the hero. Dark backgrounds make colors pop.
- **Elegant & Premium:** Smooth animations, refined typography, subtle glassmorphism.
- **No branding:** White-label. Only a small footer link and custom favicon.

### 6.2. Design Tokens

```css
/* Color Palette */
--bg-primary: #0a0a0a;          /* Page background */
--bg-secondary: #111111;        /* Cards, sections */
--bg-elevated: #1a1a1a;         /* Elevated surfaces */
--bg-overlay: rgba(0,0,0,0.85); /* Lightbox overlay */

--text-primary: #f5f5f5;
--text-secondary: #999999;
--text-muted: #666666;

--accent: #c8a97e;              /* Warm gold — elegant, photography-appropriate */
--accent-hover: #d4b88a;

--border: rgba(255,255,255,0.08);

/* Typography */
--font-display: 'Outfit', sans-serif;   /* Headings, hero text */
--font-body: 'Inter', sans-serif;       /* Body, UI elements */

/* Spacing */
--gap-grid: 4px;                /* Gap between photos in justified grid */

/* Transitions */
--transition-fast: 150ms ease;
--transition-normal: 300ms ease;
--transition-slow: 600ms cubic-bezier(0.16, 1, 0.3, 1);
```

### 6.3. React File Structure

```
resources/js/gallery/
├── main.jsx                    # ReactDOM.createRoot, mount to #app
├── App.jsx                     # Single route: /{slug} → ProjectPage
├── api/
│   └── client.js              # fetch wrapper, base URL = /api
├── components/
│   ├── HeroSection.jsx        # 100vh hero with project title
│   ├── GalleryTabs.jsx        # Tab bar for gallery navigation
│   ├── JustifiedGrid.jsx      # Justified photo layout using justified-layout
│   ├── PhotoCard.jsx          # Single photo in grid (lazy load, hover effect)
│   ├── Lightbox.jsx           # PhotoSwipe 5 wrapper
│   ├── PasswordGate.jsx       # Full-screen password modal
│   ├── DownloadButton.jsx     # Single photo download in lightbox
│   ├── DownloadAllButton.jsx  # ZIP download with progress
│   ├── ShareButton.jsx        # Copy link to clipboard
│   ├── ExpirationBadge.jsx    # "Available until: Jun 30, 2026"
│   ├── Footer.jsx             # Minimal footer with link
│   └── Spinner.jsx            # Loading indicator
├── hooks/
│   ├── useProject.js          # GET /api/projects/{slug}
│   ├── useGallery.js          # GET /api/projects/{slug}/galleries/{gallery}
│   └── useAntiTheft.js        # Disable right-click, drag on images
├── utils/
│   └── justified.js           # Wrapper around justified-layout library
└── styles/
    └── gallery.css            # All public frontend styles
```

### 6.4. Component Specifications

#### `ProjectPage.jsx` — Main Page
```
State:
  - project: null | ProjectData
  - activeGallery: string (gallery slug)
  - isPasswordRequired: boolean
  - isLoading: boolean

Flow:
  1. Extract slug from URL path (window.location.pathname)
  2. Fetch GET /api/projects/{slug}
     - If 401 + requires_password → show PasswordGate
     - If 410 → show "This gallery has expired" message
     - If 200 → set project data, activeGallery = first gallery slug
  3. Render: HeroSection → GalleryTabs → JustifiedGrid → Footer
  4. Deep link: check URL param ?photo={id}, if present → open Lightbox on that photo
```

#### `HeroSection.jsx`
```
Props: { imageUrl, title, galleryCount }

Render:
  - Full viewport height (100vh)
  - Background image with object-fit: cover, object-position: center
  - Dark gradient overlay at bottom (linear-gradient transparent to #0a0a0a)
  - Project title: large, Outfit font, centered bottom-third
  - Subtle scroll-down indicator (animated chevron at bottom)
  - Fade-in animation on mount (opacity 0→1, 1s)
```

#### `GalleryTabs.jsx`
```
Props: { galleries: [{slug, title}], activeSlug, onTabChange }

Render:
  - Horizontal tab bar, sticky below hero on scroll
  - Active tab: accent color underline (animated slide)
  - Tab text: uppercase, letter-spacing, Inter font
  - On mobile: horizontally scrollable if too many tabs
  - Right side: ExpirationBadge + DownloadAllButton (if allowed)
```

#### `JustifiedGrid.jsx`
```
Props: { photos: [{id, thumbnailUrl, width, height}], onPhotoClick }

Logic:
  1. Calculate aspect ratios: photos.map(p => p.width / p.height)
  2. Feed to justified-layout with config:
     - containerWidth: measured from ref
     - targetRowHeight: 300 (desktop), 200 (mobile)
     - boxSpacing: 4
  3. Render absolutely-positioned <img> elements per layout geometry
  4. Intersection Observer for lazy loading (load thumbnail when in viewport)
  5. Recalculate on window resize (debounced)

Interactions:
  - Hover: slight brightness increase (filter: brightness(1.08)), scale(1.01)
  - Click: call onPhotoClick(index) → opens Lightbox
```

#### `Lightbox.jsx` (PhotoSwipe 5 wrapper)
```
Props: { photos, initialIndex, isOpen, onClose, allowDownload, projectSlug }

Behavior:
  - Initialize PhotoSwipe 5 with web-quality image URLs
  - Navigation: arrows (left/right), keyboard (← → Esc), swipe (mobile)
  - Zoom: mouse wheel / click (desktop), pinch-to-zoom (mobile)
  - Custom UI buttons in PhotoSwipe toolbar:
    - Download (if allowDownload) — triggers signed URL download
    - Share — copies deep link to clipboard
  - On slide change: update URL param ?photo={id} (replaceState, no page reload)
  - On close: remove ?photo param from URL
```

#### `PasswordGate.jsx`
```
Props: { projectSlug, onSuccess }

Render:
  - Full-screen dark overlay with centered card
  - Lock icon (SVG)
  - "This gallery is password protected"
  - Password input field
  - "Enter" button
  - On submit: POST /api/projects/{slug}/verify-password
    - If success → cookie set by server, call onSuccess()
    - If fail → shake animation on card, "Incorrect password" message
```

#### `DownloadAllButton.jsx`
```
Props: { projectSlug }

States: idle | requesting | generating | ready | downloading | error

Flow:
  1. Click → POST /api/projects/{slug}/download-all → receive {token}
  2. Poll GET /api/downloads/{token}/status every 3 seconds
     - Response: {status: 'generating', progress: 45} or {status: 'ready', size: 123456}
  3. When ready → show "Download ZIP (120 MB)" button
  4. Click → window.location = /api/downloads/{token}/file (browser downloads)

Render:
  - idle: "Download All" button with download icon
  - generating: progress bar or spinner with "Preparing ZIP..."
  - ready: "Download ZIP" button (highlighted)
```

### 6.5. Anti-Theft Protection (when `allow_download = false`)

Implemented in `useAntiTheft.js` hook:
```javascript
// Applied to all <img> elements in gallery and lightbox
- onContextMenu → e.preventDefault()        // Block right-click
- draggable="false"                          // Block drag from browser
- style: { userSelect: 'none', WebkitUserSelect: 'none', pointerEvents: 'auto' }
- CSS: img { -webkit-touch-callout: none; }  // Block iOS long-press save
```

Also:
- Download button hidden in Lightbox
- "Download All" button hidden in GalleryTabs
- API endpoints return 403 if `allow_download === false`

### 6.6. Sharing

#### Share Project
- Copy `https://gallery.nagiyev.com/{slug}` to clipboard
- Toast notification: "Link copied!"

#### Share Photo (Deep Link)
- Copy `https://gallery.nagiyev.com/{slug}?photo={photoId}` to clipboard
- When this URL is opened:
  - If password-protected → PasswordGate first, then open lightbox on that photo
  - If public → directly open lightbox on that photo

---

## 7. API Contracts

### `GET /api/projects/{slug}`

**Response 200:**
```json
{
  "project": {
    "title": "Wedding — Smith & Johnson",
    "slug": "wedding-smith-johnson",
    "hero_image_url": "/storage/web/1/1/DSC_0001.webp",
    "allow_download": true,
    "expires_at": "2026-07-15T23:59:59Z",
    "expires_at_formatted": "July 15, 2026",
    "galleries": [
      { "slug": "ceremony", "title": "Ceremony", "photo_count": 45 },
      { "slug": "reception", "title": "Reception", "photo_count": 120 }
    ]
  }
}
```

**Response 401 (password required):**
```json
{ "requires_password": true }
```

**Response 410 (expired):**
```json
{ "error": "expired", "message": "This gallery is no longer available." }
```

### `POST /api/projects/{slug}/verify-password`

**Request:** `{ "password": "secret123" }`

**Response 200:** `{ "success": true }` + Set-Cookie: `project_access_{id}=hash; Max-Age=604800; Path=/; HttpOnly; Secure`

**Response 422:** `{ "success": false, "message": "Incorrect password" }`

### `GET /api/projects/{slug}/galleries/{gallerySlug}`

**Response 200:**
```json
{
  "gallery": {
    "title": "Ceremony",
    "slug": "ceremony",
    "photos": [
      {
        "id": 42,
        "thumbnail_url": "/storage/thumbnails/1/1/DSC_0001.webp",
        "web_url": "/storage/web/1/1/DSC_0001.webp",
        "width": 6000,
        "height": 4000,
        "download_url": "/api/photos/42/download?signature=abc..."
      }
    ]
  }
}
```

> `download_url` is a **signed URL** (Laravel's `URL::signedRoute`). Included only if `allow_download === true`.

### `POST /api/projects/{slug}/download-all`

**Response 202:**
```json
{ "token": "a1b2c3d4-uuid", "message": "ZIP generation started" }
```

### `GET /api/downloads/{token}/status`

**Response 200 (generating):**
```json
{ "status": "generating" }
```

**Response 200 (ready):**
```json
{ "status": "ready", "size": 524288000, "size_formatted": "500 MB" }
```

### `GET /api/downloads/{token}/file`

**Response:** Binary file download (`Content-Type: application/zip`, `Content-Disposition: attachment`)

### `GET /api/photos/{id}/download` (signed URL)

**Response:** Binary file download (original photo, `Content-Disposition: attachment`)

---

## 8. Vite Configuration

#### [NEW] `vite.config.js`

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/admin.css',
                'resources/js/admin/app.js',
                'resources/css/gallery.css',
                'resources/js/gallery/main.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
});
```

Two entry points:
1. **Admin:** `admin.css` + `admin/app.js` → loaded in Blade admin layout via `@vite()`
2. **Gallery:** `gallery.css` + `gallery/main.jsx` → loaded in `gallery/app.blade.php` via `@vite()`

---

## 9. File Storage Layout (on Server)

```
storage/app/
├── originals/              # Original uploaded files (never modified)
│   └── {project_id}/
│       └── {gallery_id}/
│           ├── DSC_0001.jpg
│           └── DSC_0002.jpg
├── web/                    # Web-optimized versions (WebP, max 2000px)
│   └── {project_id}/
│       └── {gallery_id}/
│           ├── DSC_0001.webp
│           └── DSC_0002.webp
├── thumbnails/             # Thumbnails (WebP, 400px height)
│   └── {project_id}/
│       └── {gallery_id}/
│           ├── DSC_0001.webp
│           └── DSC_0002.webp
└── zips/                   # Temporary ZIP archives (auto-deleted after 24h)
    └── {uuid}.zip
```

**Symlink:** `php artisan storage:link` creates `public/storage → storage/app/public`

**For serving web/thumbnail images:** We'll use `storage/app/public/` (symlinked) or configure a custom disk. Web and thumbnail paths will be accessible via `/storage/web/...` and `/storage/thumbnails/...`.

**Originals are NOT publicly accessible.** They are served only through the signed download API endpoint.

---

## 10. npm Dependencies

#### [NEW/MODIFY] `package.json` (additions to Laravel's default)

```json
{
  "devDependencies": {
    "@vitejs/plugin-react": "^4.x",
    "laravel-vite-plugin": "^1.x",
    "vite": "^6.x"
  },
  "dependencies": {
    "react": "^18.x",
    "react-dom": "^18.x",
    "justified-layout": "^4.x",
    "photoswipe": "^5.x",
    "alpinejs": "^3.x",
    "sortablejs": "^1.x"
  }
}
```

---

## 11. Composer Dependencies

#### [MODIFY] `composer.json` (additions to Laravel's default)

```json
{
  "require": {
    "intervention/image": "^3.0",
    "ext-zip": "*",
    "ext-gd": "*"
  }
}
```

---

## 12. Execution Order (Phases)

| Phase | What | Depends On | Files Created/Modified |
|-------|------|-----------|----------------------|
| **1** | Initialize Laravel project structure, `.env.example`, Vite config, npm deps, composer deps | — | `composer.json`, `package.json`, `vite.config.js`, `.env.example`, `.gitignore` |
| **2** | Database migrations + models + seeders | Phase 1 | `database/migrations/*`, `app/Models/*`, `database/seeders/*` |
| **3** | Admin auth (controller, middleware, login view, session config) | Phase 2 | `Admin/AuthController`, `AdminAuth` middleware, `login.blade.php` |
| **4** | Admin CRUD (projects, galleries) + routes | Phase 3 | `Admin/ProjectController`, `Admin/GalleryController`, `routes/web.php` |
| **5** | Photo upload + image processing pipeline (job, service) | Phase 4 | `Admin/PhotoController`, `ProcessImage` job, `ImageService` |
| **6** | Complete admin UI (all Blade views, CSS, Alpine.js interactions) | Phase 4–5 | All `resources/views/admin/*`, `admin.css`, `admin/app.js` |
| **7** | Public API endpoints + middleware | Phase 2 | All `Api/*` controllers, `routes/api.php`, middleware |
| **8** | React SPA — all components, styles, lightbox, justified grid | Phase 7 | All `resources/js/gallery/*`, `gallery.css`, `gallery/app.blade.php` |
| **9** | ZIP generation, scheduled commands, anti-theft, sharing | Phase 7–8 | `GenerateZip` job, commands, download endpoints |
| **10** | CI/CD pipeline + deployment + production testing | All | `.github/workflows/deploy.yml`, documentation |

---

## User Review Required

> [!IMPORTANT]
> **Before I start Phase 1**, please:
> 1. Create a **private GitHub repository** and share the repo URL
> 2. Tell me the **SSH username** for the server
> 3. Tell me the **absolute path** on the server where the project should live (e.g., `/home/username/gallery.nagiyev.com`)
> 4. Run these checks on the server via SSH and share the output:
>    ```bash
>    php -m | grep -iE "gd|imagick|zip|mbstring|xml|curl|mysql"
>    composer --version
>    ```
> 5. I will set up Git locally in your project and create the CI/CD pipeline

> [!NOTE]
> **No PHP/Composer/Laravel locally.** All code is written locally as plain files, pushed to GitHub, and deployed to the server automatically. The only local tool used is Node.js (for building the React frontend in the CI/CD pipeline — not locally).

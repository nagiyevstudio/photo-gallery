# Task Checklist — Photography Delivery Platform

## Phase 1: Project Initialization
- [x] Initialize Laravel 11 project structure
- [x] Configure Vite for multi-entry (admin + gallery)
- [x] Configure `.env.example` and settings
- [x] Install npm dependencies (React, PhotoSwipe, etc.)
- [x] Set up `.gitignore`
- [x] Create GitHub Actions CI/CD workflow

## Phase 2: Database + Models
- [x] Create all database migrations (6 tables)
- [x] Create Eloquent models with relationships
- [x] Create AdminSeeder
- [x] Create ResetAdminPassword artisan command

## Phase 3: Admin Authentication
- [x] Admin auth controller (login/logout)
- [x] AdminAuth middleware
- [x] Login Blade view
- [x] Session configuration

## Phase 4: Admin CRUD
- [x] Project CRUD controller + form requests
- [x] Gallery management controller
- [x] Route definitions (web.php)

## Phase 5: Photo Upload + Image Pipeline
- [x] Photo upload controller (folder-based upload)
- [x] ProcessImage job (queue)
- [x] ImageService (resize, WebP, thumbnails)
- [x] Auto-create galleries from folder names
- [x] Hero image auto-assignment

## Phase 6: Admin UI (Blade + Alpine.js)
- [x] Admin layout (sidebar, dark theme)
- [x] Login page
- [x] Dashboard page
- [x] Projects list page
- [x] Project create/edit pages
- [x] Project detail page (settings + upload + gallery + stats)
- [x] Drag-and-drop upload zone
- [x] Drag-and-drop photo/gallery reordering
- [x] Admin CSS (dark theme)
- [x] Admin JS (Alpine.js + SortableJS + upload logic)

## Phase 7: Public API
- [x] Project API endpoint
- [x] Gallery API endpoint
- [x] Password verification endpoint
- [x] Download endpoints (single signed URL + ZIP)
- [x] TrackProjectView middleware
- [x] CheckProjectAccess middleware
- [x] API route definitions

## Phase 8: React SPA
- [x] App.jsx + main.jsx (entry point, routing)
- [x] API client service
- [x] ProjectPage component (unified in App.jsx)
- [x] HeroSection component
- [x] GalleryTabs component
- [x] JustifiedGrid component (justified-layout)
- [x] Lightbox component (PhotoSwipe 5)
- [x] PasswordGate component
- [x] DownloadButton + DownloadAllButton
- [x] ShareButton component
- [x] ExpirationBadge component
- [x] Footer component
- [x] useAntiTheft hook
- [x] gallery.css (dark theme, all styles)
- [x] gallery/app.blade.php (SPA shell)

## Phase 9: Advanced Backend
- [x] GenerateZip job
- [x] CleanupZips scheduled command
- [x] ExpireProjects scheduled command
- [x] Scheduler configuration

## Phase 10: Deployment
- [x] Git init + push
- [x] GitHub Actions CI/CD pipeline
- [x] Server setup documentation
- [ ] Deploy to gallery.nagiyev.com
- [ ] Cron configuration
- [ ] Production testing

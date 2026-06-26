# Deployment & Server Setup Guide

This document outlines the steps required to deploy the photography platform to your Rocky Linux server and configure all necessary settings.

---

## 1. GitHub Secrets Configuration

Before GitHub Actions can deploy the application, you must add the following Secrets to your GitHub repository (**Settings** → **Secrets and variables** → **Actions** → **Repository secrets**):

| Secret Name | Description / Value |
|-------------|---------------------|
| `SSH_HOST` | `162.210.97.28` |
| `SSH_USER` | *Your SSH username on the server (e.g., `root` or a deployment user)* |
| `SSH_KEY` | *The entire content of your SSH private key (usually located at `~/.ssh/id_rsa` or similar)* |
| `DEPLOY_PATH` | *The absolute path to the project directory on the server (e.g., `/var/www/html` or `/home/username/gallery.nagiyev.com`)* |

---

## 2. Server Prerequisites

Ensure the following are installed and configured on your server:

1. **PHP 8.4** (with the following extensions: `gd`, `zip`, `mbstring`, `xml`, `curl`, `mysql`, `pdo_mysql`).
   - Check with: `php -m | grep -iE "gd|zip|mbstring|xml|curl|mysql"`
2. **Composer** (installed globally).
   - Check with: `composer --version`
3. **MySQL 8.4** (or compatible version). Ensure database credentials match your `.env` configuration.
4. **Apache** (with `mod_rewrite` enabled).

---

## 3. Environment Configuration (`.env`)

On the server, in your `DEPLOY_PATH`, you must create a `.env` file containing the environment variables.
You can copy `.env.example` as a template and customize it:

```env
APP_NAME="Photo Gallery"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gallery.nagiyev.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```

Make sure to run `php artisan key:generate` to set the `APP_KEY` inside `.env` on the server if not already generated.

---

## 4. Apache Virtual Host & .htaccess

The document root of your web server for `gallery.nagiyev.com` must point to the **`public/`** folder of your project, i.e., `${DEPLOY_PATH}/public`.

### If you cannot change the document root (shared hosting):
If you cannot configure the document root via Apache/panel config, place this `.htaccess` file in the project's root folder (`DEPLOY_PATH`):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## 5. Cron Job for Laravel Scheduler

The application relies on the Laravel Scheduler to handle queue jobs (like processing images and generating ZIP archives), expiring galleries, and cleaning up temporary zip archives.

Access the server via SSH and edit the crontab for the web user (e.g., `crontab -e`):

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

> [!IMPORTANT]
> Replace `/path/to/your/project` with the absolute path (`DEPLOY_PATH`) of your application on the server.

---

## 6. Post-deployment Setup

Once the deployment action runs for the first time, log in via SSH and perform the following tasks:

### 1. Initialize Admin User
Run the seeder to create the initial admin user (`admin@gallery.nagiyev.com` / `changeme`):
```bash
cd /path/to/your/project
php artisan db:seed --class=AdminSeeder
```

### 2. Change Admin Password
Change the default admin credentials using our custom command:
```bash
php artisan admin:reset-password
```
Follow the interactive prompts to set your email and a secure password.

# 🚆 Online Railway Reservation System (ORRS) - Cloud Deployment

Production-ready cloud configuration for **Online Railway Reservation System (ORRS)** built with **PHP 8.2**, **Apache**, **Aiven Cloud MySQL**, and **Render** (Docker containerized).

---

## 🚀 Live Access & Default Credentials

| Portal | URL Path | Username | Default Password |
| :--- | :--- | :--- | :--- |
| **Website Homepage** | `/index.php` | - | Public Access |
| **Admin Control Panel** | `/admin/login.php` | `admin` | `admin123` |
| **Staff Portal** | `/admin/login.php` | `slou` | `slou123` |
| **1-Click DB Installer** | `/db_setup.php` | - | Web GUI |

---

## 🛠️ Architecture & Compatibility Upgrades

1. **PHP 8.x Safe Property Initialization**:
   - `classes/DBConnection.php` initializes all DB credentials inside the `__construct()` method rather than direct property expressions.
2. **Aiven Cloud MySQL SSL Enforcement**:
   - Enabled `mysqli_init()` with `MYSQLI_CLIENT_SSL` (`require_secure_transport=ON`).
   - Supports custom 5-digit ports (e.g. `26284`).
   - Auto-fallback for environment variables.
3. **Render Port 10000 & Reverse Proxy Fixes**:
   - Dynamic port binding via `${PORT:-80}` in `start.sh`.
   - Reverse proxy configuration: `UseCanonicalName Off`, `UseCanonicalPhysicalPort Off`, `ServerName localhost` to eliminate internal port leak (`:10000`) in HTTP redirects.
   - Smart `.htaccess` rules to strip internal ports from directory trailing slash redirects.
   - JS login redirect points directly to `/admin/index.php`.
4. **1-Click Database Installer (`db_setup.php`)**:
   - Visual web installer to test connection, check tables, and import `database/orrs_db.sql` with foreign key checks safely disabled during import.

---

## ⚙️ Environment Variables (Render Dashboard)

When creating your Web Service on Render, add these Environment Variables:

| Variable | Description | Example Value |
| :--- | :--- | :--- |
| `DB_SERVER` | Aiven MySQL Hostname | `mysql-xxxx-project.a.aivencloud.com` |
| `DB_PORT` | Aiven Custom Port | `26284` |
| `DB_USERNAME` | Aiven MySQL User | `avnadmin` |
| `DB_PASSWORD` | Aiven MySQL Password | `AVNS_xxxxxxxxxxxx` |
| `DB_NAME` | Aiven Database Name | `defaultdb` |
| `DB_SSL` | Force SSL Connection | `true` |
| `BASE_URL` | Live Render App URL | `https://your-service-name.onrender.com/` |

---

## 📦 Deployment Instructions (Render)

1. **Create Web Service**:
   - Go to [Render Dashboard](https://dashboard.render.com).
   - Click **New +** > **Web Service**.
   - Connect your GitHub repository.
2. **Settings**:
   - **Runtime**: `Docker`
   - **Branch**: `main`
   - **Plan**: `Free`
3. **Environment Variables**:
   - Add the database credentials as shown in the table above.
4. **Deploy & Initialize Database**:
   - Once deployment completes, visit: `https://your-app.onrender.com/db_setup.php`.
   - Click **1-Click Import Database (.SQL)**.
   - Click **Login to Admin** and log in using `admin` / `admin123`.

---

## 📁 Project Structure

```
├── .htaccess             # Apache reverse-proxy friendly rewrite rules
├── .htaccess.original    # Original backup
├── Dockerfile            # Production PHP 8.2 Apache container
├── README.md             # Deployment documentation
├── admin/                # Admin LTE dashboard & portal
├── classes/              # Core business logic & database handler
│   ├── DBConnection.php  # SSL-enabled Aiven MySQL connection
│   └── SystemSettings.php# Safe null-checked metadata handler
├── database/
│   └── orrs_db.sql       # Default database schema & seed data
├── db_setup.php          # 1-Click Aiven database installer
├── dist/                 # Frontend assets (CSS, JS, AdminLTE)
├── inc/                  # Headers, footers, and navigation
├── index.php             # Main railway reservation landing page
├── initialize.php        # Environment configuration loader
├── render.yaml           # Infrastructure-as-code Render blueprint
└── start.sh              # Container startup & reverse-proxy script
```

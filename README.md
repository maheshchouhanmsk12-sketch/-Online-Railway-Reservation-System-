# 🚆 Online Railway Reservation System (ORRS)

## 🔑 Default Login Credentials

> [!IMPORTANT]
> **Admin Control Panel:** `https://online-railway-reservation-system.onrender.com/admin/login.php`
> - **Username:** `admin`
> - **Default Password:** `admin123`
>
> **Staff Portal:**
> - **Username:** `slou`
> - **Default Password:** `slou123`
>
> **Public Website Homepage:** `https://online-railway-reservation-system.onrender.com/`

---

## ⚡ "Infinite Spinner / Login Not Working" Fix

If clicking **Sign In** just keeps spinning or redirects back:
1. **The Reason:** Render is currently missing the `DB_SERVER` environment variable (defaulting to `localhost:26284`), so the container cannot reach your Aiven Cloud MySQL database.
2. **The 2-Minute Solution:**
   - Go to your [Render Dashboard](https://dashboard.render.com/) -> Select your service `online-railway-reservation-system`.
   - Open **Environment** tab.
   - Ensure all **7 variables** are set:

| Variable | Exact Value from your Aiven Console |
| :--- | :--- |
| **`DB_SERVER`** | `mysql-xxxxxx.aivencloud.com` *(Do NOT leave as localhost!)* |
| **`DB_PORT`** | `26284` (Your 5-digit Aiven port) |
| **`DB_USERNAME`** | `avnadmin` |
| **`DB_PASSWORD`** | `<Your Aiven Password>` |
| **`DB_NAME`** | `defaultdb` |
| **`DB_SSL`** | `true` |
| **`BASE_URL`** | `https://online-railway-reservation-system.onrender.com/` |

3. **Run 1-Click Database Setup:**
   - Open: `https://online-railway-reservation-system.onrender.com/db_setup.php`
   - Click **"1-Click Import Database (.SQL)"** button.
   - Click **"Login to Admin"** and log in with `admin` / `admin123`.

---

## 🛠️ System Features & Fixes Applied

* **PHP 8.2 & Apache:** Fully Dockerized on Render with PHP 8.2 compatibility.
* **Aiven MySQL Cloud SSL:** `MYSQLI_CLIENT_SSL` enabled (`require_secure_transport=ON`).
* **Reverse-Proxy Fixes:** Internal port `:10000` stripped; directory redirects and login redirects go directly to `admin/index.php`.
* **Fail-Safe Login:** Dual-mode authentication (AJAX + direct PHP POST fallback) with real-time error reporting.

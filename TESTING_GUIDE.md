# 🧪 Reschevie — Local Testing Guide

## Prerequisites

Before you begin, make sure you have the following installed:

| Tool | Purpose | Recommended |
|------|---------|-------------|
| **XAMPP** or **WAMP** or **Laragon** | Local PHP + MySQL + Apache server | [XAMPP](https://www.apachefriends.org/) |
| **Web Browser** | Chrome, Firefox, or Edge | Chrome (with DevTools) |
| **Git** | Clone the repository | [git-scm.com](https://git-scm.com/) |

---

## Step 1 — Clone the Repository

```bash
git clone https://github.com/Jherrie27/Reschevie.git
```

Move or clone it into your local server's web directory:

| Server | Directory |
|--------|-----------|
| **XAMPP** | `C:\xampp\htdocs\Reschevie` |
| **WAMP** | `C:\wamp64\www\Reschevie` |
| **Laragon** | `C:\laragon\www\Reschevie` |

---

## Step 2 — Set Up the Database

1. **Start Apache and MySQL** from your XAMPP/WAMP/Laragon control panel.

2. **Open phpMyAdmin** at [http://localhost/phpmyadmin](http://localhost/phpmyadmin).

3. **Import the database schema:**
   - Click **"Import"** tab at the top
   - Click **"Choose File"** and select `Reschevie/database.sql`
   - Click **"Go"** to execute

   This will:
   - Create the `reschevie_db` database
   - Create all 7 tables: `users`, `admins`, `products`, `product_images`, `inquiries`, `inquiry_items`, `client_stories`, `newsletters`
   - Seed 8 sample products, 3 client stories, and 1 admin account

4. **Verify tables were created:**
   - In phpMyAdmin, select `reschevie_db` from the sidebar
   - You should see all 7 tables listed

---

## Step 3 — Configure Database Connection

Open `api/db_connect.php` and update the credentials to match your local setup:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // your MySQL username
define('DB_PASS', '');             // your MySQL password (blank for XAMPP default)
define('DB_NAME', 'reschevie_db');
```

> **Note:** XAMPP default is `root` with no password. WAMP may differ. Adjust accordingly.

---

## Step 4 — Hash the Admin Password

The default admin account in `database.sql` should now have a bcrypt-hashed password. But if you need to reset it manually:

1. Open phpMyAdmin → `reschevie_db` → `admins` table
2. Check the `admin_password` column. If it looks like plaintext, run this SQL:

```sql
UPDATE admins 
SET admin_password = '$2y$10$YourBcryptHashHere' 
WHERE admin_email = 'admin@reschevie.com';
```

To generate a bcrypt hash, create a temporary PHP file:

```php
<?php
echo password_hash('reschevieAdmin2026&!', PASSWORD_DEFAULT);
```

Visit `http://localhost/Reschevie/hash.php`, copy the output hash, paste it into the SQL above, then **delete `hash.php`**.

---

## Step 5 — Test the Database Connection

Visit: [http://localhost/Reschevie/api/db_connect.php?test](http://localhost/Reschevie/api/db_connect.php?test)

You should see a JSON response like:

```json
{
  "connection": { "ok": true, "message": "Connected to MySQL" },
  "database":   { "ok": true, "message": "Using database: reschevie_db" },
  "tables":     { "ok": true, "message": "All required tables found" }
}
```

If any check shows `"ok": false`, fix the issue before proceeding.

---

## Step 6 — Open the Website

Navigate to: **http://localhost/Reschevie/index.html**

---

## Step 7 — Test Each Feature

### 🏠 A. Homepage (`index.html`)

| Test | Expected Result |
|------|-----------------|
| Page loads without errors | Hero section, navigation, footer all render |
| Navbar scroll effect | Navbar gets `scrolled` class when you scroll past 40px |
| Featured Products section | 4 featured products load from database (with emojis) |
| Client Stories section | 3 testimonials load with author names and dates |
| Curations section | 4 origin cards (Japan, Italy, Saudi Arabia, Hong Kong) |
| Mobile hamburger menu | Click ☰ → mobile nav slides open. Click ✕ → closes |
| "Explore Collection" button | Navigates to `catalog.html` |

### 🛍️ B. Catalog (`catalog.html`)

| Test | Expected Result |
|------|-----------------|
| All products load | 8 seed products displayed in grid |
| Filter by origin | `?origin=Japan` → shows only Japanese products |
| Filter by type | `?type=ring` → shows only rings |
| Click product card | Modal opens with full product details |
| Wishlist button (♡) | Toggles to ♥, shows toast notification |
| Inquire button | If not signed in → toast "Please sign in first" → redirects to login |

### 📝 C. User Registration (`register.html`)

| Test | Expected Result |
|------|-----------------|
| Fill in all fields & submit | Success message, account created in `users` table |
| Submit with missing fields | Error: "Missing required field: ___" |
| Submit with existing email | Error: duplicate email message |
| Password stored as bcrypt hash | Check `users` table → `user_password` starts with `$2y$` |

### 🔐 D. Login (`login.html`)

| Test | Expected Result |
|------|-----------------|
| Login with registered user | Success → redirected to homepage, "Sign In" changes to user greeting |
| Login with admin credentials | `admin@reschevie.com` + password → redirected to admin dashboard |
| Login with wrong password | Error: "Invalid credentials" |
| Login with non-existent email | Error message displayed |

### 📩 E. Inquiry Page (`inquiry.html`)

| Test | Expected Result |
|------|-----------------|
| Add products to inquiry list from catalog | Cart badge updates (number increases) |
| Open inquiry page with items in cart | Products listed with details |
| Fill in inquiry form & submit | Success → inquiry saved in `inquiries` + `inquiry_items` tables |
| Submit with empty cart | Error: "At least one product must be included" |
| Remove item from inquiry list | Item removed, badge count updates |

### ✉️ F. Newsletter Subscription

| Test | Expected Result |
|------|-----------------|
| Subscribe with registered email | "Thank you for joining the Reschevie inner circle!" |
| Subscribe with unregistered email | Error: "not associated with a registered account" (FK constraint) |
| Subscribe same email twice | Error: "already subscribed" |

### 👑 G. Admin Dashboard (`admin/index.html`)

> **Login first as admin:** `admin@reschevie.com`

| Test | Expected Result |
|------|-----------------|
| Dashboard loads | Stats cards show counts (Products, Available, Inquiries, Users) |
| Recent Inquiries table | Shows latest 5 inquiries |
| Products Overview table | Shows first 6 products |

### 💍 H. Admin — Products (`admin/products.html`)

| Test | Expected Result |
|------|-----------------|
| View all products | Full list in table with Type, Origin, Karat, Price, Status |
| **Add Product** (+ button) | Modal opens → fill fields → Save → new product appears |
| **Edit Product** | Click edit → modal pre-fills → change fields → Save |
| **Delete Product** | Click delete → confirm → product removed |
| Search products | Type in search box → table filters in real-time |

### 📩 I. Admin — Inquiries (`admin/inquiries.html`)

| Test | Expected Result |
|------|-----------------|
| View all inquiries | List with Name, Email, Phone, Items, Notes, Status |
| View inquiry detail | Click → modal shows full details with product list |
| Update status | Change dropdown (pending → in-progress → completed) → saves |
| Delete inquiry | Click delete → inquiry removed |

### 👤 J. Admin — Users (`admin/users.html`)

| Test | Expected Result |
|------|-----------------|
| View registered users | Table with Name, Email, Username, Phone, Role |
| Delete a user | Click delete → user removed from `users` table |

### 💬 K. Admin — Client Stories (`admin/stories.html`)

| Test | Expected Result |
|------|-----------------|
| View all stories | Table with Title, Author, Description, Date |
| **Add Story** | Click + → fill modal → Save → story appears on homepage |
| **Edit Story** | Click edit → change fields → Save |
| **Delete Story** | Click delete → story removed |

### ✉️ L. Admin — Newsletters (`admin/newsletters.html`)

| Test | Expected Result |
|------|-----------------|
| View subscribers | Table with Email, Subscribed On |
| Unsubscribe | Click remove → subscriber removed |

---

## Step 8 — Check Browser Console for Errors

Open **DevTools** (`F12` or `Ctrl+Shift+I`) → **Console** tab.

| What to look for | Action |
|------------------|--------|
| `[RESCHEVIE] GET api/products.php failed` | Check if Apache & MySQL are running |
| `HTTP 500` errors | Check `api/db_connect.php` credentials |
| `HTTP 403` on admin endpoints | Make sure you're logged in as admin |
| `session_start()` warnings | Should be fixed already (removed duplicates) |

---

## Step 9 — Test API Endpoints Directly (Optional)

You can test the API endpoints directly in the browser or with a tool like [Postman](https://www.postman.com/):

| Endpoint | Method | Auth | Expected |
|----------|--------|------|----------|
| `api/products.php` | GET | Public | JSON array of all products |
| `api/products.php?origin=Japan` | GET | Public | Filtered products |
| `api/stories.php` | GET | Public | JSON array of stories |
| `api/inquiries.php` | GET | Admin | JSON array of inquiries (403 if not admin) |
| `api/db_connect.php?test` | GET | Public | Connection test results |

---

## Quick Reference — Test Accounts

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@reschevie.com` | `reschevieAdmin2026&!` |
| **User** | *(register one yourself)* | *(your chosen password)* |

---

## ⚠️ Known Considerations

1. **XML files** — The `xml/` directory contains XML/XSLT files for the XML requirement. These are separate from the main PHP/JS app.
2. **No image uploads** — Products use emoji placeholders (`product_emoji` column) instead of uploaded images. The `product_images` table exists in the schema but isn't actively used yet.
3. **Newsletter FK constraint** — Only registered user emails can subscribe to the newsletter. This is by design (FK from `newsletters.newsletter_email` → `users.user_email`).

---

## Need Help?

If you encounter any issues during testing, check:
1. **Apache & MySQL are running** in your XAMPP/WAMP control panel
2. **Database credentials** in `api/db_connect.php` match your setup
3. **Browser console** (F12 → Console) for JavaScript errors
4. **PHP error logs** in your XAMPP/WAMP logs directory

---

*Reschevie — Grp 5 ITS122L | Mapúa University*
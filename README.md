# Reschevie Website
## Setup Guide for ITS122L — Web Systems and Technologies 2

---

## 📁 Project Structure

```
reschevie/
├── index.html          ← Homepage
├── catalog.html        ← Product catalog with filters
├── login.html          ← User login
├── register.html       ← User registration
├── inquiry.html        ← Inquiry/quote request page
├── css/
│   └── main.css        ← All styles
├── js/
│   ├── data.js         ← Data layer (localStorage / replace with PHP API)
│   └── main.js         ← Homepage logic, product rendering
├── xml/
│   ├── products.xml    ← XML product data
│   ├── products.dtd    ← DTD validation
│   └── products.xslt   ← XSLT transform to HTML
├── admin/
│   ├── index.html      ← Admin dashboard
│   ├── products.html   ← Product CRUD
│   ├── inquiries.html  ← Inquiry management
│   ├── users.html      ← User management
│   ├── stories.html    ← Client stories CRUD
│   └── newsletters.html← Newsletter subscribers
├── database.sql        ← MySQL schema + seed data
└── php_integration_guide.php ← PHP backend code snippets
```

---

## 🚀 Quick Start (Frontend Only)

1. Open `index.html` in a browser — fully works with localStorage
2. **Demo credentials:**
   - Admin: `admin@reschevie.com` / `Admin@2026`
   - Register a new user to test customer features

---

## 🗄️ Database Integration (PHP + MySQL)

### Step 1: Set up XAMPP or WAMP
- Install XAMPP: https://www.apachefriends.org
- Start Apache and MySQL

### Step 2: Create the database
1. Open phpMyAdmin → http://localhost/phpmyadmin
2. Create a new database: `reschevie_db`
3. Import `database.sql` (Import tab → Choose file → Go)

### Step 3: Copy project files
- Copy the `reschevie/` folder to `C:/xampp/htdocs/reschevie/`

### Step 4: Create PHP API files
Using the snippets in `php_integration_guide.php`, create:
- `api/db_connect.php` — database connection
- `api/auth.php` — login, register, logout
- `api/products.php` — product CRUD
- `api/inquiries.php` — inquiry submission
- `api/stories.php` — client stories
- `api/newsletters.php` — newsletter subscriptions

### Step 5: Update js/data.js
Replace the localStorage functions with `fetch()` calls to your PHP API:
```javascript
// Example: Replace synchronous getProducts() with async version
async function getProducts() {
  const res = await fetch('api/products.php');
  return await res.json();
}
```

### Step 6: Update HTML files
Since API calls are async, update all JS to use `await`:
```javascript
const products = await getProducts();
```

---

## 🌐 Web Hosting (Deployment)

### Option A: InfinityFree (Free hosting)
1. Sign up at https://infinityfree.net
2. Create account → Create hosting → Get FTP credentials
3. Upload all files via FileZilla FTP client
4. Create MySQL database in their control panel
5. Import `database.sql`
6. Update `api/db_connect.php` with their credentials

### Option B: 000webhost (Free)
Similar process — free PHP + MySQL hosting

### Option C: Railway / Render (Modern, free tier)
- Deploy PHP + MySQL with Git integration

---

## ✅ Features Checklist

| Feature | Status |
|---------|--------|
| Login/Registration with session | ✅ |
| User browsing catalog | ✅ |
| User inquiry list | ✅ |
| User wishlist | ✅ |
| Admin dashboard | ✅ |
| Admin product CRUD | ✅ |
| Admin inquiry management | ✅ |
| Admin user management | ✅ |
| Admin client stories CRUD | ✅ |
| Admin newsletter management | ✅ |
| Database design (ERD-based) | ✅ |
| XML + DTD + XSLT | ✅ |
| Responsive design | ✅ |
| Dark luxury aesthetic | ✅ |
| Session handling | ✅ |
| Data validation | ✅ |

---

## 📋 Technologies Used
- HTML5, CSS3 (custom properties, grid, flexbox, animations)
- JavaScript (ES6+, localStorage for demo)
- XML, DTD, XSLT
- MySQL (schema provided)
- PHP (integration guide provided)

---

**Group EBK | Mapúa University | ITS122L | February 2026**

# Reschevie — Luxury Jewelry E-Commerce Website

**ITS122L — Web Systems and Technologies 2 | Group 5 | Mapúa University**

Reschevie is a full-stack luxury jewelry e-commerce website that allows customers to browse exclusive jewelry pieces, submit personalized inquiries, and manage wishlists, while providing admins with a complete CRUD dashboard.

---

## 👥 Group Info

- **Course:** ITS122L — Web Systems and Technologies 2
- **Group:** Group 5
- **School:** Mapúa University

---

## 🛠️ Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Frontend   | HTML5, CSS3, JavaScript (ES6+)    |
| Backend    | PHP 8+                            |
| Database   | MySQL 8+                          |
| Data Layer | XML, DTD, XSLT                    |
| Server     | Apache (XAMPP/WAMP)               |

---

## 📁 File Structure

```
reschevie/
├── index.html           ← Homepage (hero, featured products, stories, newsletter)
├── catalog.html         ← Product catalog with origin/type/status filters
├── login.html           ← User & admin login
├── register.html        ← New user registration
├── inquiry.html         ← Inquiry list & concierge request form
├── css/
│   └── main.css         ← All styles (dark luxury aesthetic)
├── js/
│   ├── data.js          ← Data layer — all API calls to PHP backend
│   └── main.js          ← Homepage logic, product rendering
├── xml/
│   ├── products.xml     ← XML product catalog
│   ├── products.dtd     ← DTD validation schema
│   └── products.xslt    ← XSLT transform to HTML
├── admin/
│   ├── index.html       ← Admin dashboard (stats + recent data)
│   ├── products.html    ← Product CRUD
│   ├── inquiries.html   ← Inquiry management
│   ├── users.html       ← User management
│   ├── stories.html     ← Client stories CRUD
│   └── newsletters.html ← Newsletter subscribers
├── api/
│   ├── db_connect.php   ← MySQL connection
│   ├── auth.php         ← Login, registration, logout
│   ├── products.php     ← Product CRUD API
│   ├── inquiries.php    ← Inquiry submission & management API
│   ├── stories.php      ← Client stories API
│   ├── newsletters.php  ← Newsletter subscriptions API
│   └── users.php        ← User management API (admin)
├── database.sql         ← MySQL schema + seed data
└── README.md
```

---

## 🚀 Setup Instructions

### Prerequisites
- XAMPP or WAMP (Apache + MySQL + PHP 8+)

### Step 1: Install XAMPP
- Download from https://www.apachefriends.org
- Start **Apache** and **MySQL** from the XAMPP Control Panel

### Step 2: Clone / Copy project files
- Copy the `Reschevie/` folder to `C:/xampp/htdocs/Reschevie/`

### Step 3: Create the database
1. Open phpMyAdmin → http://localhost/phpmyadmin
2. Click **New** → name it `reschevie_db` → Create
3. Select `reschevie_db` → click **Import** tab
4. Choose `database.sql` → click **Go**

### Step 4: Configure database connection
- Open `api/db_connect.php`
- Update `DB_USER` and `DB_PASS` if needed (defaults: `root` / empty password)

### Step 5: Access the site
- Frontend: http://localhost/Reschevie/
- Admin panel: http://localhost/Reschevie/admin/

### Default Admin Credentials
- **Email:** `admin@reschevie.com`
- **Password:** `reschevieAdmin2026&!`

---

## ✅ Features

| Feature                              | Status |
|--------------------------------------|--------|
| Homepage with hero & featured products | ✅ |
| Product catalog with filters          | ✅ |
| User registration & login (PHP/MySQL) | ✅ |
| Session-based authentication          | ✅ |
| Inquiry list & concierge request form | ✅ |
| Wishlist (client-side)                | ✅ |
| Newsletter subscription               | ✅ |
| Admin dashboard with live stats       | ✅ |
| Admin product CRUD                    | ✅ |
| Admin inquiry management              | ✅ |
| Admin user management                 | ✅ |
| Admin client stories CRUD             | ✅ |
| Admin newsletter management           | ✅ |
| XML product catalog + DTD + XSLT      | ✅ |
| Responsive dark luxury design         | ✅ |
| Input validation & security           | ✅ |
| bcrypt password hashing               | ✅ |

---

**Group 5 | Mapúa University | ITS122L | 2026**


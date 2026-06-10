# 📚 KDLMS — ប្រព័ន្ធបណ្ណាល័យឌីជីថលខ្មែរ

**Khmer Digital Library Management System** — ចំណេះដឹងគ្មានព្រំដែន

A full-stack PHP/MySQL digital library PWA built for Cambodian institutions, schools, and organizations.

---

## ✨ Features

- 📖 **Digital Library** — Upload, browse, and download books (PDF, DOCX, EPUB, MP4, etc.)
- 🔐 **Role-Based Access** — Superadmin / Admin / User roles
- 🇰🇭 **Khmer-First Design** — Full Khmer Unicode support, traditional colors
- 📱 **PWA** — Installable on Android/iOS, works offline
- 🔍 **AJAX Search** — Fulltext search across titles, authors, tags
- 📊 **Admin Dashboard** — Charts, stats, download logs
- 🛡️ **Security** — CSRF protection, bcrypt passwords, rate limiting

---

## 🚀 Live Demo

> **[https://kdlms.vercel.app](https://kdlms.vercel.app)** ← Live on Vercel

**Login:** `admin@kdlms.kh` / `Admin@2025`

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 + PDO |
| Database | MySQL 10.4 (MariaDB) |
| Frontend | Bootstrap 5.3 + Vanilla CSS/JS |
| Fonts | Moul, Kantumruy Pro, Hanuman, Inter |
| Charts | Chart.js 4 |
| PWA | Web App Manifest + Service Worker |
| Hosting | Vercel (PHP runtime) |
| DB Cloud | Railway / PlanetScale |

---

## 📦 Local Setup (XAMPP)

```bash
# 1. Clone
git clone https://github.com/YOUR_USERNAME/KDLMS.git
cd KDLMS

# 2. Copy to XAMPP
# Place folder in: C:/xampp/htdocs/MSDL/

# 3. Import database
# Open phpMyAdmin → Create database 'kdlms' → Import sql/kdlms.sql
# OR run: php reimport.php (if available)

# 4. Open browser
http://localhost/MSDL/
```

---

## ☁️ Vercel Deployment

### Step 1: Get a free MySQL database

Go to [railway.app](https://railway.app) → New Project → MySQL  
Copy your `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

### Step 2: Deploy to Vercel

```bash
# Install Vercel CLI
npm install -g vercel

# Login and deploy
vercel login
vercel --prod
```

### Step 3: Set Environment Variables in Vercel

In your Vercel dashboard → Project → Settings → Environment Variables:

| Key | Value |
|---|---|
| `DB_HOST` | your-railway-host |
| `DB_NAME` | railway |
| `DB_USER` | root |
| `DB_PASS` | your-password |
| `APP_BASE_URL` | https://your-app.vercel.app |

### Step 4: Import database to Railway

Run `php reimport.php` pointing to Railway DB, or use the Railway MySQL console.

---

## 📁 File Structure

```
MSDL/
├── admin/           # Admin panel pages
├── assets/          # CSS, JS, images, icons
│   └── img/icons/   # PWA icons (192px, 512px)
├── includes/        # PHP core: db, auth, functions, headers
├── sql/             # Database schema + seed data
├── uploads/         # Uploaded files (excluded from git)
├── index.php        # Public homepage
├── browse.php       # Book browser
├── login.php        # Authentication
├── download.php     # Secure file download
├── manifest.json    # PWA manifest
├── sw.js            # Service Worker
├── offline.html     # Offline fallback
└── vercel.json      # Vercel deployment config
```

---

## 👤 Creator

**អេង ចាន់ធឿន (Eng Chanthoeun - Vthe)**  
KDLMS v1.0 · 2025

---

## 📄 License

MIT License — Free for educational and institutional use.

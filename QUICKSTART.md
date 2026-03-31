# 🚀 Quick Start Guide - Aetheria Library

Panduan cepat untuk menjalankan aplikasi Aetheria Library di lokal atau production.

---

## ⚡ Quick Start (5 Menit)

### 1️⃣ Setup Lokal dengan XAMPP

**Prerequisites:**
- XAMPP installed (PHP 7.4+, MySQL 5.7+)
- Git installed

**Steps:**

```bash
# 1. Clone repository
cd C:\xampp\htdocs
git clone https://github.com/yourusername/aetheria-library.git f1q
cd f1q

# 2. Start XAMPP (Apache + MySQL)
# Buka XAMPP Control Panel → Start Apache & MySQL

# 3. Import Database
# Buka http://localhost/phpmyadmin
# - Create database: perpus_30
# - Import file: perpus_30.sql

# 4. Akses aplikasi
# Buka browser: http://localhost/f1q
```

**Default Login Credentials:**
```
Admin:
  Username: admin
  Password: admin

Petugas:
  Username: petugas
  Password: petugas

Anggota:
  Username: siti
  Password: siti
```

---

### 2️⃣ Deploy ke Shared Hosting (Niagahoster)

**Prerequisites:**
- FTP/SFTP credentials
- cPanel access

**Steps:**

```bash
# 1. Download project atau push ke Git
# 2. Upload ke hosting via FTP ke folder public_html
# 3. Buka cPanel → phpMyAdmin
#    - Create database: perpus_30
#    - Import perpus_30.sql
# 4. Update config/database.php dengan credentials hosting
# 5. Set permissions via FTP:
#    - uploads/ → 755
#    - config/ → 755
# 6. Access: https://yourdomain.com/f1q
```

---

### 3️⃣ Deploy ke VPS (DigitalOcean/Linode/AWS)

**Prerequisites:**
- SSH access ke VPS
- Root atau sudo access

**Steps:**

```bash
# 1. SSH ke server
ssh root@your_server_ip

# 2. Install dependencies
apt update && apt upgrade -y
apt install nginx php-fpm php-mysql php-curl php-gd mysql-server git -y

# 3. Clone project
cd /var/www
git clone https://github.com/yourusername/aetheria-library.git aetheria
cd aetheria

# 4. Setup database
mysql -u root -p << EOF
CREATE DATABASE perpus_30;
CREATE USER 'perpus_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON perpus_30.* TO 'perpus_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import SQL
mysql -u perpus_user -p perpus_30 < perpus_30.sql

# 5. Configure Nginx & PHP-FPM
# (See DEPLOYMENT.md for detailed config)

# 6. Set permissions
chown -R www-data:www-data /var/www/aetheria
chmod 755 /var/www/aetheria

# 7. Get SSL (Let's Encrypt)
certbot --nginx -d yourdomain.com

# 8. Access: https://yourdomain.com
```

---

## 🗄️ Database Setup

### Option A: Via phpMyAdmin

1. Buka http://localhost/phpmyadmin (lokal) atau cPanel phpMyAdmin
2. Buat database baru bernama `perpus_30`
3. Tab "Import" → Select file `perpus_30.sql`
4. Klik "Go"

### Option B: Via Command Line

```bash
# Create database & user
mysql -u root -p << EOF
CREATE DATABASE perpus_30 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'perpus_user'@'localhost' IDENTIFIED BY 'secure_password123';
GRANT ALL PRIVILEGES ON perpus_30.* TO 'perpus_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import SQL dump
mysql -u root -p perpus_30 < perpus_30.sql

# Verify import
mysql -u root -p -e "USE perpus_30; SHOW TABLES;"
```

---

## 📝 Configuration

### Development (Lokal)

File `config/database.php` sudah default untuk lokal:
```php
DB_HOST = localhost
DB_USER = root
DB_PASS = (kosong)
DB_NAME = perpus_30
```

### Production (Server)

1. Copy `.env.example` → `.env.local`:
   ```bash
   cp .env.example .env.local
   ```

2. Edit `.env.local` dengan credentials server:
   ```
   DB_HOST=localhost
   DB_USER=perpus_user
   DB_PASS=your_strong_password
   DB_NAME=perpus_30
   APP_ENV=production
   APP_DEBUG=false
   ```

3. Ensure `.env.local` di `.gitignore` (jangan push ke Git!)

4. Update permissions:
   ```bash
   chmod 600 .env.local
   chown www-data:www-data .env.local
   ```

---

## 🔍 Verify Installation

Akses halaman check-up:

### Lokal
```
http://localhost/f1q/
```

### Production
```
https://yourdomain.com/
```

### Checklist:
- [ ] Landing page muncul dengan list buku
- [ ] Login page berfungsi
- [ ] Admin bisa login
- [ ] Petugas bisa login
- [ ] Anggota bisa login
- [ ] Database records muncul

---

## 🆘 Troubleshooting

### Error: "Koneksi database gagal"

```bash
# Check MySQL is running
# Windows (XAMPP): Start MySQL dari Control Panel
# Linux: sudo systemctl status mysql

# Test connection
mysql -h localhost -u root -p

# Check config/database.php credentials
```

### Error: "Permission denied uploads"

```bash
# Local (Windows): Just works, no need to change
# Linux/Mac:
chmod 755 uploads/
chmod 755 uploads/cover/
chmod 755 uploads/foto_anggota/
```

### Error: "Page not found" (404)

- Lokal: Ensure `localhost/f1q/` → bukan `localhost/f1q`
- Server: Ensure .htaccess di root atau configure Nginx properly

### Error: "White page"

Enable debug mode di `config/database.php`:
```php
define('APP_DEBUG', true);
```

Check `error_log()` atau browser console untuk error details.

---

## 📱 Features Checklist

**Admin Dashboard:**
- [ ] View stats (books, members, loans)
- [ ] Quick approve/reject loans
- [ ] View transactions
- [ ] Manage fines

**Petugas Dashboard:**
- [ ] CRUD books
- [ ] CRUD categories
- [ ] View transactions
- [ ] Manage fines
- [ ] View reports

**Member Portal:**
- [ ] View catalog
- [ ] Search books
- [ ] Request loan
- [ ] View loan history
- [ ] Return books
- [ ] Write reviews
- [ ] View fines

---

## 🔐 Security Checklist

Before going live:

- [ ] Change default admin/petugas/anggota passwords
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set `APP_DEBUG=false` in production
- [ ] Backup database before deploy
- [ ] Configure firewall rules
- [ ] Setup automated backups
- [ ] Review `.gitignore` (no secrets in repo)
- [ ] Test all input validation & sanitization
- [ ] Test file upload restrictions

---

## 📚 Next Steps

1. **Customize:**
   - Update school/library name in header/footer
   - Upload custom logo/branding
   - Customize CSS colors/theme

2. **Configure:**
   - Setup email sending (SMTP)
   - Configure fine calculation
   - Setup member registration approval

3. **Launch:**
   - Create admin accounts for staff
   - Bulk import members from CSV
   - Add book catalog (excel import if available)
   - Setup member onboarding

---

## 📞 Support & Documentation

- **Detailed Deployment:** See [DEPLOYMENT.md](DEPLOYMENT.md)
- **API Documentation:** See [README.md](README.md)
- **Project Structure:** See [README.md#Project-Structure](README.md#-project-structure)

---

## 🎉 You're Ready!

Aplikasi Aetheria Library siap digunakan. Selamat menggunakan sistem perpustakaan digital yang modern! 📚✨

**Last Updated:** 31 Mar 2026  
**Version:** 1.0

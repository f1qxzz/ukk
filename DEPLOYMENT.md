# 🚀 Deployment Guide - Aetheria Library

Panduan lengkap untuk deploy aplikasi Aetheria Library ke berbagai platform hosting.

---

## 📋 Daftar Persyaratan

Sebelum deploy, pastikan:
- [ ] PHP 7.4 atau lebih baru
- [ ] MySQL 5.7 atau MariaDB 10.2+
- [ ] Domain atau subdomain
- [ ] FTP/SFTP akses atau Git akses
- [ ] Database dump file (`perpus_30.sql`)

---

## 🏠 Option 1: Shared Hosting (Niagahoster, Hostinger, cPanel)

### A. Upload Files via FTP/SFTP

1. **Download & Extract Project**
   ```bash
   # Download dari Git (jika available)
   git clone <repo-url>
   
   # Atau download ZIP dari repository
   ```

2. **Upload via FTP Client (FileZilla)**
   - Buka FileZilla → File > Site Manager
   - Masukkan:
     - Host: `ftp.yourdomin.com`
     - Username: `your_ftp_user`
     - Password: `your_ftp_password`
   - Pilih folder `public_html` atau `www`
   - Upload semua file (kecuali `.git` folder)

### B. Import Database via cPanel

1. **Buka cPanel → phpMyAdmin**
2. **Buat Database Baru:**
   - Database Name: `perpus_30` (atau nama lain)
   - Buat User MySQL dengan password kuat
   - Add user ke database dengan privilege `All`

3. **Import SQL Dump:**
   - Buka phpMyAdmin → Pilih database `perpus_30`
   - Tab "Import" → Pilih `perpus_30.sql`
   - Klik Import

### C. Konfigurasi Database

Buka file `config/database.php` dan update:

```php
<?php
// PRODUCTION CONFIG - JANGAN hardcode password!
// Gunakan environment variables atau include dari config file terpisah

define('DB_HOST',   $_ENV['DB_HOST']   ?? 'localhost');
define('DB_USER',   $_ENV['DB_USER']   ?? 'root');
define('DB_PASS',   $_ENV['DB_PASS']   ?? '');
define('DB_NAME',   $_ENV['DB_NAME']   ?? 'perpus_30');
```

Atau buat file `.env.local` (jangan push ke Git):
```
DB_HOST=localhost
DB_USER=perpus_user
DB_PASS=strong_password_here
DB_NAME=perpus_30
```

Load `.env.local` di awal `config/database.php`:
```php
if (file_exists(__DIR__ . '/../.env.local')) {
    $env = parse_ini_file(__DIR__ . '/../.env.local');
    foreach ($env as $k => $v) $_ENV[$k] = $v;
}
```

### D. Test Akses

- Akses `https://yourdomain.com/` → Harus muncul landing page
- Login admin: username `admin`, password `admin` (atau sesuai data di SQL)
- Test semua role: Admin, Petugas, Anggota

---

## 🖥️ Option 2: VPS/Dedicated Server (DigitalOcean, Linode, AWS)

### A. Setup Server

1. **Update System**
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Install LEMP Stack**
   ```bash
   # Install Nginx
   sudo apt install nginx -y
   sudo systemctl start nginx

   # Install PHP
   sudo apt install php-fpm php-mysql php-curl php-gd php-zip -y
   
   # Install MySQL
   sudo apt install mysql-server -y
   sudo mysql_secure_installation
   ```

3. **Setup Nginx Virtual Host**
   ```bash
   sudo nano /etc/nginx/sites-available/aetheria
   ```

   Paste config:
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com www.yourdomain.com;
       root /var/www/aetheria;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.ht {
           deny all;
       }
   }
   ```

   Enable & test:
   ```bash
   sudo ln -s /etc/nginx/sites-available/aetheria /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   ```

### B. Deploy Project

1. **Clone Repository**
   ```bash
   cd /var/www
   sudo git clone https://github.com/yourusername/aetheria-library.git aetheria
   cd aetheria
   ```

2. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/aetheria
   sudo chmod -R 755 /var/www/aetheria
   sudo chmod -R 775 /var/www/aetheria/uploads
   ```

3. **Load .env.local atau Update Config**
   ```bash
   cp config/.env.example /var/www/aetheria/.env.local
   # Edit .env.local dengan database credentials
   ```

### C. Setup Database

```bash
# Login MySQL
mysql -u root -p

# Create database & user
CREATE DATABASE perpus_30;
CREATE USER 'perpus_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON perpus_30.* TO 'perpus_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import SQL dump
mysql -u perpus_user -p perpus_30 < perpus_30.sql
```

### D. SSL Certificate (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renew
sudo systemctl enable certbot.timer
```

---

## 🐳 Option 3: Docker (Untuk Development & Production)

1. **Buat Dockerfile**
   ```dockerfile
   FROM php:8.0-fpm
   RUN apt-get update && apt-get install -y \
       mysql-client \
       libfreetype6-dev \
       libjpeg62-turbo-dev \
       libpng-dev \
       && docker-php-ext-install mysqli gd
   WORKDIR /app
   EXPOSE 9000
   ```

2. **Buat docker-compose.yml**
   ```yaml
   version: '3.8'
   services:
     web:
       image: nginx:latest
       ports:
         - "80:80"
       volumes:
         - ./:/app
         - ./nginx.conf:/etc/nginx/nginx.conf
     php:
       build: .
       volumes:
         - ./:/app
     db:
       image: mysql:8.0
       environment:
         MYSQL_DATABASE: perpus_30
         MYSQL_USER: perpus_user
         MYSQL_PASSWORD: strong_password
         MYSQL_ROOT_PASSWORD: root_password
       volumes:
         - ./perpus_30.sql:/docker-entrypoint-initdb.d/perpus_30.sql
   ```

3. **Run Docker**
   ```bash
   docker-compose up -d
   docker-compose exec db mysql -uroot -proot_password perpus_30 < perpus_30.sql
   ```

---

## 📦 Checklist Pre-Deployment

- [ ] Database sudah diimport dengan SQL dump
- [ ] `config/database.php` sudah dikonfigurasi dengan credentials produksi
- [ ] File `.env.local` atau `.env.production` dibuat dan `.gitignore`
- [ ] `uploads/` folder ada dan writable (chmod 755/775)
- [ ] SSL certificate aktif (jika HTTPS)
- [ ] Email sending configured (jika ada fitur email)
- [ ] Session path writable (`/tmp` atau custom)
- [ ] Error logging configured (`$_ENV['DEBUG']` off di produksi)
- [ ] Backup database dibuat sebelum deploy
- [ ] Test semua role: Admin, Petugas, Anggota di produksi

---

## 🔐 Security Best Practices

1. **Database Credentials**
   - Jangan hardcode di code
   - Gunakan `.env.local` yang di-gitignore
   - Rotate password secara berkala

2. **File Uploads**
   ```php
   // Validate file type & size
   $allowed = ['jpg', 'jpeg', 'png', 'gif'];
   if (!in_array(strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)), $allowed)) {
       die('File type tidak diizinkan');
   }
   ```

3. **SQL Injection Prevention**
   - Selalu gunakan prepared statement (mysqli_prepare)
   - Jangan concatenate SQL strings

4. **XSS Prevention**
   - Gunakan `htmlspecialchars()` saat output data
   - Sanitize input dengan filter_var() atau custom validators

5. **Authentication**
   - Hash password dengan `password_hash()` & `password_verify()`
   - Logout ketika browser ditutup
   - Session timeout 30 menit

---

## 📞 Troubleshooting

### Error: "Koneksi database gagal"
- Cek hostname, username, password di `config/database.php`
- Pastikan MySQL service running: `sudo systemctl status mysql`
- Test koneksi: `mysql -h hostname -u user -p`

### Error: "Permission denied" pada uploads
```bash
sudo chown -R www-data:www-data /path/to/uploads
sudo chmod 775 /path/to/uploads
```

### Error: "Headers already sent"
- Ada output sebelum `header()` call
- Cek `BOM` di file PHP (gunakan UTF-8 without BOM)
- Cek spasi/newline di akhir file

### Error: "Unable to connect to MySQL"
```php
// Debug connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Exception $e) {
    die($e->getMessage());
}
```

---

## 📊 Database Credentials (Default)

| Role | Username | Password | Database |
|------|----------|----------|----------|
| Admin | `admin` | `admin` | `perpus_30` |
| Petugas | `petugas` | `petugas` | `perpus_30` |
| Anggota | `siti` | `siti` | `perpus_30` |

**⚠️ UBAH PASSWORD SETELAH DEPLOY KE PRODUKSI!**

Ubah via:
```bash
mysql> UPDATE pengguna SET password=PASSWORD('newpassword') WHERE username='admin';
mysql> UPDATE anggota SET password=PASSWORD('newpassword') WHERE username='siti';
```

---

## 🔄 Automated Backup

### Linux Cron Job
```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * mysqldump -u perpus_user -ppassword perpus_30 > /home/user/backups/perpus_$(date +\%Y\%m\%d).sql

# Backup juga files
0 3 * * * tar -czf /home/user/backups/aetheria_$(date +\%Y\%m\%d).tar.gz /var/www/aetheria
```

### cPanel Backup
- cPanel → Backup → Setup Backup Account
- Configure remote backup ke cloud storage

---

## 📚 Helpful Links

- [PHP Official](https://www.php.net/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Let's Encrypt SSL](https://letsencrypt.org/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [Docker Documentation](https://docs.docker.com/)

---

## ✅ Go Live Checklist

Sebelum announcement ke publik:
- [ ] Semua page berfungsi di semua browser
- [ ] Mobile responsive tested
- [ ] Database backup tersimpan aman
- [ ] Admin credentials sudah diubah
- [ ] SSL/HTTPS aktif dan valid
- [ ] Monitoring setup (error logs, uptime)
- [ ] Contact form / support email working
- [ ] Privacy Policy & Terms of Service page
- [ ] Footer dengan copyright & contact info

---

**Last Updated:** 31 Mar 2026  
**Version:** 1.0  
**Status:** Production Ready

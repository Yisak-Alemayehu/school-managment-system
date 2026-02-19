# Urjiberi School ERP — Deployment Guide

## System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP       | 8.2+    | 8.3         |
| MySQL     | 8.0+    | 8.0.33+     |
| Apache    | 2.4+    | 2.4.57+     |
| RAM       | 512 MB  | 2 GB        |
| Disk      | 1 GB    | 10 GB       |

### Required PHP Extensions
- `pdo_mysql` — Database connectivity
- `mbstring` — Multi-byte string support
- `gd` — Image processing (icons, photos)
- `openssl` — Encryption / HTTPS
- `json` — JSON encoding
- `session` — Session management
- `fileinfo` — File upload validation
- `curl` — Payment gateway API calls

---

## Quick Start (Development)

### 1. Clone/Copy Files
```bash
# Copy project to web-accessible directory
cp -r "urjiberischool system 2026" /var/www/urjiberi
```

### 2. Create Database
```bash
mysql -u root -p
```
```sql
CREATE DATABASE urjiberi_school CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'urjiberi'@'localhost' IDENTIFIED BY 'YourStrongPassword';
GRANT ALL PRIVILEGES ON urjiberi_school.* TO 'urjiberi'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Run Migrations
```bash
cd /var/www/urjiberi
mysql -u urjiberi -p urjiberi_school < sql/001_core_auth.sql
mysql -u urjiberi -p urjiberi_school < sql/002_academics.sql
mysql -u urjiberi -p urjiberi_school < sql/003_students.sql
mysql -u urjiberi -p urjiberi_school < sql/004_assessment.sql
mysql -u urjiberi -p urjiberi_school < sql/005_finance.sql
mysql -u urjiberi -p urjiberi_school < sql/006_payment_gateway.sql
mysql -u urjiberi -p urjiberi_school < sql/007_communication.sql
mysql -u urjiberi -p urjiberi_school < sql/008_system.sql
mysql -u urjiberi -p urjiberi_school < sql/009_seed_data.sql
```

### 4. Configure Application
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'urjiberi_school');
define('DB_USER', 'urjiberi');
define('DB_PASS', 'YourStrongPassword');
```

Edit `config/app.php`:
```php
define('APP_URL', 'http://localhost/urjiberi');
define('APP_ENV', 'development');
define('APP_DEBUG', true);
```

### 5. Set Permissions
```bash
chmod -R 755 storage/
chmod -R 640 config/
```

### 6. Point Web Server
Configure Apache to serve `public/` as the document root.

### 7. Login
- URL: `http://your-domain/`
- Username: `superadmin`
- Password: `Admin@123`
- **Change this password immediately!**

---

## Production Deployment (Apache)

### Apache Virtual Host
```apache
<VirtualHost *:443>
    ServerName erp.urjiberi.edu.et
    DocumentRoot /var/www/urjiberi/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/erp.urjiberi.edu.et/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/erp.urjiberi.edu.et/privkey.pem
    
    <Directory /var/www/urjiberi/public>
        AllowOverride All
        Require all granted
        Options -Indexes -ExecCGI
    </Directory>
    
    # Block access to non-public directories
    <DirectoryMatch "^/var/www/urjiberi/(config|core|modules|templates|sql|docs|storage)">
        Require all denied
    </DirectoryMatch>
    
    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    ErrorLog ${APACHE_LOG_DIR}/urjiberi_error.log
    CustomLog ${APACHE_LOG_DIR}/urjiberi_access.log combined
</VirtualHost>

# HTTP to HTTPS redirect
<VirtualHost *:80>
    ServerName erp.urjiberi.edu.et
    Redirect permanent / https://erp.urjiberi.edu.et/
</VirtualHost>
```

### Enable Required Apache Modules
```bash
sudo a2enmod rewrite ssl headers
sudo systemctl restart apache2
```

---

## Production Deployment (Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name erp.urjiberi.edu.et;
    root /var/www/urjiberi/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/erp.urjiberi.edu.et/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/erp.urjiberi.edu.et/privkey.pem;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Block access to sensitive directories
    location ~ ^/(config|core|modules|templates|sql|docs|storage)/ {
        deny all;
        return 404;
    }

    # Block dotfiles
    location ~ /\. {
        deny all;
        return 404;
    }

    # Static files
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # PWA manifest and service worker
    location /manifest.webmanifest {
        types { application/manifest+json webmanifest; }
        expires 1d;
    }
    
    location /service-worker.js {
        expires off;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    # PHP processing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

server {
    listen 80;
    server_name erp.urjiberi.edu.et;
    return 301 https://$server_name$request_uri;
}
```

---

## XAMPP / WAMP (Windows Development)

1. Copy project folder to `C:\xampp\htdocs\urjiberi`
2. Import SQL files via phpMyAdmin or command line
3. Edit `config/database.php` with your local credentials
4. Set `APP_URL` to `http://localhost/urjiberi`
5. Access `http://localhost/urjiberi/public/`

Optional: Add Apache alias in `httpd.conf`:
```apache
Alias /school "C:/xampp/htdocs/urjiberi/public"
<Directory "C:/xampp/htdocs/urjiberi/public">
    AllowOverride All
    Require all granted
</Directory>
```

---

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment (development/production) | development |
| `APP_DEBUG` | Show detailed errors | true |
| `APP_URL` | Base URL of the application | http://localhost |
| `DB_HOST` | MySQL host | localhost |
| `DB_PORT` | MySQL port | 3306 |
| `DB_NAME` | Database name | urjiberi_school |
| `DB_USER` | Database user | root |
| `DB_PASS` | Database password | (empty) |

---

## Backup & Restore

### Backup
Use the built-in backup tool: **Settings > Database Backup > Create Backup**

Or via command line:
```bash
mysqldump --single-transaction --routines --triggers urjiberi_school > backup_$(date +%Y%m%d).sql
```

### Restore
```bash
mysql -u urjiberi -p urjiberi_school < backup_20250101.sql
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Blank page | Check PHP error log, ensure `display_errors = On` in dev |
| 500 Error | Check Apache error log, verify `.htaccess` / `mod_rewrite` |
| Database error | Verify credentials in `config/database.php` |
| Login fails | Run seed migration `009_seed_data.sql` again |
| Upload fails | Check `storage/` permissions (755) and PHP `upload_max_filesize` |
| PWA not installing | Must be served over HTTPS with valid manifest |
| Icons missing | Ensure PHP GD extension is enabled for dynamic icons |
| Payments fail | Check API keys in `config/payment.php`, verify HTTPS |

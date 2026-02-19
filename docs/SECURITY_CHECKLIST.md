# Urjiberi School ERP — Security Hardening Checklist

## Pre-Deployment Security Checklist

### 1. Environment Configuration
- [ ] Set `APP_ENV` to `production` in `config/app.php`
- [ ] Set `APP_DEBUG` to `false`
- [ ] Generate a new `APP_KEY` using `bin2hex(random_bytes(32))`
- [ ] Set strong MySQL password (not root, not empty)
- [ ] Set `SESSION_SECURE` to `true` (requires HTTPS)
- [ ] Update `APP_URL` to production domain (with https://)

### 2. HTTPS & Headers
- [ ] Enable HTTPS (Let's Encrypt / Cloudflare)
- [ ] Verify HSTS header is sent (`Strict-Transport-Security`)
- [ ] Check `X-Content-Type-Options: nosniff` header
- [ ] Check `X-Frame-Options: SAMEORIGIN` header
- [ ] Check `X-XSS-Protection: 1; mode=block` header
- [ ] Check `Referrer-Policy: strict-origin-when-cross-origin` header

### 3. File System
- [ ] Set `storage/` directory to `0755` (writable by web server only)
- [ ] Set `config/` files to `0640` (readable by web server, not world)
- [ ] Ensure web root points to `public/` only
- [ ] Verify `.htaccess` blocks access to parent directories
- [ ] Remove or protect `sql/` migration files from web access
- [ ] Block all dotfiles access (`.env`, `.git`, etc.)

### 4. Database
- [ ] Use dedicated MySQL user (not root)
- [ ] Grant only necessary privileges: SELECT, INSERT, UPDATE, DELETE
- [ ] Enable MySQL slow query log for monitoring
- [ ] Set `innodb_file_per_table = ON`
- [ ] Regular automated backups (use Settings > Backup)

### 5. PHP Configuration (php.ini)
```ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = Strict
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
memory_limit = 256M
open_basedir = /var/www/urjiberi:/tmp
disable_functions = exec,passthru,shell_exec,system,proc_open,popen (if not using mysqldump backup)
```

### 6. Application Security
- [ ] Change default super admin password immediately
- [ ] Enable brute force protection (already built-in: 5 attempts / 15 min lockout)
- [ ] Review all user accounts and remove test accounts
- [ ] Set up regular audit log reviews
- [ ] Test CSRF protection on all forms
- [ ] Verify all SQL queries use prepared statements (✅ built-in)
- [ ] Verify all output uses `e()` escaping (✅ built-in)
- [ ] Check `upload_max_filesize` matches your needs

### 7. Payment Gateway Security
- [ ] Use real Telebirr/Chapa API credentials (not test keys)
- [ ] Verify webhook signatures
- [ ] Use HTTPS for all payment callbacks
- [ ] Log all payment transactions for reconciliation
- [ ] Test payment flow end-to-end before going live

### 8. Monitoring & Maintenance
- [ ] Set up error log monitoring
- [ ] Schedule regular database backups (daily)
- [ ] Schedule regular file backups (weekly)
- [ ] Monitor disk space
- [ ] Set up uptime monitoring
- [ ] Review audit logs weekly
- [ ] Keep PHP and MySQL updated

### 9. Rate Limiting
- Built-in session-based rate limiting for login (5 attempts)
- Consider adding server-level rate limiting (Nginx `limit_req` / Apache `mod_evasive`)

### 10. Content Security Policy (Optional)
Add to `.htaccess` or server config for production:
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data:; font-src 'self';
```

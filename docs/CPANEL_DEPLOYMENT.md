# Urjiberi School ERP — cPanel Deployment Guide

## Prerequisites
- cPanel shared hosting with **PHP 8.1+** and **MySQL 5.7+**
- Access to cPanel File Manager or FTP
- Access to phpMyAdmin

---

## Step 1: Create Database in cPanel

1. Open **cPanel → MySQL Databases**
2. Create a new database (e.g. `urjiberi_school`)  
   → cPanel will prefix it: `yourusername_urjiberi_school`
3. Create a new database user (e.g. `urjiberi_user`)  
   → cPanel will prefix it: `yourusername_urjiberi_user`
4. **Add the user to the database** with **ALL PRIVILEGES**
5. Note down:
   - Database name: `yourusername_urjiberi_school`
   - Username: `yourusername_urjiberi_user`
   - Password: (whatever you set)

---

## Step 2: Import the SQL Schema & Seed Data

1. Open **cPanel → phpMyAdmin**
2. Select your new database
3. Click **Import** tab
4. Import files **in this order**:
   - `sql/schema.sql` (creates all tables)
   - `sql/seed.sql` (populates sample data)
5. Wait for each import to complete before starting the next

> **Tip:** If the seed.sql is too large for phpMyAdmin, use the cPanel **Terminal** and run:
> ```
> mysql -u yourusername_urjiberi_user -p yourusername_urjiberi_school < sql/schema.sql
> mysql -u yourusername_urjiberi_user -p yourusername_urjiberi_school < sql/seed.sql
> ```

---

## Step 3: Upload Files

### Option A — App at Domain Root (recommended)
Upload the **entire project folder contents** into `public_html/`:

```
public_html/
├── .htaccess          ← root htaccess (redirects to public/)
├── .env               ← your environment config
├── config/
├── core/
├── modules/
├── templates/
├── public/
│   ├── .htaccess      ← routes all requests to index.php
│   ├── index.php      ← front controller
│   └── assets/
├── sql/
├── logs/
├── uploads/
└── storage/
```

### Option B — App in a Subdirectory
Upload into `public_html/school/` (or any folder name):
- Your site will be: `https://yourdomain.com/school/`
- Set `APP_URL=https://yourdomain.com/school` in `.env`

---

## Step 4: Create the `.env` File

1. Copy `.env.example` to `.env` in the project root
2. Edit `.env` with your values:

```env
APP_ENV=production
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=yourusername_urjiberi_school
DB_USER=yourusername_urjiberi_user
DB_PASS=your_database_password
```

> **Important:** Replace `yourusername` with your actual cPanel username.  
> **Important:** `DB_HOST` on cPanel is almost always `localhost`.

---

## Step 5: Set Directory Permissions

In cPanel **File Manager** or via SSH/Terminal:

```bash
chmod 755 public_html/           # (or your project root)
chmod -R 755 logs/
chmod -R 755 uploads/
chmod -R 755 storage/
```

Make sure these directories are **writable** by the web server:
- `logs/` — PHP error logs
- `uploads/` — Student photos and files
- `storage/uploads/` — General uploads
- `storage/backups/` — Database backups

---

## Step 6: Set PHP Version

1. Open **cPanel → MultiPHP Manager** (or **Select PHP Version**)
2. Select your domain
3. Set PHP version to **8.1** or higher
4. Make sure these PHP extensions are enabled:
   - `pdo_mysql`
   - `mbstring`
   - `fileinfo`
   - `json`
   - `openssl`
   - `curl`

---

## Step 7: Test

1. Visit `https://yourdomain.com/` in your browser
2. You should see the login page
3. Login with:
   - **Username:** `superadmin`
   - **Password:** `Admin@123`

---

## Troubleshooting

### Blank White Page / 500 Error
- Check `logs/php_errors.log` in the project directory
- Check cPanel **Error Logs** (Metrics → Errors)
- Temporarily set `APP_ENV=development` in `.env` to see error details
- Make sure PHP 8.1+ is selected

### "Page Not Found" on All Routes
- Confirm `mod_rewrite` is enabled (it usually is on cPanel)
- Verify both `.htaccess` files were uploaded (root AND `public/`)
- In cPanel → **Apache Handlers**, check `.htaccess` files are allowed

### Database Connection Error
- Verify the DB credentials in `.env` match what you created in cPanel
- Remember cPanel prefixes both the DB name and username with your cPanel username
- Make sure you added the user to the database with ALL privileges
- `DB_HOST` should be `localhost` (not `127.0.0.1` on some cPanel setups)

### Session / Login Issues
- Make sure `session` PHP extension is enabled
- If on HTTP (not HTTPS), sessions are auto-configured to work without `Secure` flag
- Clear browser cookies and try again

### CSS/JS Not Loading
- Check browser console for 404 errors on assets
- Make sure `APP_URL` in `.env` matches your actual domain (no trailing slash)
- Verify `public/assets/` directory was uploaded completely

### File Upload Errors
- Check that `uploads/` and `storage/uploads/` directories exist and are writable (chmod 755)
- Check PHP `upload_max_filesize` in cPanel → **MultiPHP INI Editor**

---

## Security Reminders

- **Never** leave `APP_ENV=development` on a live server
- Make sure `.env` is not accessible from the browser (the `.htaccess` blocks it)
- Enable HTTPS via cPanel → **SSL/TLS** or **Let's Encrypt**
- Once HTTPS is working, uncomment the HTTPS redirect in `public/.htaccess`
- Change the default `superadmin` password immediately after first login

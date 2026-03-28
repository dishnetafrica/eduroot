# Installation Guide

## Prerequisites

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 7.4 | 8.1+ |
| MySQL | 5.7 | 8.0 |
| Apache | 2.4 | 2.4 |
| Composer | 2.x | 2.x |
| RAM | 512 MB | 2 GB |
| Disk | 1 GB | 10 GB |

**Required PHP extensions:**
```
pdo  pdo_mysql  mysqli  gd  mbstring  curl  openssl  zip  intl  xml
```

---

## Step 1 — Clone the Repository

```bash
git clone https://github.com/your-org/eduroot.git /var/www/eduroot
cd /var/www/eduroot
```

---

## Step 2 — Install PHP Dependencies

EduRoot uses Composer for the Omnipay payment library:

```bash
cd application/third_party/omnipay
composer install --no-dev --optimize-autoloader
cd ../../..
```

---

## Step 3 — Create Database

```sql
CREATE DATABASE eduroot
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'eduroot_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON eduroot.* TO 'eduroot_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Step 4 — Configure Database Credentials

```bash
cp application/config/database.example.php application/config/database.php
```

Edit `application/config/database.php`:

```php
$db['default'] = array(
    'dsn'          => '',
    'hostname'     => 'localhost',
    'username'     => 'eduroot_user',
    'password'     => 'your_strong_password',
    'database'     => 'eduroot',
    'dbdriver'     => 'mysqli',
    'dbprefix'     => '',
    'pconnect'     => FALSE,
    'db_debug'     => (ENVIRONMENT !== 'production'),
    'cache_on'     => FALSE,
    'cachedir'     => '',
    'char_set'     => 'utf8mb4',
    'dbcollat'     => 'utf8mb4_unicode_ci',
    'swap_pre'     => '',
    'encrypt'      => FALSE,
    'compress'     => FALSE,
    'stricton'     => FALSE,
    'failover'     => array(),
    'save_queries' => TRUE
);
```

---

## Step 5 — Set Base URL

Edit `application/config/config.php`:

```php
$config['base_url'] = 'https://your-school-domain.com/';
```

Generate and set a strong encryption key:

```php
$config['encryption_key'] = 'your-32-char-random-key-here-xxx';
```

Generate one with:
```bash
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

---

## Step 6 — Set File Permissions

```bash
chmod -R 755 application/logs/
chmod -R 755 application/cache/
chmod -R 755 application/tmp/
chmod -R 755 uploads/

# Ensure the web server user owns these directories
chown -R www-data:www-data application/logs/ application/cache/ application/tmp/ uploads/
```

---

## Step 7 — Configure Apache Virtual Host

Create `/etc/apache2/sites-available/eduroot.conf`:

```apache
<VirtualHost *:80>
    ServerName your-school-domain.com
    DocumentRoot /var/www/eduroot

    <Directory /var/www/eduroot>
        AllowOverride All
        Options -Indexes +FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/eduroot_error.log
    CustomLog ${APACHE_LOG_DIR}/eduroot_access.log combined
</VirtualHost>
```

Enable the site:

```bash
a2ensite eduroot
a2enmod rewrite
systemctl reload apache2
```

> **HTTPS strongly recommended.** Use Certbot: `certbot --apache -d your-school-domain.com`

---

## Step 8 — Run the Web Installer

Navigate to:
```
https://your-school-domain.com/install
```

The installer will:
1. Verify PHP extensions
2. Import the database schema
3. Seed default data (roles, modules, languages)
4. Create the first super admin account
5. Set up the initial school profile

After installation completes, **delete or disable the install route** by commenting it out in `application/config/routes.php`:

```php
// $route['install'] = 'install/start';
// $route['install/(:any)'] = 'install/$1';
```

---

## Step 9 — Configure Cron Jobs

Add to your crontab (`crontab -e`):

```bash
# Fee reminders — 8am daily
0 8 * * * curl -s "https://your-school-domain.com/cron/fees_reminder" > /dev/null 2>&1

# Attendance notifications — 3pm daily
0 15 * * * curl -s "https://your-school-domain.com/cron/attendance_notification" > /dev/null 2>&1

# Birthday notifications — 7am daily
0 7 * * * curl -s "https://your-school-domain.com/cron/birthday_notification" > /dev/null 2>&1

# General maintenance cron — every hour
0 * * * * curl -s "https://your-school-domain.com/cron/main" > /dev/null 2>&1
```

See [Cron Jobs Reference](cron-jobs.md) for all available cron endpoints.

---

## Step 10 — First Login

Default super admin credentials are set during the installer. Log in at:
```
https://your-school-domain.com/admin
```

Complete the school setup under **Settings → School Settings**.

---

## Post-Installation Checklist

- [ ] Set `ENVIRONMENT` to `production` in `index.php`
- [ ] Set `$config['cookie_secure'] = TRUE` in `config.php` (HTTPS required)
- [ ] Set `$config['cookie_httponly'] = TRUE` in `config.php`
- [ ] Configure SMTP in **Admin → Email Configuration**
- [ ] Configure SMS gateway in **Admin → SMS Configuration**
- [ ] Upload school logo and set school details
- [ ] Set the active academic session
- [ ] Configure fee types, classes, and sections
- [ ] Delete `decrypt_test.php` from the project root
- [ ] Verify cron jobs are running

---

## Nginx Configuration (Alternative)

If using Nginx instead of Apache:

```nginx
server {
    listen 80;
    server_name your-school-domain.com;
    root /var/www/eduroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block access to application/ and system/ directories
    location ~* ^/(application|system)/ {
        return 403;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## Troubleshooting

**Blank page after installation**
- Check `application/logs/` for PHP errors
- Ensure `mod_rewrite` is enabled (`a2enmod rewrite`)
- Verify `AllowOverride All` is set in your Apache config

**Database connection error**
- Confirm MySQL is running: `systemctl status mysql`
- Test credentials: `mysql -u eduroot_user -p eduroot`
- Check `database.php` has no syntax errors

**File upload fails**
- Check `uploads/` directory permissions: `ls -la uploads/`
- Check `upload_max_filesize` and `post_max_size` in `php.ini`

**Cron jobs not running**
- Test manually: `curl -v https://your-domain.com/cron/main`
- Check cron log: `grep CRON /var/log/syslog`

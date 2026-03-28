# EduRoot SaaS — Multi-Tenancy Setup Guide
# Database-per-school architecture

---

## How it works (2-minute summary)

1. School visits `eduroot.in/register` → fills form → lands in your approval queue
2. You go to `admin.eduroot.in/superadmin/registrations` → click Approve
3. `SchoolProvisioner` runs automatically:
   - Creates `eduroot_s0001` MySQL database
   - Runs your schema SQL (all 211 tables)
   - Seeds first admin user + first academic session + school settings
   - Registers in `eduroot_master.schools`
   - Warms the subdomain cache
4. School gets a welcome email: URL + temp password. They log in immediately.
5. `TenantMiddleware` runs on every request — reads `greenvalley.eduroot.in` →
   looks up `eduroot_master.subdomain_cache` → switches `$CI->db` to `eduroot_s0001`.
   All 155 models work without a single change.

---

## Files to deploy

```
application/
├── config/
│   ├── database.php        ← add 'master' group (see config_additions.php)
│   ├── config.php          ← add saas_mode settings (see config_additions.php)
│   ├── hooks.php           ← add TenantMiddleware hook (see hooks_and_routes.php)
│   └── routes.php          ← add register + superadmin routes (see hooks_and_routes.php)
├── controllers/
│   ├── Register.php        ← public self-registration
│   ├── Superadmin.php      ← your management panel
│   └── Tools.php           ← CLI migration runner (extracted from MigrationRunner.php)
├── libraries/
│   ├── TenantMiddleware.php ← the DB-switching hook
│   ├── SchoolProvisioner.php← creates school in < 5 seconds
│   └── MigrationRunner.php  ← runs schema changes across all schools
└── sql/
    └── school_schema.sql   ← YOU MUST GENERATE THIS (step 3 below)
```

---

## Step-by-step deployment

### Step 1 — Create the master database

```bash
mysql -u root -p < saas/sql/01_master_database.sql
mysql -u root -p < saas/sql/02_migration_log_and_server_config.sql
```

### Step 2 — Create a MySQL provisioner user

This user needs CREATE DATABASE privileges. Do NOT use your app's regular DB user.

```sql
CREATE USER 'eduroot_admin'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON `eduroot_%`.* TO 'eduroot_admin'@'localhost';
FLUSH PRIVILEGES;
```

### Step 3 — Generate the school schema template (CRITICAL)

This is the SQL that gets run when a new school is created.
Run it against your existing single-school database:

```bash
mysqldump \
  --no-data \
  --routines \
  --skip-comments \
  --single-transaction \
  your_existing_database > application/sql/school_schema.sql
```

That file is the template. Every new school gets an exact copy of this schema.

### Step 4 — Deploy the PHP files

Copy all files from the saas/ folder into your EduRoot install:

```bash
cp saas/libraries/TenantMiddleware.php  application/libraries/
cp saas/libraries/SchoolProvisioner.php application/libraries/
cp saas/libraries/MigrationRunner.php   application/libraries/
cp saas/controllers/Register.php        application/controllers/
cp saas/controllers/Superadmin.php      application/controllers/
```

Extract the Tools class from MigrationRunner.php into its own file:
```bash
# The Tools class is at the bottom of MigrationRunner.php
# Move it to application/controllers/Tools.php
```

### Step 5 — Update config files

Open `application/config/database.php` and add the 'master' group
from `saas/config/config_additions.php`.

Open `application/config/config.php` and add the SaaS settings block.

Set your real values:
```php
$config['db_super_user'] = 'eduroot_admin';    // from step 2
$config['db_super_pass'] = 'StrongPassword123!';
$config['base_domain']   = 'eduroot.in';
$config['trial_days']    = 30;
```

### Step 6 — Update hooks.php and routes.php

Add the pre_controller hook and routes from `saas/config/hooks_and_routes.php`.

### Step 7 — DNS (set once, never touch again)

In your DNS provider (Cloudflare recommended):

```
A    @   YOUR_SERVER_IP    (eduroot.in itself)
A    *   YOUR_SERVER_IP    (*.eduroot.in — catches ALL subdomains)
```

### Step 8 — Wildcard SSL cert

```bash
# Using Cloudflare DNS plugin (recommended for wildcard):
certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials ~/.secrets/cloudflare.ini \
  -d eduroot.in \
  -d "*.eduroot.in"
```

Or use Let's Encrypt with manual DNS challenge. The cert covers ALL subdomains.

### Step 9 — Create your superadmin account

The default superadmin is seeded in the SQL (email: superadmin@eduroot.in, password: Admin@123).
Change it immediately:

```sql
USE eduroot_master;
UPDATE superadmins
SET password = '$2y$10$YOUR_BCRYPT_HASH_HERE'
WHERE email = 'superadmin@eduroot.in';
```

Generate bcrypt hash:
```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"
```

### Step 10 — Test end-to-end

```bash
# 1. Visit https://eduroot.in/register
#    Fill in: School Name, subdomain "testschool", your email

# 2. Visit https://admin.eduroot.in/superadmin/login
#    Login with your superadmin credentials

# 3. Go to Registrations → click Approve on your test request
#    SchoolProvisioner runs — check logs:
tail -f application/logs/log-$(date +%Y-%m-%d).php

# 4. Visit https://testschool.eduroot.in
#    Should load the EduRoot login page
#    Login with the email you registered + temp password from approval

# 5. Verify DB isolation:
mysql -u root -p -e "SHOW DATABASES LIKE 'eduroot_%';"
# Should show: eduroot_master, eduroot_s0001
```

---

## Running schema migrations

When you update your EduRoot code and need to run a new SQL migration
across all school databases:

```bash
# Dry run first — see what would happen, no changes made
php index.php tools run_migration --file=application/migrations/sql/044_new_column.sql --dry_run

# Run for real
php index.php tools run_migration --file=application/migrations/sql/044_new_column.sql

# Test on one school before rolling out
php index.php tools run_migration --sql="ALTER TABLE students ADD COLUMN photo_url VARCHAR(255) NULL" --school=testschool

# Roll out to all
php index.php tools run_migration --sql="ALTER TABLE students ADD COLUMN photo_url VARCHAR(255) NULL"

# List all school databases
php index.php tools list_schools
```

---

## What TenantMiddleware does per request

```
Request: GET https://greenvalley.eduroot.in/admin/dashboard

1. TenantMiddleware::boot() fires (pre_controller hook)
2. Extracts 'greenvalley' from HTTP_HOST
3. NOT in reserved list (www, admin, api...) → treat as school subdomain
4. Queries eduroot_master.subdomain_cache WHERE subdomain='greenvalley'
   → finds: { db_name: 'eduroot_s0001', status: 1 }
5. Calls $CI->db->database = 'eduroot_s0001'; $CI->db->initialize();
6. Defines: CURRENT_SCHOOL_ID=1, CURRENT_SUBDOMAIN='greenvalley'
7. Returns — controller runs normally
8. Every model query now hits eduroot_s0001 automatically

Total overhead per request: ~0.3ms (single index lookup on subdomain_cache)
```

---

## Superadmin panel features

- `admin.eduroot.in/superadmin/dashboard`   — stats, recent schools, pending approvals
- `admin.eduroot.in/superadmin/registrations` — approve / reject self-registrations
- `admin.eduroot.in/superadmin/schools`      — manage all schools (search, suspend, reactivate)
- `admin.eduroot.in/superadmin/schools/create` — create school directly (skip self-registration)

---

## Billing integration (add later)

When a school's Razorpay subscription payment succeeds, call:

```php
$this->load->library('SchoolProvisioner');
$this->schoolprovisioner->reactivate($school_id);

// Update plan and expiry in master DB
$this->db->where('id', $school_id)->update('schools', [
    'plan'        => 'pro',
    'plan_expires'=> date('Y-m-d', strtotime('+1 year')),
    'status'      => 'active',
]);
```

When payment fails:
```php
$this->schoolprovisioner->suspend($school_id, 'Payment failed — Razorpay');
```

---

## Security notes

- Each school's database has NO cross-school foreign keys — complete isolation
- A query bug in one school's code cannot leak another school's data
- The subdomain_cache uses a PRIMARY KEY on subdomain — single index lookup, no table scan
- The provisioner MySQL user (eduroot_admin) can only access eduroot_% databases
- Never store db_super_pass in the database — config.php only, excluded from git

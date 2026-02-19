# VØID Studio — Setup Guide
Complete step-by-step guide to get the site running with PHP + MySQL backend.

---

## 📁 Final Folder Structure

```
void-studio/
├── index.html              ← Main website
├── css/
│   └── style.css           ← All styles
├── js/
│   └── main.js             ← All JavaScript (with real API calls)
├── api/
│   └── contact.php         ← Contact form API endpoint
├── config/
│   └── db.php              ← Database credentials & connection
├── admin/
│   ├── login.php           ← Admin login page
│   └── index.php           ← Admin dashboard (view/manage submissions)
└── sql/
    └── schema.sql          ← Database setup script
```

---

## ⚙️ Requirements

| Tool         | Minimum Version |
|--------------|----------------|
| PHP          | 8.0+           |
| MySQL        | 5.7+ / MariaDB 10.3+ |
| Web server   | Apache / Nginx (or XAMPP / WAMP locally) |

---

## 🚀 Step-by-Step Setup

### STEP 1 — Set up your local server (skip if already on hosting)

**Option A — XAMPP (Windows/Mac/Linux, recommended for beginners)**
1. Download from https://www.apachefriends.org
2. Install and open XAMPP Control Panel
3. Click **Start** next to **Apache** and **MySQL**
4. Place your `void-studio/` folder inside `C:/xampp/htdocs/` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
5. Open browser → `http://localhost/void-studio/`

**Option B — WAMP (Windows only)**
1. Download from https://www.wampserver.com
2. Install → place project in `C:/wamp64/www/void-studio/`
3. Open browser → `http://localhost/void-studio/`

**Option C — PHP built-in server (quickest)**
```bash
cd void-studio
php -S localhost:8000
# Open http://localhost:8000
```
> Note: Built-in server is fine for testing but doesn't support .htaccess.

---

### STEP 2 — Create the Database

**Using phpMyAdmin (easiest — works with XAMPP/WAMP)**
1. Open `http://localhost/phpmyadmin`
2. Click **SQL** tab at the top
3. Paste the entire contents of `sql/schema.sql`
4. Click **Go**

**Using MySQL command line**
```bash
mysql -u root -p < sql/schema.sql
```

**Using TablePlus / DBeaver / any GUI**
1. Connect to `localhost` with your credentials
2. Open a new SQL tab
3. Run the contents of `sql/schema.sql`

---

### STEP 3 — Configure Database Credentials

Open `config/db.php` and update these lines:

```php
define('DB_HOST', 'localhost');   // usually 'localhost'
define('DB_NAME', 'void_studio'); // database name from schema.sql
define('DB_USER', 'root');        // ← your MySQL username
define('DB_PASS', '');            // ← your MySQL password (empty for XAMPP default)

define('MAIL_TO',   'hello@void.studio');   // ← where contact emails go
define('MAIL_FROM', 'noreply@void.studio'); // ← sender address

define('SESSION_SECRET', 'change_me_to_a_long_random_string_123!@#'); // ← change this!
```

---

### STEP 4 — Verify the API URL in JavaScript

Open `js/main.js` and confirm the API path is correct:

```js
const API_URL = './api/contact.php';
```

If your site lives in a subfolder (e.g. `localhost/void-studio/`), this relative path works fine.
If it's at the domain root, it also works fine.

---

### STEP 5 — Test the Contact Form

1. Open your site in the browser
2. Scroll to the contact form
3. Fill it in and submit
4. You should see a gold success message
5. Open `http://localhost/void-studio/admin/` to confirm the submission was saved

---

### STEP 6 — Admin Panel

**URL:** `http://localhost/void-studio/admin/`

**Default credentials:**
```
Username: admin
Password: Admin@1234
```

> ⚠️ **IMPORTANT:** Change the password immediately. Open phpMyAdmin → `void_studio` database → `admin_users` table → run:
```sql
UPDATE admin_users
SET password = '$2y$12$YOUR_NEW_HASH_HERE'
WHERE username = 'admin';
```

To generate a hash in PHP:
```php
echo password_hash('YourNewPassword123!', PASSWORD_BCRYPT);
```

**Admin panel features:**
- View all contact submissions
- Filter by status (New / Read / Replied / Archived)
- Search by name, email, or message content
- Update submission status
- Click **Reply** to open your email client pre-filled
- Delete submissions
- Paginated (15 per page)

---

## 🌐 Deploying to Live Hosting (cPanel / Hostinger / SiteGround etc.)

1. **Upload files** via FTP (FileZilla) or cPanel File Manager to `public_html/` (or a subfolder)
2. **Create MySQL database** in cPanel → MySQL Databases
3. **Import schema**: cPanel → phpMyAdmin → select DB → Import → choose `sql/schema.sql`
4. **Update `config/db.php`** with your live hosting credentials (host is often `localhost`)
5. **Set permissions**: `config/` folder should be `755`, `db.php` should be `644`

---

## 🔒 Security Checklist

- [ ] Change default admin password
- [ ] Update `SESSION_SECRET` to a long random string
- [ ] Set correct `DB_USER` / `DB_PASS` (avoid using `root` in production)
- [ ] Add `.htaccess` to block direct access to `config/` and `sql/` folders (see below)
- [ ] Enable HTTPS on your hosting (free via Let's Encrypt)
- [ ] Restrict `MAIL_FROM` to your actual domain

**Recommended `.htaccess` for `config/` and `sql/` folders:**
```apache
# Place this file inside config/ and sql/ folders
Order deny,allow
Deny from all
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| White page / blank | Enable PHP errors: add `ini_set('display_errors', 1);` top of `db.php` |
| "Database connection failed" | Check DB_HOST, DB_USER, DB_PASS in `config/db.php` |
| Form submits but no email | PHP `mail()` needs an SMTP server. Use a service like Mailgun or SMTP plugin |
| Admin login fails | Regenerate password hash: `echo password_hash('YourPass', PASSWORD_BCRYPT);` |
| 404 on `api/contact.php` | Confirm Apache is running and file path is correct |
| CORS errors | Set `Access-Control-Allow-Origin` in `api/contact.php` to your domain |

---

## 📧 Better Email (Optional)

PHP's built-in `mail()` often lands in spam. For production, replace the `mail()` call in `api/contact.php` with **PHPMailer + SMTP**:

```bash
composer require phpmailer/phpmailer
```

Then update the email section in `api/contact.php` — happy to help with this if needed!
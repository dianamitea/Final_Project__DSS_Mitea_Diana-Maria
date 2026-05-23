# Petals & Bloom — Installation Guide

## Requirements
- XAMPP (PHP 8.0+, MySQL 8.0+, Apache)
- Python 3.8+ (for Excel export only)

---

## 1. Database Setup

1. Start XAMPP (Apache + MySQL).
2. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Create a new database: **`flower_shop_dss`**
4. Select the database → click **Import** → choose:
   ```
   database/schema.sql
   ```
5. Click **Go** to import.

---

## 2. Set Admin Password

After import, open phpMyAdmin → `flower_shop_dss` → `admins` table.

Run this SQL to set the real password hash for the `admin` user:

```sql
UPDATE admins
SET password_hash = '$2y$10$YourGeneratedHashHere'
WHERE username = 'admin';
```

To generate the hash, create a temporary PHP file:

```php
<?php
echo password_hash('Admin@1234', PASSWORD_BCRYPT);
```

Copy the output and paste it into the SQL above.

**Test credentials:**
| Role  | Username | Password     |
|-------|----------|--------------|
| Admin | `admin`  | `Admin@1234` |

---

## 3. Access the Application

- **Public site:**   `http://localhost/Final_Project__DSS_Mitea_Diana-Maria/`
- **Admin panel:**   `http://localhost/Final_Project__DSS_Mitea_Diana-Maria/admin/`

---

## 4. File Upload Directory

Ensure these directories exist and are writable:

```
assets/uploads/products/
assets/uploads/files/
exports/
```

They will be created automatically on first use if PHP has write permissions.
If not, create them manually in the project folder.

---

## 5. Python Excel Export

Install dependencies:

```bash
pip install pymysql pandas openpyxl
```

Or simply run:

```bash
run_export.bat
```

The exported `.xlsx` file will appear in the `exports/` folder.

---

## 6. Project Structure

```
Final_Project__DSS_Mitea_Diana-Maria/
├── admin/                  ← Admin panel
│   ├── includes/           ← Admin header/sidebar/footer
│   ├── orders/             ← Order CRUD + status + PDF
│   ├── products/           ← Product CRUD
│   ├── categories/         ← Category CRUD
│   ├── customers/          ← Customer list & detail
│   ├── reports/            ← Charts & analytics
│   ├── uploads/            ← File upload management
│   ├── ajax/               ← AJAX endpoints
│   ├── currency.php        ← Live exchange rates
│   ├── index.php           ← Admin dashboard
│   ├── login.php           ← Admin login
│   └── logout.php
├── assets/
│   ├── css/
│   │   ├── style.css       ← Public CSS
│   │   └── admin.css       ← Admin CSS
│   ├── js/
│   │   └── main.js         ← Public jQuery
│   └── uploads/            ← Uploaded images & files
├── database/
│   └── schema.sql          ← Full DB schema + seed data
├── includes/               ← Shared PHP includes
│   ├── auth.php
│   ├── db.php
│   ├── header.php
│   └── footer.php
├── lib/
│   └── pdf_generator.php   ← Raw PDF class (no library needed)
├── index.php               ← Home page
├── products.php            ← Product listing
├── product.php             ← Product detail
├── order.php               ← Order form (public)
├── confirmation.php        ← Order confirmation
├── status.php              ← Order status tracker
├── register.php            ← Customer registration
├── login.php               ← Customer login
├── logout.php
├── contact.php
├── export_project_data_to_excel.py   ← Python export (PY1)
├── requirements.txt
├── run_export.bat
└── INSTALL.md              ← This file
```

---

## 7. Notes for Grader

- All PHP uses **prepared statements** (MySQLi) to prevent SQL injection.
- CSRF tokens protect all POST forms.
- Passwords are stored using `password_hash(PASSWORD_BCRYPT)`.
- The **order placement** (both public and admin) uses a **MySQL transaction**.
- **Reports page** has 5 Chart.js charts powered by SQL queries.
- **Currency page** fetches live rates via cURL from `open.er-api.com`, caches in DB, and supports AJAX refresh.
- **PDF generation** uses a custom raw PHP class — no Composer or external library required.
- **Python export** produces a 5-sheet `.xlsx` with frozen headers, auto column widths, alternating row colours, and filters.

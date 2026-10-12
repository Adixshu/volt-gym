# Volt Gym PHP backend

## Run with XAMPP

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin`.
3. Import `php/schema.sql` using the **Import** tab. This creates the `volt_gym` database and its `inquiries`, `reviews`, `admins`, and `admin_read_status` tables.
4. Open the site at `http://localhost/Volt_Gym/` (do not open `index.html` directly from the file system).
5. Submit either form, then open `http://localhost/Volt_Gym/php/admin.php` to review submissions.

The enquiry and review forms accept an email address, a phone number, or both.
When upgrading an existing database, run these statements once in phpMyAdmin
before using the updated forms:

```sql
ALTER TABLE inquiries
  MODIFY email VARCHAR(254) NULL,
  ADD COLUMN phone VARCHAR(32) NULL;

ALTER TABLE reviews
  ADD COLUMN email VARCHAR(254) NULL,
  ADD COLUMN phone VARCHAR(32) NULL;
```

## Create an admin account

Create an account from PowerShell on the server; public admin registration is not available. In PowerShell, run this command from any folder (including your user folder):

```powershell
& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\Volt_Gym\php\create_admin.php' admin
```

Choose a password of at least 12 characters when prompted, then sign in at `http://localhost/Volt_Gym/php/admin.php`. Run the same command with another username to add an additional admin. Admin passwords are stored as password hashes.

If you already imported the schema before setting up the admin panel, re-import `php/schema.sql` or run the `CREATE TABLE IF NOT EXISTS admins` and `CREATE TABLE IF NOT EXISTS admin_read_status` statements from that file in phpMyAdmin.

The connection defaults are `127.0.0.1`, database `volt_gym`, MySQL user `root`, and a blank password, which match a typical local XAMPP installation. Override them with `VOLT_DB_HOST`, `VOLT_DB_NAME`, `VOLT_DB_USER`, and `VOLT_DB_PASSWORD` environment variables if your MySQL credentials differ.

The JSON endpoint is `php/api.php`. Enquiry and review submissions are validated server-side and stored using prepared statements. The admin dashboard lists reviews and enquiries separately, keeps each record's read/unread status in `admin_read_status`, and requires a signed-in admin session.

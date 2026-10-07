# Volt Gym PHP backend

## Run with XAMPP

1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Open `http://localhost/phpmyadmin`.
3. Import `php/schema.sql` using the **Import** tab. This creates the `volt_gym` database and its `inquiries` and `reviews` tables.
4. Open the site at `http://localhost/Volt_Gym/` (do not open `index.html` directly from the file system).
5. Submit either form, then inspect its rows in phpMyAdmin under `volt_gym`.

The connection defaults are `127.0.0.1`, database `volt_gym`, MySQL user `root`, and a blank password, which match a typical local XAMPP installation. Override them with `VOLT_DB_HOST`, `VOLT_DB_NAME`, `VOLT_DB_USER`, and `VOLT_DB_PASSWORD` environment variables if your MySQL credentials differ.

The JSON endpoint is `php/api.php`. Enquiry and review submissions are validated server-side and stored using prepared statements.

# Volt Gym

**A focused training space for strength, conditioning, and long-term progress.**

Volt Gym is a responsive website for a neighborhood gym. It introduces the gym,
its training programs, coaches, membership options, and community, with an
optional PHP and MySQL backend for contact enquiries and member reviews.

## Preview

[Open the static GitHub Pages preview](https://adixshu.github.io/volt-gym-website/).

GitHub Pages serves the front end as a static website. PHP endpoints are not
executed there, so submitting enquiries or reviews and using the admin
dashboard require a PHP-enabled server and a configured MySQL database.

## Website features

- Responsive navigation and layouts for desktop and mobile
- Automatic hero image slider with previous, next, and pagination controls
- Persistent light and dark theme selection
- Gym overview, training programs, photo gallery, and image lightbox
- Coach profiles, member review carousel, and membership plans
- Contact and review forms connected to the optional backend
- PHP admin dashboard for reviewing and managing submissions
- Accessible control labels and reduced-motion support

## Technology

- HTML5, CSS3, and vanilla JavaScript
- PHP and MySQL for form submissions and the admin dashboard
- Bootstrap CSS and Bootstrap Icons
- Google Fonts: Anton, Oswald, and Poppins
- GitHub Actions for automatic GitHub Pages deployment

No Node.js installation or frontend build step is required.

## Run locally

### Static preview

For browsing the front end only, open `index.html` in a browser or serve the
project directory with any static web server. Backend form submissions and
the admin dashboard are unavailable without PHP and MySQL.

### Full website with XAMPP

1. Place or clone the repository at `C:\xampp\htdocs\Volt_Gym`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin` and import
   [`php/schema.sql`](php/schema.sql).
4. Visit `http://localhost/Volt_Gym/`.
5. Create the initial `admin` account on a new database using the masked
   password prompt below. This bootstrap runs only when the `admins` table is
   empty; its password is hashed before it is stored and is not hardcoded in
   the repository.

   ```powershell
   $securePassword = Read-Host 'Initial admin password' -AsSecureString
   $passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
   try {
     $env:VOLT_INITIAL_ADMIN_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
     & 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\Volt_Gym\php\bootstrap_admin.php'
   } finally {
     [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
     Remove-Item Env:VOLT_INITIAL_ADMIN_PASSWORD -ErrorAction SilentlyContinue
     $securePassword.Dispose()
   }
   ```

   Enter the initial password when prompted, then sign in at
   `http://localhost/Volt_Gym/php/admin.php`. Passwords must be at least 12
   characters.

6. To create additional admin accounts, use the existing CLI tool:

   ```powershell
   & 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\Volt_Gym\php\create_admin.php' another-admin
   ```

   Follow its prompts to set and confirm that account's password.

The backend uses the `volt_gym` database and, by default, the local MySQL
`root` account with a blank password. To use different database credentials,
set the `VOLT_DB_HOST`, `VOLT_DB_NAME`, `VOLT_DB_USER`, and `VOLT_DB_PASSWORD`
environment variables on the server.

The API endpoint is `php/api.php`.

## Project structure

```text
.
├── assets/
│   ├── css/          Stylesheets and Bootstrap CSS
│   ├── font/         Local font assets
│   ├── image/        Logo and profile images
│   └── js/           Frontend interactions
├── php/
│   ├── admin.php     Admin dashboard
│   ├── api.php       Form submission API
│   ├── bootstrap_admin.php  One-time initial admin setup
│   ├── config.php    Database configuration
│   ├── create_admin.php  Add admin accounts
│   └── schema.sql    Database schema
├── .github/
│   └── workflows/    GitHub Pages deployment workflow
└── index.html        Main website
```

## Deployment

The GitHub Actions workflow in `.github/workflows/pages.yml` deploys the site
to GitHub Pages when changes are pushed to `main`. It can also be started
manually from the repository's **Actions** tab. Configure GitHub Pages to use
GitHub Actions as its build and deployment source.

To deploy the backend features, host the `php/` application on a server that
supports PHP and MySQL and configure its database credentials. GitHub Pages
does not provide a PHP runtime or database.

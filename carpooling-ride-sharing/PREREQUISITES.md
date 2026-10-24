# Prerequisites & Setup — TransitPool — Community Carpooling & Ride-Sharing Platform (Windows)

Everything below is Windows-specific. This project is plain PHP (custom MVC) with **no Composer dependencies** — no email/PDF/Excel library is used.

## 1. Install XAMPP

1. Download XAMPP with **PHP 8.1 or newer** from https://www.apachefriends.org (default install path `C:\xampp`).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Add `C:\xampp\php` and `C:\xampp\mysql\bin` to your system `Path`, then confirm `php -v` / `mysql --version` in a new terminal.

## 2. No Composer needed

This project has no `composer.json` — skip Composer entirely.

## 3. No extra PHP extensions needed

Only `pdo_mysql` is required, which is enabled by default in a standard XAMPP install.

## 4. Configure MySQL/MariaDB port

`config/database.php` is committed pointing at `127.0.0.1:3306`, `root`, no password — XAMPP's own defaults, so no change should be needed on a standard XAMPP install. Only edit `config/database.php` if your local MySQL differs.

## 5. Database setup

Unlike most of this suite, `database/schema.sql` does **not** create the database itself (only `DROP TABLE IF EXISTS` / `CREATE TABLE`), so create it first, then import:
```
mysql -u root -P 3306 -e "CREATE DATABASE IF NOT EXISTS carpooling_ride_sharing"
mysql -u root -P 3306 carpooling_ride_sharing < database/schema.sql
```
(or create the database via phpMyAdmin, then import `database/schema.sql` into it). This creates the tables and demo seed data (15 seeded drivers, 20 seeded riders).

## 6. Verify everything

```
php -v
mysql -u root -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```
Ready — see `HOW-TO-RUN.md` to start the app.

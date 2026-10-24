# How to Run — TransitPool — Community Carpooling & Ride-Sharing Platform

Assumes prerequisites in `PREREQUISITES.md` are already done. No Composer step — this project has no dependencies.

## One-time setup

```
mysql -u root -P 3306 -e "CREATE DATABASE IF NOT EXISTS carpooling_ride_sharing"
mysql -u root -P 3306 carpooling_ride_sharing < database/schema.sql
```
This creates the `carpooling_ride_sharing` database, tables, and demo seed data.

To wipe the seeded data and start over, just re-run the second (import) command — `database/schema.sql` drops and recreates every table itself, so the database doesn't need dropping first:
```
mysql -u root -P 3306 carpooling_ride_sharing < database/schema.sql
```

## Start MySQL

Open **XAMPP Control Panel** and make sure **MySQL** is running.

## Run the app

From this project's folder:
```
php -S localhost:8020 -t public public/index.php
```
(`-t public` is required — without it, static assets under `public/assets` 404.)

## Open it

Visit `http://localhost:8020/` and sign in with any of the demo credentials in `README.md` under "Default Login Credentials".

## Stop the app

`Ctrl+C` in the terminal running the `php -S` command.

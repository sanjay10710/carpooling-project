# TransitPool — Community Carpooling & Ride-Sharing Platform

A carpooling platform for a college/office community: drivers post a
point-to-point ride (origin, destination, date/time, seats, price per
seat), riders search by plain-text origin/destination/date and book one or
more seats at a fare computed server-side, and once a ride is marked
completed the driver and every rider who took it can rate each other. A
Super Admin (Platform Admin) oversees every user, vehicle, ride, booking,
rating and reported issue on the platform.

## 1. Overview & Objective

Informal carpooling inside a college or office community usually happens
over scattered WhatsApp messages with no record of who actually has a seat
free, no protection against a ride quietly filling past its own capacity
when two people claim the "last seat" at once, and no way to know whether a
driver or rider is reliable before getting in the car. TransitPool fixes
that with a small, focused loop: post a ride, search/book it, take the
ride, rate each other — with the one piece of real technical substance
being that **seat booking can never overbook a ride even under concurrent
requests** (see §9).

Matching is deliberately simple: origin/destination are free-text locality
names (e.g. "Koramangala, Bengaluru"), matched by case-insensitive
substring search — **not** a real maps/geocoding integration, since no
maps API key is available in this environment and the project brief
explicitly scopes this out.

## 2. Tech Stack

- **Backend**: Plain PHP 8.1+ (custom MVC, no framework) — PDO/MySQL, native sessions
- **Frontend**: Tailwind CSS (via CDN, no build step), **"Transit"** theme:
  sky-blue primary / lime accent, neutral grays, a dashed "route line" +
  pin-drop motif used sparingly (brand mark, ride cards) rather than a full
  illustrated hero. Space Grotesk for display type, Inter for body text,
  JetBrains Mono for anything that should read like a departure board
  (times, fares, plate numbers).
- **Database**: MySQL/MariaDB (InnoDB, foreign keys enforced)
- **No Composer dependencies** — no email/PDF/Excel features in this
  project's scope, so no `composer.json` (per `GROUNDRULES.md` §11, only
  reached for when a plain-PHP project genuinely needs one of those).

## 3. Database — Table List

| Table | Purpose |
|---|---|
| `users` | All three personas (`role` column): Super Admin, Driver, Rider. `status` active/inactive is the block-list Admin uses. |
| `vehicles` | A driver's registered vehicle(s). `status` active/inactive — an inactive vehicle can't be used to post a new ride. |
| `rides` | A driver's posted trip: origin, destination, departure time, `seats_total`/`seats_available`, price per seat, lifecycle `status`. |
| `bookings` | A rider's seat claim on a ride: `seats_booked`, fare snapshot (`fare_per_seat`/`total_fare`), `status` confirmed/cancelled. |
| `ratings` | Post-ride 1–5 star rating + optional comment, either direction (`rater_role`). Unique per `(ride_id, rater_id, ratee_id)`. |
| `reports` | A driver/rider flagging a ride or user; Admin resolves/dismisses from a moderation queue. |

ER shape: `users(driver) 1—N vehicles` · `users(driver) 1—N rides` ·
`vehicles 1—N rides` · `rides 1—N bookings` · `users(rider) 1—N bookings` ·
`rides 1—N ratings` (both directions) · `users 1—N reports` (as reporter,
reported user, and resolver).

Full schema with seed data: `database/schema.sql`.

## 4. Feature List

See `FEATURES.md` for the complete spec (pitch, 6 core modules, screen list
per persona, out-of-scope list). Summary by persona:

- **Super Admin**: platform dashboard (users/vehicles/rides/bookings/
  revenue/reports stats); activate/deactivate any user or vehicle; view
  every ride (incl. its full roster + ratings) and cancel one if needed;
  view every booking and rating platform-wide; resolve/dismiss reports.
- **Driver**: register/edit/deactivate own vehicle(s); post a ride
  (vehicle, origin, destination, departure time, seats, price/seat, notes);
  edit or cancel a ride while still scheduled; mark a ride ongoing then
  completed; view a ride's rider roster; rate each rider on a completed
  ride; view own ratings received; file a report.
- **Rider**: search rides by origin/destination/date; view ride details and
  book 1+ seats (server-computed fare, race-safe capacity check); view/
  cancel own bookings; rate the driver on a completed ride; view own
  ratings received; file a report.

## 5. Default Login Credentials

| Role | Email | Password | Notes |
|---|---|---|---|
| Super Admin | `admin@transitpool.test` | `Admin@123` | Full platform access |
| Driver | `driver1@transitpool.test` … `driver15@transitpool.test` | `Driver@123` | 15 seeded drivers; `driver15` is seeded **inactive** to demo the block-list |
| Rider | `rider1@transitpool.test` … `rider20@transitpool.test` | `Rider@123` | 20 seeded riders; `rider19`/`rider20` are seeded **inactive** |

All seeded passwords are documented here because they're demo accounts.
Real driver/rider accounts are created via open self-registration from the
login page (role selector: Driver or Rider) — the Super Admin account is
seed-only and is never self-registerable, matching the rest of this suite's
auth pattern (`GROUNDRULES.md` §4).

## 6. Setup

1. Start Apache + MySQL from the XAMPP Control Panel (MySQL on the default
   port **3306** — see the suite-wide `../CLAUDE.md` for the XAMPP
   environment notes).
2. Create the database and import the schema:
   ```
   mysql -u root -P 3306 -e "CREATE DATABASE IF NOT EXISTS carpooling_ride_sharing"
   mysql -u root -P 3306 carpooling_ride_sharing < database/schema.sql
   ```
   (or import `database/schema.sql` via phpMyAdmin — it's plain MySQL/
   InnoDB syntax with seed data baked in, so login works immediately.)
3. `config/database.php` already points at `127.0.0.1:3306`, `root`, no
   password — XAMPP's default. Edit it if your local MySQL differs.
4. Serve the app:
   - Via XAMPP/Apache: copy this folder under `htdocs` and point a vhost
     (or a subfolder alias) at `carpooling-ride-sharing/public/`.
   - Via PHP's built-in server for local testing — **this project's
     assigned dev-server port is 8020** (project #21 → 8000 + 21 − 1, per
     `../CLAUDE.md`'s port convention):
     ```
     php -S localhost:8020 -t public public/index.php
     ```
     (`-t public` matters — without it, static assets 404 silently while
     the HTML still renders.)
5. Log in with any credential from §5.

## 7. Folder Structure

```
carpooling-ride-sharing/
  public/
    index.php             <- front controller (all routes go through here)
    assets/{css,js}
  app/
    controllers/           AuthController, AdminController, DriverController,
                            RiderController, ReportController (shared by
                            Driver + Rider, see §10)
    models/                 User, Vehicle, Ride, Booking, Rating, Report
    views/
      admin/ driver/ rider/ auth/ reports/ errors/
      partials/            header.php, footer.php, navbar.php, flash.php
  core/                     Router, Database (PDO wrapper), Auth, Csrf, Validator, helpers
  config/
    database.php
  database/
    schema.sql             <- full MySQL/InnoDB schema + seed data
  README.md
  FEATURES.md
```

## 8. Security Baseline (GROUNDRULES §7 checklist)

- [x] Prepared statements everywhere (`core/Database.php` PDO, `PDO::ATTR_EMULATE_PREPARES => false`), no raw SQL concatenation of user input
- [x] Passwords hashed with `password_hash()`/`password_verify()` (bcrypt), never plaintext
- [x] CSRF token per session (`core/Csrf.php`), verified on every state-changing POST (`Csrf::requireValid()`), returns HTTP 419 on failure
- [x] All dynamic output escaped via `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`)
- [x] No file uploads in this project's scope (no upload validation needed)
- [x] Role/permission checks enforced server-side — every controller calls `Auth::requireRole(...)` in its constructor, independent of nav visibility; ownership checks (`belongsToDriver`/`belongsToRider`) additionally gate per-row access so a driver/rider can't act on another user's vehicle/ride/booking even by guessing an id in the URL

Additional hardening beyond the checklist: `session_regenerate_id(true)` on
every successful login; a distinct session cookie name
(`carpooling_ride_sharing_sess`) so this project's session never collides
with a sibling suite project's session on the same `127.0.0.1`/port during
parallel local dev; custom 404/403/419/500 error pages instead of raw stack
traces.

## 9. Race-Safe Seat Booking — the Technical Centerpiece

**The requirement**: under concurrent booking requests for the same ride,
the system must never allow confirmed bookings to exceed the ride's
`seats_total`.

**The naive approach and why it breaks**: check-then-act — `SELECT` the
ride, read `seats_available`, and if the request fits, `INSERT` the booking
and decrement the counter. Two riders' requests can both run that `SELECT`
in the same instant, both see "3 seats left, I want 2, that's fine," and
both proceed — nothing in that flow ever told the second request the first
one was already mid-flight. Whichever `UPDATE` runs last simply overwrites
the counter and the ride silently overbooks.

**This project's approach** (`Booking::book()` in `app/models/Booking.php`)
uses the same "row lock as mutex" pattern already established elsewhere in
this suite (project 3's slot reservation, project 19's registration
capacity check):

1. `SELECT * FROM rides WHERE id = ? FOR UPDATE`, inside an explicit
   transaction. Every concurrent booking attempt against this same ride
   must wait for MySQL's row-level exclusive lock before it can even read
   `seats_available` — serializing what would otherwise be a
   read-then-write race into a safe queue of one-at-a-time transactions.
2. While still holding that lock, `seats_available` is re-checked against
   the request. The second of two simultaneous "3 left, book 2" requests
   now sees the *already-decremented* value once its turn comes, not the
   stale number it might have read before locking.
3. The `bookings` row is inserted and `rides.seats_available` is
   decremented in the same transaction, committed together — an insert can
   never succeed while leaving the counter stale.

`Booking::cancel()` uses the identical locking discipline in reverse (lock
the booking + its ride, then restore the seat count), so a cancel racing a
concurrent new booking on the same ride can never both act on a stale
`seats_available`. `Ride::cancel()`/`Ride::adminCancel()` cascade-cancel
every confirmed booking on a cancelled ride inside one transaction too.

### Concurrency test result

No MySQL/Docker is available in this sandbox (checked: only
`pdo_sqlite`/`pdo_pgsql`/`pdo_mysql` drivers present, no `mysqld` binary),
so the live concurrency proof was run against the SQLite stand-in described
in §11 below, with `FOR UPDATE` stripped and `BEGIN` upgraded to
`BEGIN IMMEDIATE` by a throwaway query-rewriting shim (SQLite's own
equivalent of taking a write lock immediately, closing the same
read-then-write race window `FOR UPDATE` closes on MySQL) — confined
entirely to the scratch test copy, never touching the shipped
`core/Database.php`.

**Test**: a fresh ride was posted with exactly **5** seats. **8 different
riders logged in and fired a 2-seat booking request each, truly
concurrently** (via `curl_multi`, all 8 requests in flight before any
response returned) — 16 seats requested in total against only 5 available.

**Result, run 5 times in a row (fresh DB each time)**: every run admitted
exactly **2 of the 8** requests (filling the ride to 4/5 seats — the 5th
seat can't fit a 2-seat request, so it's correctly left unbooked rather
than partially split), and rejected the other 6 with "not enough seats
remaining." Verified directly against the database after each run:
`sum(seats_booked) + seats_available == seats_total` always held,
`seats_available` never went negative, and no rider ever ended up with two
confirmed bookings on the same ride from the race. **Zero overbooking
across all 5 runs.**

This still needs one confirming run against real XAMPP/MySQL before the
viva — `SELECT ... FOR UPDATE` inside an explicit transaction is real
MySQL/InnoDB syntax that the SQLite stand-in only exercised through the
compatibility shim, not through the shipped `core/Database.php`.

## 10. Design Notes (for viva)

- **`ReportController` is shared between Driver and Rider** rather than
  duplicated into each persona's controller — filing a report is the exact
  same form/logic for both roles (`Auth::requireRole('driver', 'rider')` at
  the top), so splitting it would only duplicate the same handful of
  validation lines twice for no behavioral difference, the same "shared
  screen, role-checked inside" reasoning this suite already uses elsewhere
  (e.g. campusconnect's shared board controller).
- **Fare is always server-computed, never trusted from the client.**
  `Booking::book()` reads `price_per_seat` off the *locked* ride row and
  multiplies by the server-validated seat count — the booking form's live
  fare preview (`public/assets/js/main.js`) is a pure display convenience;
  a tampered `seats` value in a forged POST still gets the correct fare
  recomputed server-side, not whatever the client displayed.
- **A ride's `seats_total` is fixed once posted.** The edit-ride form
  disables that field and resubmits it via a hidden input rather than
  letting a driver shrink capacity out from under bookings already made
  against it (which would need a reconciliation policy this project's
  scope deliberately doesn't take on — see `FEATURES.md` §6).
- **Vehicle deactivation is independent of driver deactivation.** Admin can
  deactivate a specific vehicle (e.g. reported as unsafe) without touching
  the driver's account, or deactivate the driver account outright — two
  different, composable levers rather than one blunt "ban" action.
- **Role-based access enforced server-side**
  (`Auth::requireRole()`/`Auth::requireLogin()`), independent of nav
  visibility, checked at the top of every controller's constructor.

## 11. Testing Notes

This project was built and smoke-tested end-to-end in a sandbox with no
MySQL server available, against a throwaway SQLite stand-in (schema
recreated in SQLite syntax by a one-off script, with a query-rewriting
`Database.php` swap — strips `FOR UPDATE`, maps `NOW()` to SQLite's
`datetime('now')`, upgrades `BEGIN` to `BEGIN IMMEDIATE` — confined
entirely to a scratch copy of the project, never committed, and deleted
after testing).

Verified via direct HTTP requests against the running dev server (`php -S`
with `PHP_CLI_SERVER_WORKERS=8` for genuine multi-worker concurrency):

- Login for all three personas + wrong-password rejection + inactive-
  account rejection (both an inactive driver and an inactive rider)
- Server-side role-guard rejection: a rider hitting an admin URL, a driver
  hitting an admin URL, and a rider hitting a driver URL directly all
  return 403, not a rendered page
- CSRF rejection (419) on a forged POST with a garbage token
- Driver vehicle CRUD incl. case-insensitive duplicate-plate rejection
- Driver posts a ride → rider searches by origin substring and finds it
- Booking flow: correct per-seat and total fare shown and charged,
  overbooking beyond `seats_available` cleanly rejected
- **Race-safe seat booking concurrency test** (§9) — zero overbooking
  across 5 repeated runs
- Ride lifecycle: edit while scheduled, mark ongoing → completed, driver
  cancel and admin cancel both cascade-cancel confirmed bookings correctly
- Rider booking cancellation correctly restores `seats_available`
- Post-ride rating: driver rates a rider on a completed ride
- Admin resolves an open report from the moderation queue
- 404 handling for an unknown route and a nonexistent ride id
- All 23 distinct view templates across all three personas (dashboards,
  CRUD forms incl. edit-with-prefilled-data, roster, rating, profile,
  report-an-issue) rendered with **zero PHP warnings/notices/fatals** in
  their output

**This still needs one real run against XAMPP/MySQL before the viva** —
see §9's concurrency-test caveat above; everything else in this checklist
exercises application logic that is identical regardless of the underlying
PDO driver (the SQL itself, aside from `FOR UPDATE`/`NOW()`, is portable
between MySQL and SQLite and was executed unmodified).

## 12. Anticipated Viva Questions

**Q: Walk me through why the seat-booking race condition can't happen here.**
See §9 in full — the short version is that `SELECT ... FOR UPDATE` turns a
read-then-write race into a serialized queue by making every concurrent
request wait for the same row lock before it's even allowed to read
`seats_available`, so the check and the decrement effectively happen as one
atomic step per request, not two steps two requests can interleave between.

**Q: Why a row lock (`FOR UPDATE`) here instead of the unique-key-insert
pattern used in project 3 (doctor appointments)?** Project 3's slot booking
is a single yes/no claim on one exact (doctor, date, time) tuple — a unique
key on that tuple *is* the mutex, no explicit lock needed. Seat booking
here is a *quantity* claim (a rider can book 1–8 seats) against a shared
counter, not a single row's existence — there's no unique tuple to insert
that would, by itself, prevent the counter from being read and written by
two overlapping transactions. Locking the ride row for the duration of the
check-and-decrement is the natural fit for a shared-counter problem;
project 19 (Event Organizer) uses the identical row-lock pattern for the
same reason (variable-size team registrations against an event's capacity).

**Q: What stops a rider from booking seats on someone else's cancelled
ride, or a driver from editing another driver's ride?** Every model method
that mutates a ride/vehicle/booking takes the acting user's id as a
parameter and includes it in the `WHERE` clause (`Ride::belongsToDriver()`,
`Vehicle::belongsToDriver()`, `Booking::belongsToRider()`) — a mismatched
id makes the query affect zero rows, which the controller treats as a 404
or a rejected action, never a silent partial success. `Booking::book()`
additionally re-checks the ride's own `status` (must be `scheduled`) and
departure time inside the same locked transaction, so a booking against an
already-cancelled or already-departed ride is rejected server-side even if
the rider had the page open before it changed state.

**Q: How does fare calculation stay correct if a driver edits the price
after some riders have already booked?** `Booking::book()` snapshots
`price_per_seat` into the booking row's `fare_per_seat`/`total_fare` at
booking time — a later price edit on the ride only affects *new* bookings
going forward, never rewrites the fare a rider already agreed to and paid.

**Q: Why does Admin get a separate "cancel ride" action instead of just
deactivating the driver?** Different failure modes need different levers:
a specific ride might need to be pulled (e.g. a safety report about that
trip) without touching the driver's other rides or their ability to post
new ones, while deactivating the driver is the blunter tool for when the
account itself is the problem. Keeping them separate, composable actions
mirrors the same driver/vehicle-status split described in §10.

**Q: Why is `seats_total` locked after a ride is posted instead of
editable?** Shrinking it after bookings exist would require a policy for
what happens to bookings that no longer fit (auto-cancel the newest? the
biggest? ask the driver to pick?) that adds real complexity for a scenario
this project's scope deliberately doesn't take on (`FEATURES.md` §6) —
cancelling and reposting the ride is the straightforward alternative for a
driver who genuinely needs a different vehicle/capacity.

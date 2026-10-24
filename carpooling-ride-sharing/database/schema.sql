-- ============================================================================
-- Community Carpooling & Ride-Sharing Platform — database/schema.sql
-- MySQL 5.7+ / MariaDB, InnoDB, utf8mb4. Import via phpMyAdmin or:
--   mysql -u root -P 3306 -e "CREATE DATABASE IF NOT EXISTS carpooling_ride_sharing"
--   mysql -u root -P 3306 carpooling_ride_sharing < database/schema.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rides;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- users — all three personas share one table + a role column (GROUNDRULES §4)
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120)    NOT NULL,
    email      VARCHAR(150)    NOT NULL UNIQUE,
    phone      VARCHAR(20)     NOT NULL,
    password   VARCHAR(255)    NOT NULL,
    role       ENUM('super_admin','driver','rider') NOT NULL,
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- vehicles — owned by a driver; a deactivated vehicle can't be used to post
-- a new ride (checked server-side), independent of the driver's own status
-- ----------------------------------------------------------------------------
CREATE TABLE vehicles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id     INT UNSIGNED NOT NULL,
    make          VARCHAR(60)  NOT NULL,
    model         VARCHAR(60)  NOT NULL,
    color         VARCHAR(30)  NOT NULL,
    plate_number  VARCHAR(20)  NOT NULL UNIQUE,
    vehicle_type  ENUM('hatchback','sedan','suv','bike') NOT NULL,
    total_seats   TINYINT UNSIGNED NOT NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicles_driver FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_vehicles_driver (driver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- rides — a driver's posted trip. seats_available is the live counter that
-- the booking transaction below decrements/restores under a row lock; it is
-- never derived on the fly from bookings, so the FOR UPDATE lock on THIS row
-- is what actually serializes concurrent booking attempts for the ride.
-- ----------------------------------------------------------------------------
CREATE TABLE rides (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id        INT UNSIGNED NOT NULL,
    vehicle_id       INT UNSIGNED NOT NULL,
    origin           VARCHAR(120) NOT NULL,
    destination      VARCHAR(120) NOT NULL,
    departure_at     DATETIME NOT NULL,
    seats_total      TINYINT UNSIGNED NOT NULL,
    seats_available  TINYINT UNSIGNED NOT NULL,
    price_per_seat   DECIMAL(8,2) NOT NULL,
    notes            VARCHAR(255) NULL,
    status           ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rides_driver  FOREIGN KEY (driver_id)  REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_rides_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    INDEX idx_rides_driver (driver_id),
    INDEX idx_rides_search (status, departure_at),
    INDEX idx_rides_origin (origin),
    INDEX idx_rides_destination (destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- bookings — a rider claiming N seats on a ride. fare_per_seat/total_fare are
-- snapshotted at booking time so a later price edit never rewrites history.
-- ----------------------------------------------------------------------------
CREATE TABLE bookings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ride_id        INT UNSIGNED NOT NULL,
    rider_id       INT UNSIGNED NOT NULL,
    seats_booked   TINYINT UNSIGNED NOT NULL,
    fare_per_seat  DECIMAL(8,2) NOT NULL,
    total_fare     DECIMAL(8,2) NOT NULL,
    status         ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    booked_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at   TIMESTAMP NULL,
    CONSTRAINT fk_bookings_ride  FOREIGN KEY (ride_id)  REFERENCES rides(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_rider FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_bookings_ride (ride_id),
    INDEX idx_bookings_rider (rider_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- ratings — post-ride, either direction. One rating per (ride, rater, ratee)
-- pair, enforced by the unique key so a forged double-submit can't inflate
-- an average.
-- ----------------------------------------------------------------------------
CREATE TABLE ratings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ride_id     INT UNSIGNED NOT NULL,
    rater_id    INT UNSIGNED NOT NULL,
    ratee_id    INT UNSIGNED NOT NULL,
    rater_role  ENUM('driver','rider') NOT NULL,
    stars       TINYINT UNSIGNED NOT NULL,
    comment     VARCHAR(500) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ratings_ride  FOREIGN KEY (ride_id)  REFERENCES rides(id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_rater FOREIGN KEY (rater_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_ratee FOREIGN KEY (ratee_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_ratings_stars CHECK (stars BETWEEN 1 AND 5),
    CONSTRAINT uniq_rating_per_direction UNIQUE (ride_id, rater_id, ratee_id),
    INDEX idx_ratings_ratee (ratee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- reports — a rider/driver flagging a ride or another user; Admin resolves
-- or dismisses from the moderation queue.
-- ----------------------------------------------------------------------------
CREATE TABLE reports (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id       INT UNSIGNED NOT NULL,
    reported_user_id  INT UNSIGNED NULL,
    ride_id           INT UNSIGNED NULL,
    reason            VARCHAR(150) NOT NULL,
    details           TEXT NULL,
    status            ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolved_by       INT UNSIGNED NULL,
    resolved_at       TIMESTAMP NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id)      REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_reported FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_ride     FOREIGN KEY (ride_id)          REFERENCES rides(id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_resolver FOREIGN KEY (resolved_by)      REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SEED / DEMO DATA
-- Passwords: Admin@123 / Driver@123 / Rider@123 (bcrypt hashes below), same
-- password shared across all seeded accounts of a role, documented in
-- README.md so an examiner can log in immediately as any persona.
-- ============================================================================

-- ==================== USERS (1 admin, 15 drivers, 20 riders) ====================
INSERT INTO users (id, name, email, phone, password, role, status) VALUES
(1, 'Platform Admin', 'admin@transitpool.test', '9800000001', '$2y$12$3aLWgtBDQQGhYXEdC8hcbu/8DSlSeaYTFSoJDFeMY55Xiw/IdEDcy', 'super_admin', 'active'),
(2, 'Arjun Mehta', 'driver1@transitpool.test', '9810000001', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(3, 'Priya Sharma', 'driver2@transitpool.test', '9810000002', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(4, 'Rohit Kulkarni', 'driver3@transitpool.test', '9810000003', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(5, 'Sneha Patil', 'driver4@transitpool.test', '9810000004', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(6, 'Vikram Rao', 'driver5@transitpool.test', '9810000005', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(7, 'Ananya Iyer', 'driver6@transitpool.test', '9810000006', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(8, 'Karan Deshmukh', 'driver7@transitpool.test', '9810000007', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(9, 'Meera Nair', 'driver8@transitpool.test', '9810000008', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(10, 'Suresh Reddy', 'driver9@transitpool.test', '9810000009', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(11, 'Divya Menon', 'driver10@transitpool.test', '9810000010', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(12, 'Aditya Joshi', 'driver11@transitpool.test', '9810000011', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(13, 'Neha Kapoor', 'driver12@transitpool.test', '9810000012', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(14, 'Manoj Pillai', 'driver13@transitpool.test', '9810000013', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(15, 'Kavya Shetty', 'driver14@transitpool.test', '9810000014', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'active'),
(16, 'Ravi Verma', 'driver15@transitpool.test', '9810000015', '$2y$12$eoJxhQ6Egj22cPNmqSxfwOpos0VZHgPML5UWqbcn/qDHRd2SLOmrG', 'driver', 'inactive'),
(17, 'Ishaan Gupta', 'rider1@transitpool.test', '9720000001', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(18, 'Tanvi Rao', 'rider2@transitpool.test', '9720000002', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(19, 'Aman Singh', 'rider3@transitpool.test', '9720000003', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(20, 'Pooja Bhatt', 'rider4@transitpool.test', '9720000004', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(21, 'Rahul Nanda', 'rider5@transitpool.test', '9720000005', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(22, 'Simran Kaur', 'rider6@transitpool.test', '9720000006', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(23, 'Yash Malhotra', 'rider7@transitpool.test', '9720000007', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(24, 'Nikita Bose', 'rider8@transitpool.test', '9720000008', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(25, 'Devansh Shah', 'rider9@transitpool.test', '9720000009', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(26, 'Riya Chawla', 'rider10@transitpool.test', '9720000010', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(27, 'Aryan Kapoor', 'rider11@transitpool.test', '9720000011', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(28, 'Sanya Mehra', 'rider12@transitpool.test', '9720000012', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(29, 'Kabir Khanna', 'rider13@transitpool.test', '9720000013', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(30, 'Anushka Rana', 'rider14@transitpool.test', '9720000014', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(31, 'Varun Saxena', 'rider15@transitpool.test', '9720000015', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(32, 'Ishita Bansal', 'rider16@transitpool.test', '9720000016', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(33, 'Siddharth Rao', 'rider17@transitpool.test', '9720000017', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(34, 'Tara Fernandes', 'rider18@transitpool.test', '9720000018', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'active'),
(35, 'Om Prakash', 'rider19@transitpool.test', '9720000019', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'inactive'),
(36, 'Larissa Dsouza', 'rider20@transitpool.test', '9720000020', '$2y$12$BD5Itdkgyy97b8CHDfS9jepWJ.5T/66bK8pljQsImnJ3tvFfkUOCO', 'rider', 'inactive');

-- ==================== VEHICLES (16 total) ====================
INSERT INTO vehicles (id, driver_id, make, model, color, plate_number, vehicle_type, total_seats, status) VALUES
(1, 2, 'Maruti Suzuki', 'Swift', 'Silver', 'KA02BE1001', 'hatchback', 3, 'active'),
(2, 3, 'Hyundai', 'i20', 'Grey', 'KA03CF1002', 'hatchback', 3, 'active'),
(3, 4, 'Honda', 'City', 'Red', 'KA04DG1003', 'sedan', 3, 'active'),
(4, 5, 'Toyota', 'Innova Crysta', 'Blue', 'KA05EH1004', 'suv', 6, 'active'),
(5, 6, 'Maruti Suzuki', 'Dzire', 'Black', 'KA06FI1005', 'sedan', 3, 'active'),
(6, 7, 'Tata', 'Nexon', 'Maroon', 'KA07GJ1006', 'suv', 4, 'inactive'),
(7, 8, 'Mahindra', 'XUV700', 'White', 'KA08HK1007', 'suv', 5, 'active'),
(8, 9, 'Hyundai', 'Verna', 'Silver', 'KA09IL1008', 'sedan', 3, 'active'),
(9, 10, 'Kia', 'Seltos', 'Grey', 'KA10JM1009', 'suv', 4, 'active'),
(10, 11, 'Honda', 'Activa', 'Red', 'KA11KN1010', 'bike', 1, 'active'),
(11, 12, 'Royal Enfield', 'Classic 350', 'Blue', 'KA12LO1011', 'bike', 1, 'active'),
(12, 13, 'Maruti Suzuki', 'Ertiga', 'Black', 'KA13MP1012', 'suv', 5, 'active'),
(13, 14, 'Toyota', 'Glanza', 'Maroon', 'KA14NQ1013', 'hatchback', 3, 'active'),
(14, 15, 'Hyundai', 'Creta', 'White', 'KA15OR1014', 'suv', 4, 'active'),
(15, 16, 'Tata', 'Tiago', 'Silver', 'KA16PS1015', 'hatchback', 3, 'inactive'),
(16, 9, 'Tata', 'Altroz', 'White', 'KA05MZ9911', 'hatchback', 3, 'active');

-- ==================== RIDES (25 total: 12 scheduled, 2 ongoing, 9 completed, 2 cancelled) ====================
INSERT INTO rides (id, driver_id, vehicle_id, origin, destination, departure_at, seats_total, seats_available, price_per_seat, notes, status) VALUES
(1, 2, 1, 'Koramangala, Bengaluru', 'Whitefield, Bengaluru', '2026-09-08 07:00:00', 2, 0, 150.00, 'AC car, no smoking, one medium bag per seat.', 'scheduled'),
(2, 3, 2, 'Whitefield, Bengaluru', 'Electronic City, Bengaluru', '2026-09-09 08:15:00', 2, 1, 180.00, NULL, 'scheduled'),
(3, 4, 3, 'Indiranagar, Bengaluru', 'Kempegowda Airport, Bengaluru', '2026-09-10 09:30:00', 2, 0, 200.00, NULL, 'scheduled'),
(4, 5, 4, 'Andheri, Mumbai', 'Bandra Kurla Complex, Mumbai', '2026-09-11 10:45:00', 4, 3, 220.00, 'AC car, no smoking, one medium bag per seat.', 'scheduled'),
(5, 6, 5, 'Powai, Mumbai', 'Navi Mumbai', '2026-09-12 11:00:00', 2, 0, 250.00, NULL, 'scheduled'),
(6, 2, 1, 'Connaught Place, Delhi', 'Gurgaon Cyber Hub, Gurgaon', '2026-09-13 12:15:00', 2, 0, 280.00, NULL, 'scheduled'),
(7, 8, 7, 'Dwarka, Delhi', 'Noida Sector 62, Noida', '2026-09-14 13:30:00', 4, 2, 300.00, 'AC car, no smoking, one medium bag per seat.', 'scheduled'),
(8, 9, 8, 'Hinjewadi, Pune', 'Pune Railway Station, Pune', '2026-09-15 14:45:00', 2, 1, 350.00, NULL, 'scheduled'),
(9, 10, 9, 'Kothrud, Pune', 'Mumbai', '2026-09-16 15:00:00', 3, 0, 400.00, NULL, 'scheduled'),
(10, 11, 10, 'Hitech City, Hyderabad', 'Secunderabad, Hyderabad', '2026-09-17 16:15:00', 2, 1, 450.00, 'AC car, no smoking, one medium bag per seat.', 'scheduled'),
(11, 12, 11, 'Gachibowli, Hyderabad', 'Vijayawada', '2026-09-08 07:30:00', 2, 0, 500.00, NULL, 'scheduled'),
(12, 13, 12, 'T Nagar, Chennai', 'Chennai Airport, Chennai', '2026-09-09 08:45:00', 4, 1, 150.00, NULL, 'scheduled'),
(13, 14, 13, 'OMR, Chennai', 'Coimbatore', '2026-09-07 08:00:00', 2, 0, 180.00, 'AC car, no smoking, one medium bag per seat.', 'ongoing'),
(14, 15, 14, 'Kakkanad, Kochi', 'Bengaluru', '2026-09-07 08:00:00', 3, 0, 200.00, NULL, 'ongoing'),
(15, 2, 1, 'Salt Lake, Kolkata', 'Howrah, Kolkata', '2026-08-22 13:30:00', 2, 0, 220.00, NULL, 'completed'),
(16, 2, 1, 'Koramangala, Bengaluru', 'Whitefield, Bengaluru', '2026-08-21 14:45:00', 2, 0, 250.00, 'AC car, no smoking, one medium bag per seat.', 'completed'),
(17, 3, 2, 'Whitefield, Bengaluru', 'Electronic City, Bengaluru', '2026-08-20 15:00:00', 2, 0, 280.00, NULL, 'completed'),
(18, 4, 3, 'Indiranagar, Bengaluru', 'Kempegowda Airport, Bengaluru', '2026-08-19 16:15:00', 2, 1, 300.00, NULL, 'completed'),
(19, 5, 4, 'Andheri, Mumbai', 'Bandra Kurla Complex, Mumbai', '2026-08-18 08:30:00', 4, 1, 350.00, 'AC car, no smoking, one medium bag per seat.', 'completed'),
(20, 6, 5, 'Powai, Mumbai', 'Navi Mumbai', '2026-08-17 09:45:00', 2, 0, 400.00, NULL, 'completed'),
(21, 2, 1, 'Connaught Place, Delhi', 'Gurgaon Cyber Hub, Gurgaon', '2026-09-05 10:00:00', 2, 0, 450.00, NULL, 'completed'),
(22, 8, 7, 'Dwarka, Delhi', 'Noida Sector 62, Noida', '2026-09-04 11:15:00', 4, 1, 500.00, 'AC car, no smoking, one medium bag per seat.', 'completed'),
(23, 9, 8, 'Hinjewadi, Pune', 'Pune Railway Station, Pune', '2026-09-03 12:30:00', 2, 0, 150.00, NULL, 'completed'),
(24, 10, 9, 'Kothrud, Pune', 'Mumbai', '2026-10-03 09:00:00', 3, 3, 180.00, NULL, 'cancelled'),
(25, 11, 10, 'Hitech City, Hyderabad', 'Secunderabad, Hyderabad', '2026-10-04 09:00:00', 2, 2, 200.00, 'AC car, no smoking, one medium bag per seat.', 'cancelled');

-- ==================== BOOKINGS (32 total) ====================
INSERT INTO bookings (id, ride_id, rider_id, seats_booked, fare_per_seat, total_fare, status, booked_at) VALUES
(1, 1, 17, 2, 150.00, 300.00, 'confirmed', '2026-09-07 07:00:00'),
(2, 2, 18, 1, 180.00, 180.00, 'confirmed', '2026-09-08 08:15:00'),
(3, 3, 19, 2, 200.00, 400.00, 'confirmed', '2026-09-09 09:30:00'),
(4, 4, 20, 1, 220.00, 220.00, 'confirmed', '2026-09-10 10:45:00'),
(5, 5, 21, 2, 250.00, 500.00, 'confirmed', '2026-09-11 11:00:00'),
(6, 6, 22, 1, 280.00, 280.00, 'confirmed', '2026-09-12 12:15:00'),
(7, 6, 23, 1, 280.00, 280.00, 'confirmed', '2026-09-11 12:15:00'),
(8, 7, 24, 2, 300.00, 600.00, 'confirmed', '2026-09-13 13:30:00'),
(9, 8, 25, 1, 350.00, 350.00, 'confirmed', '2026-09-14 14:45:00'),
(10, 9, 26, 2, 400.00, 800.00, 'confirmed', '2026-09-15 15:00:00'),
(11, 9, 27, 1, 400.00, 400.00, 'confirmed', '2026-09-14 15:00:00'),
(12, 10, 28, 1, 450.00, 450.00, 'confirmed', '2026-09-16 16:15:00'),
(13, 11, 29, 2, 500.00, 1000.00, 'confirmed', '2026-09-07 07:30:00'),
(14, 12, 30, 1, 150.00, 150.00, 'confirmed', '2026-09-08 08:45:00'),
(15, 12, 31, 2, 150.00, 300.00, 'confirmed', '2026-09-07 08:45:00'),
(16, 13, 32, 2, 180.00, 360.00, 'confirmed', '2026-09-06 08:00:00'),
(17, 14, 33, 1, 200.00, 200.00, 'confirmed', '2026-09-06 08:00:00'),
(18, 14, 34, 2, 200.00, 400.00, 'confirmed', '2026-09-05 08:00:00'),
(19, 15, 17, 2, 220.00, 440.00, 'confirmed', '2026-08-21 13:30:00'),
(20, 16, 18, 1, 250.00, 250.00, 'confirmed', '2026-08-20 14:45:00'),
(21, 16, 19, 1, 250.00, 250.00, 'confirmed', '2026-08-19 14:45:00'),
(22, 17, 20, 2, 280.00, 560.00, 'confirmed', '2026-08-19 15:00:00'),
(23, 18, 21, 1, 300.00, 300.00, 'confirmed', '2026-08-18 16:15:00'),
(24, 19, 22, 2, 350.00, 700.00, 'confirmed', '2026-08-17 08:30:00'),
(25, 19, 23, 1, 350.00, 350.00, 'confirmed', '2026-08-16 08:30:00'),
(26, 20, 24, 1, 400.00, 400.00, 'confirmed', '2026-08-16 09:45:00'),
(27, 20, 25, 1, 400.00, 400.00, 'confirmed', '2026-08-15 09:45:00'),
(28, 21, 26, 2, 450.00, 900.00, 'confirmed', '2026-09-04 10:00:00'),
(29, 22, 27, 1, 500.00, 500.00, 'confirmed', '2026-09-03 11:15:00'),
(30, 22, 28, 2, 500.00, 1000.00, 'confirmed', '2026-09-02 11:15:00'),
(31, 23, 29, 2, 150.00, 300.00, 'confirmed', '2026-09-02 12:30:00'),
(32, 25, 30, 1, 200.00, 200.00, 'cancelled', '2026-10-02 09:00:00');

-- ==================== RATINGS (19 total, post-ride, both directions) ====================
INSERT INTO ratings (id, ride_id, rater_id, ratee_id, rater_role, stars, comment) VALUES
(1, 15, 2, 17, 'driver', 5, 'Smooth ride, would carpool again.'),
(2, 15, 17, 2, 'rider', 4, NULL),
(3, 16, 2, 18, 'driver', 5, 'Punctual and courteous throughout.'),
(4, 16, 18, 2, 'rider', 5, NULL),
(5, 16, 2, 19, 'driver', 4, 'Good conversation, safe driving.'),
(6, 16, 19, 2, 'rider', 5, 'A bit late to the pickup point but otherwise fine.'),
(7, 17, 3, 20, 'driver', 3, NULL),
(8, 19, 5, 22, 'driver', 4, 'A bit late to the pickup point but otherwise fine.'),
(9, 19, 22, 5, 'rider', 5, 'Smooth ride, would carpool again.'),
(10, 19, 5, 23, 'driver', 5, 'Clean car, easy trip.'),
(11, 19, 23, 5, 'rider', 4, 'Punctual and courteous throughout.'),
(12, 20, 6, 24, 'driver', 5, 'Great co-passenger, on time and friendly.'),
(13, 20, 6, 25, 'driver', 4, 'Smooth ride, would carpool again.'),
(14, 20, 25, 6, 'rider', 5, NULL),
(15, 22, 8, 27, 'driver', 5, 'Good conversation, safe driving.'),
(16, 22, 27, 8, 'rider', 3, 'A bit late to the pickup point but otherwise fine.'),
(17, 22, 8, 28, 'driver', 4, NULL),
(18, 23, 9, 29, 'driver', 5, NULL),
(19, 23, 29, 9, 'rider', 4, 'Great co-passenger, on time and friendly.');

-- ==================== REPORTS (8 total, moderation queue) ====================
INSERT INTO reports (reporter_id, reported_user_id, ride_id, reason, details, status, resolved_by, resolved_at) VALUES
(17, 3, 15, 'Driver arrived 30 minutes late', 'Waited at the pickup point with no update until the driver finally called.', 'resolved', 1, '2026-08-20 10:00:00'),
(20, NULL, 2, 'Vehicle did not match listing', 'Booked a sedan, an unlisted hatchback showed up instead.', 'dismissed', 1, '2026-08-20 10:00:00'),
(4, 19, 16, 'Rider no-show', 'Rider did not show up at the pickup point and did not cancel in advance.', 'resolved', 1, '2026-08-20 10:00:00'),
(22, 6, 17, 'Unsafe driving', 'Driver was over speed limit for most of the trip.', 'open', NULL, NULL),
(9, 25, 18, 'Payment dispute', 'Rider paid for 1 seat but had booked 2.', 'open', NULL, NULL),
(28, 11, 19, 'Smoking in the car', 'Driver smoked during the ride despite no-smoking note.', 'resolved', 1, '2026-08-20 10:00:00'),
(13, NULL, NULL, 'Suspicious profile', 'A rider profile with a clearly fake name kept messaging outside the app.', 'dismissed', 1, '2026-08-20 10:00:00'),
(31, 8, 20, 'Rude behaviour', 'Driver was dismissive when asked to stop for a water break.', 'open', NULL, NULL);

-- Keep AUTO_INCREMENT counters ahead of the explicit seed IDs above so the
-- app's own INSERTs (no explicit id) never collide with seed rows.
ALTER TABLE users    AUTO_INCREMENT = 37;
ALTER TABLE vehicles AUTO_INCREMENT = 17;
ALTER TABLE rides    AUTO_INCREMENT = 26;
ALTER TABLE bookings AUTO_INCREMENT = 33;
ALTER TABLE ratings  AUTO_INCREMENT = 20;
ALTER TABLE reports  AUTO_INCREMENT = 9;

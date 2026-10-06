-- =====================================================
-- Hotel Room Booking System - MySQL / MariaDB schema
-- Import via phpMyAdmin: Import tab > choose this file > Go
-- =====================================================

CREATE DATABASE IF NOT EXISTS hotel_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE hotel_db;

-- Drop in dependency order (safe re-import)
DROP VIEW  IF EXISTS vw_booking_list;
DROP TABLE IF EXISTS booking_services;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS guests;
DROP TABLE IF EXISTS rooms;

-- -----------------------------------------------------
-- 1. ROOMS
-- -----------------------------------------------------
CREATE TABLE rooms (
  room_id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_number     VARCHAR(10)  NOT NULL,
  room_type       ENUM('Single','Double','Suite') NOT NULL,
  price_per_night DECIMAL(10,2) NOT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (room_id),
  UNIQUE KEY uq_room_number (room_number),
  CONSTRAINT chk_room_price CHECK (price_per_night > 0)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 2. GUESTS
-- -----------------------------------------------------
CREATE TABLE guests (
  guest_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name  VARCHAR(60)  NOT NULL,
  last_name   VARCHAR(60)  NOT NULL,
  email       VARCHAR(120) NULL,
  phone       VARCHAR(20)  NOT NULL,
  address     VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (guest_id),
  UNIQUE KEY uq_guest_email (email),
  KEY idx_guest_name (last_name, first_name)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. SERVICES (breakfast, spa, airport pickup ...)
-- -----------------------------------------------------
CREATE TABLE services (
  service_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(100) NOT NULL,
  description  VARCHAR(255) NULL,
  price        DECIMAL(10,2) NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (service_id),
  UNIQUE KEY uq_service_name (name),
  CONSTRAINT chk_service_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. BOOKINGS
-- status: Booked -> Checked-In -> Checked-Out, or Cancelled
-- room_price_per_night is a snapshot so later price edits
-- don't change old bookings' totals.
-- -----------------------------------------------------
CREATE TABLE bookings (
  booking_id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  guest_id             INT UNSIGNED NOT NULL,
  room_id              INT UNSIGNED NOT NULL,
  check_in_date        DATE NOT NULL,
  check_out_date       DATE NOT NULL,
  status               ENUM('Booked','Checked-In','Checked-Out','Cancelled')
                       NOT NULL DEFAULT 'Booked',
  room_price_per_night DECIMAL(10,2) NOT NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (booking_id),
  KEY idx_booking_guest (guest_id),
  KEY idx_booking_room_dates (room_id, check_in_date, check_out_date),
  KEY idx_booking_checkin (check_in_date),
  KEY idx_booking_status (status),
  CONSTRAINT fk_booking_guest FOREIGN KEY (guest_id)
    REFERENCES guests (guest_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_booking_room FOREIGN KEY (room_id)
    REFERENCES rooms (room_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_booking_dates CHECK (check_out_date > check_in_date)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. BOOKING_SERVICES (many-to-many: booking <-> service)
-- price_at_booking is a snapshot of the service price.
-- -----------------------------------------------------
CREATE TABLE booking_services (
  booking_service_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  booking_id         INT UNSIGNED NOT NULL,
  service_id         INT UNSIGNED NOT NULL,
  quantity           INT UNSIGNED NOT NULL DEFAULT 1,
  price_at_booking   DECIMAL(10,2) NOT NULL,
  added_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (booking_service_id),
  KEY idx_bs_booking (booking_id),
  KEY idx_bs_service (service_id),
  CONSTRAINT fk_bs_booking FOREIGN KEY (booking_id)
    REFERENCES bookings (booking_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_bs_service FOREIGN KEY (service_id)
    REFERENCES services (service_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_bs_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- VIEW: booking list with guest name, room number, total
-- total = (nights x room price) + SUM(service price x qty)
-- is_active = checked in, not yet checked out
-- -----------------------------------------------------
CREATE VIEW vw_booking_list AS
SELECT
  b.booking_id,
  b.guest_id,
  CONCAT(g.first_name, ' ', g.last_name) AS guest_name,
  b.room_id,
  r.room_number,
  r.room_type,
  b.check_in_date,
  b.check_out_date,
  DATEDIFF(b.check_out_date, b.check_in_date) AS nights,
  b.status,
  (b.status = 'Checked-In') AS is_active,
  DATEDIFF(b.check_out_date, b.check_in_date) * b.room_price_per_night AS room_cost,
  COALESCE(s.services_cost, 0) AS services_cost,
  DATEDIFF(b.check_out_date, b.check_in_date) * b.room_price_per_night
    + COALESCE(s.services_cost, 0) AS total_amount
FROM bookings b
JOIN guests g ON g.guest_id = b.guest_id
JOIN rooms  r ON r.room_id  = b.room_id
LEFT JOIN (
  SELECT booking_id, SUM(price_at_booking * quantity) AS services_cost
  FROM booking_services
  GROUP BY booking_id
) s ON s.booking_id = b.booking_id;

-- -----------------------------------------------------
-- SAMPLE DATA (optional - delete if not needed)
-- -----------------------------------------------------
INSERT INTO rooms (room_number, room_type, price_per_night) VALUES
  ('101','Single',2500.00), ('102','Double',4000.00), ('201','Suite',9000.00);

INSERT INTO guests (first_name, last_name, email, phone, address) VALUES
  ('Amit','Shah','amit@example.com','9876543210','Ahmedabad'),
  ('Neha','Patel','neha@example.com','9123456780','Surat');

INSERT INTO services (name, description, price) VALUES
  ('Breakfast','Buffet breakfast per person',500.00),
  ('Spa','60 minute massage',2500.00),
  ('Airport Pickup','One-way airport transfer',1200.00);

/* =====================================================
   USEFUL QUERIES FOR YOUR PHP CODE
   (? = value from your form / prepared statement)
   =====================================================

-- A) OVERLAP CHECK before booking (0 rows = room is free)
SELECT booking_id FROM bookings
WHERE room_id = ?
  AND status IN ('Booked','Checked-In')
  AND check_in_date  < ?   -- new check-out
  AND check_out_date > ?;  -- new check-in
-- (when updating a booking, add: AND booking_id <> ?)

-- B) DELETE ROOM only if no active/upcoming bookings (0 = safe)
SELECT COUNT(*) FROM bookings
WHERE room_id = ?
  AND status IN ('Booked','Checked-In')
  AND check_out_date >= CURDATE();

-- C) ROOM LIST with availability today
SELECT r.*,
  CASE WHEN EXISTS (
    SELECT 1 FROM bookings b
    WHERE b.room_id = r.room_id
      AND b.status IN ('Booked','Checked-In')
      AND CURDATE() >= b.check_in_date
      AND CURDATE() <  b.check_out_date
  ) THEN 'Occupied' ELSE 'Available' END AS availability
FROM rooms r;

-- D) FILTER BOOKINGS (by guest, room, check-in date)
SELECT * FROM vw_booking_list WHERE guest_id = ?;
SELECT * FROM vw_booking_list WHERE room_id = ?;
SELECT * FROM vw_booking_list WHERE check_in_date = ?;

-- E) ACTIVE BOOKINGS (checked in, not yet checked out)
SELECT * FROM vw_booking_list WHERE status = 'Checked-In';

-- F) ADD SERVICE TO BOOKING (copy current price as snapshot)
INSERT INTO booking_services (booking_id, service_id, quantity, price_at_booking)
SELECT ?, service_id, ?, price FROM services WHERE service_id = ?;

-- G) DASHBOARD
SELECT COUNT(*) AS total_rooms  FROM rooms;
SELECT COUNT(*) AS total_guests FROM guests;
SELECT COUNT(DISTINCT room_id) AS occupied_today FROM bookings
  WHERE status IN ('Booked','Checked-In')
    AND CURDATE() >= check_in_date AND CURDATE() < check_out_date;
SELECT COUNT(*) AS todays_checkins  FROM bookings
  WHERE check_in_date  = CURDATE() AND status <> 'Cancelled';
SELECT COUNT(*) AS todays_checkouts FROM bookings
  WHERE check_out_date = CURDATE() AND status <> 'Cancelled';
*/

-- ============================================================
--  EXPLORE BANGLADESH — Smart Tourism Management System
--  DBMS Lab Project | Database Schema
-- ============================================================

DROP DATABASE IF EXISTS explore_bangladesh;
CREATE DATABASE explore_bangladesh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE explore_bangladesh;

-- ------------------------------------------------------------
-- 1. GEOGRAPHY
-- ------------------------------------------------------------
CREATE TABLE divisions (
    division_id   INT AUTO_INCREMENT PRIMARY KEY,
    division_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE districts (
    district_id   INT AUTO_INCREMENT PRIMARY KEY,
    district_name VARCHAR(50) NOT NULL,
    division_id   INT NOT NULL,
    FOREIGN KEY (division_id) REFERENCES divisions(division_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 2. CATEGORIES & DESTINATIONS
-- ------------------------------------------------------------
CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,   -- Mountain, Sea, Heritage
    icon          VARCHAR(20)                    -- emoji / icon class
);

CREATE TABLE destinations (
    destination_id   INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    category_id      INT NOT NULL,
    district_id      INT NOT NULL,
    description      TEXT,
    best_time_to_visit VARCHAR(150),
    entry_fee        DECIMAL(8,2) DEFAULT 0,
    opening_hours    VARCHAR(100),
    latitude         DECIMAL(10,7),               -- needed for weather API calls
    longitude        DECIMAL(10,7),
    map_link         VARCHAR(255),
    safety_tips      TEXT,
    is_outdoor       TINYINT(1) DEFAULT 1,         -- 1 = outdoor (weather-sensitive), 0 = indoor/covered
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (district_id) REFERENCES districts(district_id)
);

CREATE TABLE images (
    image_id       INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    image_url      VARCHAR(255) NOT NULL,
    caption        VARCHAR(150),
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

CREATE TABLE attractions (
    attraction_id   INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    attraction_name VARCHAR(100) NOT NULL,
    distance_km     DECIMAL(5,2),
    description     VARCHAR(255),
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 3. TRANSPORT
-- ------------------------------------------------------------
CREATE TABLE transport (
    transport_id   INT AUTO_INCREMENT PRIMARY KEY,
    transport_type ENUM('Bus','Train','Flight','Launch') NOT NULL,
    operator_name  VARCHAR(100) NOT NULL,
    contact_no     VARCHAR(30)
);

CREATE TABLE transport_routes (
    route_id        INT AUTO_INCREMENT PRIMARY KEY,
    transport_id    INT NOT NULL,
    destination_id  INT NOT NULL,
    origin          VARCHAR(100) NOT NULL,
    stop_over       VARCHAR(100),                  -- e.g. Teknaf before Saint Martin
    estimated_time  VARCHAR(50),
    estimated_cost  DECIMAL(8,2),
    schedule_info   VARCHAR(150),
    FOREIGN KEY (transport_id) REFERENCES transport(transport_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 4. USERS & ADMIN
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    phone       VARCHAR(20),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin (
    admin_id   INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL
);

CREATE TABLE favourites (
    favourite_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    destination_id INT NOT NULL,
    saved_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE,
    UNIQUE(user_id, destination_id)
);

-- ------------------------------------------------------------
-- 5. HOTELS
-- ------------------------------------------------------------
CREATE TABLE hotels (
    hotel_id        INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    hotel_name      VARCHAR(100) NOT NULL,
    hotel_type      ENUM('Hotel','Resort','Guest House') DEFAULT 'Hotel',
    price_range_min DECIMAL(8,2),
    price_range_max DECIMAL(8,2),
    rating          DECIMAL(2,1) DEFAULT 0,          -- 0.0 - 5.0
    contact_no      VARCHAR(30),
    address         VARCHAR(200),
    free_breakfast  TINYINT(1) DEFAULT 0,             -- feature #4
    swimming_pool   TINYINT(1) DEFAULT 0,             -- feature #4
    distance_from_center_km DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

CREATE TABLE hotel_rooms (
    room_id      INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id     INT NOT NULL,
    room_type    VARCHAR(50) NOT NULL,               -- Single, Double, Deluxe, Suite
    price        DECIMAL(8,2) NOT NULL,
    total_rooms  INT DEFAULT 1,
    available_rooms INT DEFAULT 1,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE
);

CREATE TABLE hotel_bookings (
    booking_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    room_id       INT NOT NULL,
    check_in      DATE NOT NULL,
    check_out     DATE NOT NULL,
    guests        INT DEFAULT 1,
    total_price   DECIMAL(9,2),
    status        ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
    booked_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES hotel_rooms(room_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 6. TICKET BOOKING
-- ------------------------------------------------------------
CREATE TABLE ticket_bookings (
    ticket_booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    route_id          INT NOT NULL,
    travel_date       DATE NOT NULL,
    seats             INT DEFAULT 1,
    total_price       DECIMAL(9,2),
    status            ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
    weather_warning_shown TINYINT(1) DEFAULT 0,        -- feature #7 audit flag
    booked_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES transport_routes(route_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- 7. REVIEWS & RATINGS
-- ------------------------------------------------------------
CREATE TABLE reviews (
    review_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    destination_id INT NOT NULL,
    review_text    TEXT,
    photo_url      VARCHAR(255),
    is_verified    TINYINT(1) DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

CREATE TABLE ratings (
    rating_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    destination_id INT NOT NULL,
    stars          TINYINT NOT NULL CHECK (stars BETWEEN 1 AND 5),
    rated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE,
    UNIQUE(user_id, destination_id)
);

-- ------------------------------------------------------------
-- 8. WEATHER MODULE  (core new feature set)
-- ------------------------------------------------------------
-- Caches OpenWeatherMap responses per destination so we don't
-- call the API on every page load (free-tier rate limits).
CREATE TABLE weather_logs (
    weather_log_id  INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    forecast_date   DATE NOT NULL,
    condition_main  VARCHAR(50),          -- Clear, Rain, Thunderstorm, Clouds...
    description     VARCHAR(100),
    temp_min        DECIMAL(4,1),
    temp_max        DECIMAL(4,1),
    rain_probability DECIMAL(5,2),        -- %
    wind_speed      DECIMAL(5,2),
    weather_score   TINYINT,              -- 0-100, higher = better travel weather
    fetched_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE,
    UNIQUE(destination_id, forecast_date)
);

-- feature #6: weather-wise food/restaurant suggestion
CREATE TABLE restaurants (
    restaurant_id   INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    restaurant_name VARCHAR(100) NOT NULL,
    address         VARCHAR(200),
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    rating          DECIMAL(2,1) DEFAULT 0,
    map_link        VARCHAR(255),
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

CREATE TABLE food_items (
    food_item_id    INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id   INT NOT NULL,
    item_name       VARCHAR(100) NOT NULL,
    suitable_weather ENUM('Sunny','Rainy','Cold','Any') DEFAULT 'Any',
    price           DECIMAL(7,2),
    item_rating     DECIMAL(2,1) DEFAULT 0,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id) ON DELETE CASCADE
);

-- feature #8: shared vehicle rental between tourists
CREATE TABLE shared_rides (
    ride_id         INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    created_by      INT NOT NULL,               -- user_id
    pickup_point    VARCHAR(150) NOT NULL,
    drop_point      VARCHAR(150) NOT NULL,
    ride_datetime   DATETIME NOT NULL,
    total_fare      DECIMAL(8,2),
    seats_total     INT DEFAULT 4,
    seats_taken     INT DEFAULT 1,
    status          ENUM('Open','Full','Completed','Cancelled') DEFAULT 'Open',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE shared_ride_members (
    member_id  INT AUTO_INCREMENT PRIMARY KEY,
    ride_id    INT NOT NULL,
    user_id    INT NOT NULL,
    joined_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES shared_rides(ride_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE(ride_id, user_id)
);

-- feature #9: nearby essential services
CREATE TABLE nearby_services (
    service_id      INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    service_type    ENUM('Hospital','Medical Shop','Flower Shop','Police Station','ATM','Other') NOT NULL,
    service_name    VARCHAR(100) NOT NULL,
    address         VARCHAR(200),
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    contact_no      VARCHAR(30),
    rating          DECIMAL(2,1) DEFAULT 0,
    map_link        VARCHAR(255),
    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- INDEXES for common lookups
-- ------------------------------------------------------------
CREATE INDEX idx_destinations_category ON destinations(category_id);
CREATE INDEX idx_destinations_district ON destinations(district_id);
CREATE INDEX idx_weather_destination_date ON weather_logs(destination_id, forecast_date);
CREATE INDEX idx_hotels_destination ON hotels(destination_id);
CREATE INDEX idx_routes_destination ON transport_routes(destination_id);

-- ============================================================
--  SAMPLE DATA
-- ============================================================

INSERT INTO divisions (division_name) VALUES
('Chattogram'),('Sylhet'),('Barisal'),('Rajshahi'),('Khulna'),('Dhaka'),('Rangpur'),('Mymensingh');

INSERT INTO districts (district_name, division_id) VALUES
('Cox''s Bazar', 1),('Bandarban', 1),('Rangamati', 1),('Khagrachari', 1),
('Sylhet', 2),('Moulvibazar', 2),
('Patuakhali', 3),
('Naogaon', 4),
('Bagerhat', 5),
('Narayanganj', 6),('Dhaka', 6);

INSERT INTO categories (category_name, icon) VALUES
('Mountain','🏔️'),('Sea','🌊'),('Heritage','🏛️');

INSERT INTO destinations
(name, category_id, district_id, description, best_time_to_visit, entry_fee, opening_hours, latitude, longitude, map_link, safety_tips, is_outdoor) VALUES
('Sajek Valley', 1, 3, 'A cloud-kissed hill valley on the Bangladesh-India border, famous for rolling clouds and sunrise views.', 'October to March', 0, '24 hours', 23.3833, 92.2833, 'https://maps.google.com/?q=Sajek+Valley', 'Roads are steep; hire local guides for treks.', 1),
('Nilgiri', 1, 2, 'A hilltop resort area in Bandarban offering panoramic views above the clouds.', 'November to February', 100, '9:00 AM - 5:00 PM', 21.8167, 92.3667, 'https://maps.google.com/?q=Nilgiri+Bandarban', 'Carry warm clothes; roads can be foggy.', 1),
('Cox''s Bazar Beach', 2, 1, 'The world''s longest natural sea beach, stretching about 120 km along the Bay of Bengal.', 'November to February', 0, '24 hours', 21.4272, 92.0058, 'https://maps.google.com/?q=Cox%27s+Bazar+Beach', 'Avoid swimming during red-flag warnings and high tide.', 1),
('Saint Martin Island', 2, 1, 'Bangladesh''s only coral island, reachable by ship from Teknaf.', 'November to February', 0, '24 hours', 20.6280, 92.3220, 'https://maps.google.com/?q=Saint+Martin+Island', 'Ferries stop during monsoon; check sea conditions before travel.', 1),
('Kuakata Sea Beach', 2, 7, 'Known as "Sagar Kannya", one of the few places to see both sunrise and sunset over the sea.', 'October to March', 0, '24 hours', 21.8153, 90.1197, 'https://maps.google.com/?q=Kuakata+Sea+Beach', 'Currents can be strong; swim only in marked zones.', 1),
('Paharpur Buddhist Vihara', 3, 8, 'UNESCO World Heritage Site — ruins of one of the largest Buddhist monasteries south of the Himalayas.', 'October to March', 200, '9:00 AM - 6:00 PM', 25.0311, 88.9773, 'https://maps.google.com/?q=Paharpur', 'Stay on marked paths to protect the ruins.', 0),
('Shat Gombuj Mosque', 3, 9, 'A 15th-century UNESCO World Heritage mosque in Bagerhat with 77 domes.', 'Year-round', 200, '9:00 AM - 5:00 PM', 22.6583, 89.7500, 'https://maps.google.com/?q=Shat+Gombuj+Mosque', 'Dress modestly; it is an active place of worship.', 0);

INSERT INTO transport (transport_type, operator_name, contact_no) VALUES
('Bus','Green Line Paribahan','01711-000000'),
('Bus','Shohagh Paribahan','01711-000001'),
('Launch','Keari Sindbad','01711-000002'),
('Train','Bangladesh Railway','01711-000003'),
('Flight','Novoair','01711-000004');

INSERT INTO transport_routes (transport_id, destination_id, origin, stop_over, estimated_time, estimated_cost, schedule_info) VALUES
(1, 3, 'Dhaka', NULL, '8-9 hours', 1200, 'Every 2 hours, 8 AM - 11 PM'),
(3, 4, 'Teknaf', 'Cox''s Bazar', '2.5-3 hours', 1200, 'Departs 9:30 AM (Nov-Feb only)'),
(5, 3, 'Dhaka', NULL, '55 minutes', 4500, '3 flights daily'),
(2, 1, 'Khagrachari', NULL, '3 hours (jeep)', 800, 'Convoy system, 10 AM & 3 PM only'),
(4, 6, 'Dhaka (Rajshahi line)', 'Naogaon', '5-6 hours', 350, 'Departs 7:10 AM daily');

INSERT INTO hotels (destination_id, hotel_name, hotel_type, price_range_min, price_range_max, rating, contact_no, address, free_breakfast, swimming_pool, distance_from_center_km) VALUES
(3, 'Sayeman Beach Resort', 'Resort', 6000, 15000, 4.3, '01811-100001', 'Kolatoli Road, Cox''s Bazar', 1, 1, 0.5),
(3, 'Hotel Sea Crown', 'Hotel', 2500, 5000, 3.8, '01811-100002', 'Kolatoli, Cox''s Bazar', 1, 0, 1.2),
(4, 'Blue Marine Resort', 'Resort', 4000, 9000, 4.0, '01811-100003', 'St. Martin Island', 0, 0, 0.3),
(1, 'Sajek Resort', 'Resort', 3000, 8000, 4.1, '01811-100004', 'Ruilui Para, Sajek', 1, 0, 0);

INSERT INTO hotel_rooms (hotel_id, room_type, price, total_rooms, available_rooms) VALUES
(1,'Deluxe Double', 8000, 20, 12),
(1,'Sea View Suite', 15000, 5, 2),
(2,'Standard Single', 2500, 15, 9),
(3,'Standard Double', 4500, 10, 6),
(4,'Cottage', 5000, 8, 5);

INSERT INTO restaurants (destination_id, restaurant_name, address, latitude, longitude, rating, map_link) VALUES
(3, 'Sea Pearl Cafe', 'Marine Drive, Cox''s Bazar', 21.4200, 92.0100, 4.2, 'https://maps.google.com/?q=Sea+Pearl+Cafe'),
(4, 'Coral Kitchen', 'Saint Martin Island', 20.6260, 92.3200, 4.0, 'https://maps.google.com/?q=Coral+Kitchen');

INSERT INTO food_items (restaurant_id, item_name, suitable_weather, price, item_rating) VALUES
(1, 'Grilled Fish BBQ', 'Sunny', 450, 4.5),
(1, 'Hot Khichuri with Beef', 'Rainy', 250, 4.6),
(2, 'Fresh Coconut Water', 'Sunny', 80, 4.3),
(2, 'Squid Fry', 'Any', 350, 4.1);

INSERT INTO nearby_services (destination_id, service_type, service_name, address, latitude, longitude, contact_no, rating, map_link) VALUES
(3, 'Hospital', 'Cox''s Bazar Sadar Hospital', 'Hospital Road, Cox''s Bazar', 21.4360, 91.9800, '0341-51235', 3.9, 'https://maps.google.com/?q=Cox%27s+Bazar+Sadar+Hospital'),
(3, 'Medical Shop', 'Lazz Pharma', 'Kolatoli Road', 21.4210, 92.0080, '01811-200001', 4.0, 'https://maps.google.com/?q=Lazz+Pharma+Coxs+Bazar');

-- Sample admin login: username "admin", password "admin123"
-- (hashed with PHP password_hash(); change this in production)
INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$xUJdZUNtqpkhZavxHqA2sOcpAIekHW4vOY8boq6wn6kOKa2UouPFm');

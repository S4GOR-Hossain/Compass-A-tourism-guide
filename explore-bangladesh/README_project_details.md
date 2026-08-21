# Explore Bangladesh — Smart Tourism Management System
DBMS Lab Project — HTML, CSS, JavaScript, PHP, MySQL

## What's built

A working, tested slice of the full system covering all 8 modules from your
brief, plus the weather-intelligence layer you asked to have integrated:

**Public site**
- **Homepage** (`index.php`) — hero, category grid (Mountain/Sea/Heritage), featured destinations
- **Destinations list** (`destinations.php`) — filter by category/division/search, live weather score chips
- **Destination details** (`destination_details.php`) — full info, 5-day forecast, transport routes, hotels, nearby essentials, reviews & ratings
- **Weather Planner** (`weather_suggestion.php`) — pick a destination + date, get a go/wait recommendation; today's best-weather ranking across all destinations; weather-matched food picks
- **Hotels** (`hotels.php`) — budget + amenity (breakfast/pool) filters
- **Ticket booking** (`ticket_booking.php`) — pick a route + date, see the weather advice inline, book with a "book anyway" override if the forecast is bad
- **Hotel room booking** (`hotel_booking.php`) — pick a room, dates, guests; decrements `available_rooms` in a transaction
- **Reviews & ratings** — inline on `destination_details.php`, average star shown, one rating per user per destination
- **Shared rides** (`shared_rides.php`) — post a ride or join one; fare-per-person recalculates as people join
- **Booking history** (`booking_history.php`) — a user's ticket + hotel bookings in one place
- Auth: `login.php`, `register.php`, `logout.php`; favourites + AJAX heart button

**Admin panel** (`/admin`, login: `admin` / `admin123` — change before real use)
- `dashboard.php` — key stats + recent ticket bookings
- `destinations.php` — full CRUD for destinations
- `hotels.php` — full CRUD for hotels
- `reviews.php` — verify/unverify/delete reviews (moderation queue)
- `bookings.php` — view + confirm/cancel ticket and hotel bookings

Everything above was tested end-to-end against a live MySQL/MariaDB server:
schema loads clean, sample data inserts, every page returns HTTP 200 (or a
correct login redirect), and the full user journey — register → book a
ticket → book a room → leave a review → post/join a shared ride → view
history — was run through with real HTTP requests. Admin actions (verify a
review, cancel a ticket, delete a destination with cascading rows) were
also exercised directly.

## How your 9 weather features map to what's here

| # | Feature you asked for | Status |
|---|---|---|
| 1 | Weather forecast → when to book a ticket | ✅ `ticket_booking.php` shows live advice before you confirm |
| 2 | Suggest tourist spots by weather report | ✅ "Today's ranking" table on the weather planner, sorted by `weather_score` |
| 3 | Hotel filter by budget range | ✅ `hotels.php` (`budget` filter) |
| 4 | Free breakfast / pool filter | ✅ `hotels.php` (`hotels.free_breakfast`, `hotels.swimming_pool` columns) |
| 5 | "Don't go here today, go there" suggestion | ✅ Rainy-vs-clear callout box on the weather planner |
| 6 | Weather-wise food + restaurant + rating + direction | ✅ `restaurants` / `food_items` tables + food cards on the weather planner |
| 7 | Ticket booking suggestion (go / don't go) | ✅ `getTravelAdvice()` scores the chosen date, flags `suggest_alternate`, and the booking form offers "Book anyway" if risky |
| 8 | Shared vehicle renting between tourists | ✅ `shared_rides.php` — post/join rides, live fare-per-person |
| 9 | Nearby hospital/medical/flower shop + ratings | ✅ `nearby_services` table, shown on `destination_details.php` |

## Setup (XAMPP / WAMP / any LAMP stack)

1. Copy this whole folder into your server's web root (e.g. `htdocs/explore-bangladesh`).
2. Create the database: open phpMyAdmin (or `mysql -u root`) and run `database.sql` — it creates
   the `explore_bangladesh` database, all tables, and sample data in one go.
3. Edit `config/db.php` if your MySQL username/password aren't the XAMPP defaults (`root` / empty).
4. **Weather API key**: sign up for a free key at https://openweathermap.org/api
   (the "5 Day / 3 Hour Forecast" endpoint is on the free tier). Paste it into
   `config/weather_api.php`:
   ```php
   define('OPENWEATHER_API_KEY', 'your_real_key_here');
   ```
   Without a key, pages still work — they just show "forecast pending" until
   cached data exists (the sample data ships with no cached weather rows on
   purpose, so you can demo the live API call).
5. Visit `http://localhost/explore-bangladesh/index.php`.
6. Admin panel: `http://localhost/explore-bangladesh/admin/login.php` — username `admin`, password `admin123`.
   Change this password (or add a new admin row with your own `password_hash()` output) before any real deployment.

## Design notes

Theme is a nature/tourism identity built specifically for Bangladesh: paddy-field
green, river teal, and sunrise coral, with a terracotta clay accent reserved for
Heritage-category elements (a nod to the terracotta temple plaques at sites like
Paharpur). Type pairing is Fraunces (display) + Hind Siliguri (body — chosen because
it has full Bengali script support, in case you want to add bilingual copy later).

## Suggested next build order

1. Group tour packages / multi-day itinerary builder (from your "Future Scope" list).
2. Image uploads for destinations and review photos (currently `images`/`photo_url`
   columns exist but the UI only accepts URLs).
3. Email confirmation for bookings.
4. Admin CRUD for transport routes and nearby services (currently seeded via SQL only).
5. AI trip planner / weather forecast widget for a full week+ (OpenWeatherMap free
   tier only gives 5 days — a paid tier or a second provider would be needed for more).

## Folder structure

```
explore-bangladesh/
├── database.sql                  # full schema + sample data (tested)
├── config/
│   ├── db.php                     # PDO connection
│   └── weather_api.php            # OpenWeatherMap wrapper, scoring, caching
├── includes/
│   ├── header.php / footer.php
│   └── functions.php
├── api/
│   ├── get_weather.php            # JSON forecast + advice endpoint
│   └── toggle_favourite.php
├── admin/
│   ├── dashboard.php / destinations.php / hotels.php / reviews.php / bookings.php
│   ├── login.php / logout.php
│   └── includes/ (auth.php, header.php, footer.php)
├── css/style.css
├── js/script.js
├── index.php
├── destinations.php
├── destination_details.php
├── weather_suggestion.php
├── hotels.php
├── ticket_booking.php
├── hotel_booking.php
├── shared_rides.php
├── booking_history.php
├── favourites.php
├── login.php / register.php / logout.php
```

<?php
/**
 * Weather integration — OpenWeatherMap (free tier)
 * Get a free API key at: https://openweathermap.org/api
 *
 * This file wraps the "5 day / 3 hour forecast" endpoint, converts
 * it into a simple daily summary, scores each day for "how good is
 * this weather for tourism", and caches results in weather_logs so
 * the free-tier rate limit (60 calls/min, 1,000,000 calls/month)
 * is not hit on every page view.
 */

require_once __DIR__ . '/weather_api_key.php';
define('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5/forecast');

/**
 * Fetch + cache a 5-day forecast summary for one destination.
 * Returns an array of daily summaries: date, condition, temp_min/max,
 * rain_probability, wind_speed, weather_score (0-100).
 */
function getForecastForDestination(PDO $pdo, int $destinationId, float $lat, float $lon): array
{
    // 1. Try cache first (today .. today+4)
    $cached = getCachedForecast($pdo, $destinationId);
    if (count($cached) >= 5) {
        return $cached;
    }

    // 2. Call OpenWeatherMap
    $url = OPENWEATHER_BASE_URL . '?' . http_build_query([
        'lat'   => $lat,
        'lon'   => $lon,
        'appid' => OPENWEATHER_API_KEY,
        'units' => 'metric',
    ]);

    $raw = @file_get_contents($url);
    if ($raw === false) {
        // API unreachable / no key set yet — fall back to cache (may be empty)
        return $cached;
    }

    $data = json_decode($raw, true);
    if (!isset($data['list'])) {
        return $cached;
    }

    // 3. Group the 3-hour blocks into daily summaries
    $daily = [];
    foreach ($data['list'] as $slot) {
        $date = substr($slot['dt_txt'], 0, 10);
        if (!isset($daily[$date])) {
            $daily[$date] = [
                'temps' => [],
                'pop'   => [],   // probability of precipitation
                'wind'  => [],
                'conditions' => [],
            ];
        }
        $daily[$date]['temps'][]      = $slot['main']['temp'];
        $daily[$date]['pop'][]        = $slot['pop'] ?? 0;
        $daily[$date]['wind'][]       = $slot['wind']['speed'] ?? 0;
        $daily[$date]['conditions'][] = $slot['weather'][0]['main'] ?? 'Clear';
    }

    // 4. Build summary + weather score, then cache
    $result = [];
    foreach ($daily as $date => $d) {
        $tempMin = round(min($d['temps']), 1);
        $tempMax = round(max($d['temps']), 1);
        $rainProb = round((array_sum($d['pop']) / count($d['pop'])) * 100, 1);
        $windAvg = round(array_sum($d['wind']) / count($d['wind']), 1);
        $mainCondition = array_count_values($d['conditions']);
        arsort($mainCondition);
        $condition = array_key_first($mainCondition);

        $score = calculateWeatherScore($condition, $rainProb, $windAvg, $tempMax);

        upsertWeatherLog($pdo, $destinationId, $date, $condition, $tempMin, $tempMax, $rainProb, $windAvg, $score);

        $result[] = [
            'forecast_date'    => $date,
            'condition_main'   => $condition,
            'temp_min'         => $tempMin,
            'temp_max'         => $tempMax,
            'rain_probability' => $rainProb,
            'wind_speed'       => $windAvg,
            'weather_score'    => $score,
        ];
    }

    return array_slice($result, 0, 5);
}

/**
 * Score 0-100: higher = better weather for outdoor tourism.
 * Heavily penalises rain probability & storms; mildly penalises high wind/heat.
 */
function calculateWeatherScore(string $condition, float $rainProb, float $windSpeed, float $tempMax): int
{
    $score = 100;

    // Condition penalty
    $conditionPenalty = [
        'Thunderstorm' => 70,
        'Rain'         => 40,
        'Drizzle'      => 20,
        'Clouds'       => 10,
        'Mist'         => 15,
        'Fog'          => 20,
        'Clear'        => 0,
    ];
    $score -= $conditionPenalty[$condition] ?? 15;

    // Rain probability penalty (0-100% -> up to 40 pts off)
    $score -= round($rainProb * 0.4);

    // Wind penalty (above 8 m/s starts to matter, e.g. launch/ferry travel)
    if ($windSpeed > 8) {
        $score -= min(15, ($windSpeed - 8) * 3);
    }

    // Heat penalty (above 35°C)
    if ($tempMax > 35) {
        $score -= min(10, ($tempMax - 35) * 2);
    }

    return (int) max(0, min(100, $score));
}

/** Human readable travel recommendation from a score */
function weatherScoreLabel(int $score): array
{
    if ($score >= 80) return ['label' => 'Excellent for travel', 'class' => 'score-great'];
    if ($score >= 60) return ['label' => 'Good, minor risk',      'class' => 'score-good'];
    if ($score >= 40) return ['label' => 'Caution advised',       'class' => 'score-caution'];
    return ['label' => 'Not recommended', 'class' => 'score-bad'];
}

function getCachedForecast(PDO $pdo, int $destinationId): array
{
    $stmt = $pdo->prepare(
        "SELECT forecast_date, condition_main, temp_min, temp_max, rain_probability, wind_speed, weather_score
         FROM weather_logs
         WHERE destination_id = :id AND forecast_date >= CURDATE()
         ORDER BY forecast_date ASC LIMIT 5"
    );
    $stmt->execute(['id' => $destinationId]);
    return $stmt->fetchAll();
}

function upsertWeatherLog(PDO $pdo, int $destinationId, string $date, string $condition, float $tMin, float $tMax, float $rainProb, float $wind, int $score): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO weather_logs (destination_id, forecast_date, condition_main, temp_min, temp_max, rain_probability, wind_speed, weather_score)
         VALUES (:dest, :date, :cond, :tmin, :tmax, :rain, :wind, :score)
         ON DUPLICATE KEY UPDATE
            condition_main = VALUES(condition_main),
            temp_min = VALUES(temp_min),
            temp_max = VALUES(temp_max),
            rain_probability = VALUES(rain_probability),
            wind_speed = VALUES(wind_speed),
            weather_score = VALUES(weather_score),
            fetched_at = CURRENT_TIMESTAMP"
    );
    $stmt->execute([
        'dest'  => $destinationId,
        'date'  => $date,
        'cond'  => $condition,
        'tmin'  => $tMin,
        'tmax'  => $tMax,
        'rain'  => $rainProb,
        'wind'  => $wind,
        'score' => $score,
    ]);
}

/**
 * Feature #7 — "should I book this ticket?" for a chosen travel date.
 */
function getTravelAdvice(PDO $pdo, int $destinationId, float $lat, float $lon, string $travelDate): array
{
    $forecast = getForecastForDestination($pdo, $destinationId, $lat, $lon);
    foreach ($forecast as $day) {
        if ($day['forecast_date'] === $travelDate) {
            $advice = weatherScoreLabel($day['weather_score']);
            return [
                'found'   => true,
                'day'     => $day,
                'advice'  => $advice,
                'suggest_alternate' => $day['weather_score'] < 40,
            ];
        }
    }
    return ['found' => false, 'day' => null, 'advice' => null, 'suggest_alternate' => false];
}

<?php
/**
 * Scheduled weather refresh.
 *
 * Run this on a schedule (every 5 hours, matching WEATHER_REFRESH_HOURS in
 * config/weather_api.php) so cached forecasts stay current in the background,
 * instead of relying on a visitor loading weather_suggestion.php.
 *
 * CLI only — not meant to be opened in a browser.
 *
 * Example cron entry (every 5 hours, on the hour):
 *   0 star-slash-5 * * *  php /full/path/to/explore-bangladesh/cron/refresh_weather.php >> /full/path/to/explore-bangladesh/cron/refresh_weather.log 2>&1
 * (replace "star-slash-5" with the actual cron syntax: 0 * /5 * * *  written without the space)
 *
 * Windows Task Scheduler: run
 *   php.exe C:\path\to\explore-bangladesh\cron\refresh_weather.php
 * every 5 hours instead.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script is meant to run from the command line (cron), not a browser.\n");
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/weather_api.php';

$start = microtime(true);
echo '[' . date('Y-m-d H:i:s') . "] Weather refresh started...\n";

try {
    refreshAllDestinationsWeather($pdo);
    $count = $pdo->query("SELECT COUNT(*) FROM destinations WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn();
    $elapsed = round(microtime(true) - $start, 2);
    echo '[' . date('Y-m-d H:i:s') . "] Done. Checked/refreshed {$count} destinations in {$elapsed}s.\n";
} catch (Throwable $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
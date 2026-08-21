<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/weather_api.php';

$destinationId = isset($_GET['destination_id']) ? (int) $_GET['destination_id'] : 0;
$travelDate    = $_GET['travel_date'] ?? null;

if (!$destinationId) {
    echo json_encode(['error' => 'destination_id is required']);
    exit;
}

$stmt = $pdo->prepare("SELECT destination_id, name, latitude, longitude FROM destinations WHERE destination_id = ?");
$stmt->execute([$destinationId]);
$dest = $stmt->fetch();

if (!$dest) {
    echo json_encode(['error' => 'Destination not found']);
    exit;
}

$forecast = getForecastForDestination($pdo, $destinationId, (float) $dest['latitude'], (float) $dest['longitude']);

$advice = ['found' => false];
if ($travelDate) {
    $advice = getTravelAdvice($pdo, $destinationId, (float) $dest['latitude'], (float) $dest['longitude'], $travelDate);
}

echo json_encode([
    'destination' => $dest['name'],
    'forecast'    => $forecast,
    'advice'      => $advice,
]);

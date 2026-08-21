<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'login_required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$destinationId = (int) ($input['destination_id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if (!$destinationId) {
    echo json_encode(['error' => 'destination_id is required']);
    exit;
}

$check = $pdo->prepare("SELECT favourite_id FROM favourites WHERE user_id = ? AND destination_id = ?");
$check->execute([$userId, $destinationId]);
$existing = $check->fetch();

if ($existing) {
    $del = $pdo->prepare("DELETE FROM favourites WHERE favourite_id = ?");
    $del->execute([$existing['favourite_id']]);
    echo json_encode(['status' => 'ok', 'is_favourite' => false]);
} else {
    $ins = $pdo->prepare("INSERT INTO favourites (user_id, destination_id) VALUES (?, ?)");
    $ins->execute([$userId, $destinationId]);
    echo json_encode(['status' => 'ok', 'is_favourite' => true]);
}

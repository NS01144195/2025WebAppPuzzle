<?php
session_start();
header('Content-Type: application/json');

require_once 'Model/GameController.php';
require_once 'Model/Util/SessionKeys.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed. Use POST']);
    exit;
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE || empty($data['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

switch ($data['action']) {
    case 'swapPieces':
        $difficulty = $_SESSION[SessionKeys::DIFFICULTY] ?? 'normal';
        $gameController = new GameController($difficulty);
        $response = $gameController->handlePlayerAction('swapPieces', $data);
        echo json_encode($response);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
        break;
}
?>

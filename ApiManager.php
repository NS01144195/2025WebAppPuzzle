<?php
session_start();
header('Content-Type: application/json');

// 必要なコントローラーを読み込む
require_once 'Model/GameController.php';

// POSTされてきたJSONデータを取得
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 最低限のリクエスト検証
if (!isset($data['action']) || !$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '無効なリクエストです。']);
    exit;
}

// GameControllerを生成し、アクション処理を依頼
$gameController = new GameController();
$response = $gameController->handlePlayerAction($data['action'], $data);

// Controllerから返された結果をJSONで出力
echo json_encode($response);
?>
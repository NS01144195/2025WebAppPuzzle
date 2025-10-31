<?php
session_start();
header('Content-Type: application/json');

// INFO: プレイヤー操作を処理するコントローラーを読み込む。
require_once 'Model/GameController.php';

// INFO: JSON リクエストボディを取得して配列に変換する。
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// INFO: 不正な JSON やアクション指定なしの場合はバリデーションエラーとする。
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE || empty($data['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '無効なリクエストです。']);
    exit;
}

// INFO: 難易度はセッションから読み出し、未設定なら normal を使う。
$difficulty = $_SESSION['difficulty'] ?? 'normal';

// INFO: 難易度に応じたコントローラーでアクションを処理する。
$gameController = new GameController($difficulty);

$response = $gameController->handlePlayerAction($data['action'], $data);

// INFO: コントローラーのレスポンスをそのまま返す。
echo json_encode($response);
?>

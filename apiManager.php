<?php
// apiManager.php

// JSON形式でレスポンスを返すことをヘッダーで明示
header('Content-Type: application/json');

// セッションを開始して、保存されている盤面データにアクセスできるようにする
session_start();

// PuzzleManagerクラスを読み込む
require_once 'puzzleManager.php';

// PuzzleManagerのインスタンスを作成
$puzzleManager = new PuzzleManager();

// --- リクエスト処理 ---

// 1. セッションから現在の盤面データをロード
// 盤面データが存在しない場合はエラーレスポンスを返す
if (!isset($_SESSION['board'])) {
    // HTTPステータスコード 400 Bad Request を設定
    http_response_code(400); 
    echo json_encode(['status' => 'error', 'message' => '盤面データがセッションに存在しません。']);
    exit;
}
$puzzleManager->setBoard($_SESSION['board']);


// 2. POSTされてきたJSONデータを取得してPHPの連想配列に変換
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// 3. 'action'キーが存在しなかったり、データが空の場合はエラー
if (!isset($data['action']) || !$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '無効なリクエストです。']);
    exit;
}


// --- アクションの振り分け ---

$response = []; // 返却するレスポンスデータを格納する配列

switch ($data['action']) {
    
    // 'swapPieces'アクションが指定された場合
    case 'swapPieces':
        // 必要な座標データがすべて存在するかチェック
        if (isset($data['r1'], $data['c1'], $data['r2'], $data['c2'])) {
            
            // PuzzleManagerのメソッドを呼び出してピース交換とマッチ判定を行う
            $isMatch = $puzzleManager->swapPieces($data['r1'], $data['c1'], $data['r2'], $data['c2']);
            
            // マッチした場合のみ、更新された盤面データをセッションに保存
            if ($isMatch) {
                $_SESSION['board'] = $puzzleManager->getBoard();
            }

            // 成功レスポンスを準備 (判定結果も含める)
            $response = [
                'status' => 'success', 
                'message' => 'ピース交換処理が完了しました。',
                'isMatch' => $isMatch // 判定結果をレスポンスに追加
            ];

        } else {
            // データが不足している場合はエラー
            http_response_code(400);
            $response = ['status' => 'error', 'message' => 'ピース交換のための座標データが不足しています。'];
        }
        break;
    
    // 他のアクションが追加された場合はここに 'case' を追記
    //例: case 'checkMatches': ...

    default:
        // 知らないアクションが指定された場合はエラー
        http_response_code(400);
        $response = ['status' => 'error', 'message' => '不明なアクションです。'];
        break;
}

// 4. 最終的なレスポンスをJSON形式で出力
echo json_encode($response);
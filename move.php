<?php
include 'PuzzleManager.php';

header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $input = json_decode(file_get_contents("php://input"), true);
    $x = $input['x'] ?? 0;
    $y = $input['y'] ?? 0;
    $direction = $input['direction'] ?? '';

    $puzzle = new PuzzleManager();

    // 1. 入れ替え
    $success = $puzzle->swap($x, $y, $direction);

    $totalRemoved = [];
    $score = 0;

    if ($success) {
        // 連鎖が発生する可能性があるため、ループ処理でマッチがなくなるまで繰り返す
        do {
            // 2. マッチ判定
            $removed = $puzzle->checkMatches();

            if (count($removed) > 0) {
                // 3. 削除
                foreach ($removed as $coord) {
                    // 削除対象のピースをnullに設定 (落下のために必要)
                    $puzzle->board[$coord['y']][$coord['x']]['color'] = null;
                }

                // 削除された座標を総計に追加
                $totalRemoved = array_merge($totalRemoved, $removed);
                $score += count($removed) * 10;

                // 4. ピースの落下
                $puzzle->dropPieces();

                // 5. 新しいピースの補充
                $puzzle->refillPieces();
            }
        } while (count($removed) > 0); // マッチがある限り繰り返す (連鎖処理の最小実装)
    }

    // セッション更新
    $_SESSION['board'] = $puzzle->board;

    // JSON返却
    echo json_encode([
        'board' => $puzzle->board,
        // UIアニメーションのために、削除されたピース全体を返す
        'removed' => array_values(array_unique(array_map("serialize", $totalRemoved))),
        'score' => $score
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
exit;
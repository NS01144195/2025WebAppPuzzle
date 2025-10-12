<?php
session_start();
require_once 'PuzzleManager.php';

// PuzzleManager をセッションから取得
if (!isset($_SESSION['puzzleManager'])) {
    $puzzleManager = new PuzzleManager();
    $_SESSION['puzzleManager'] = serialize($puzzleManager);
} else {
    $puzzleManager = unserialize($_SESSION['puzzleManager']);
}

// ===== swipe POST 処理（先に判定） =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'swipe') {
    $row = (int)$_POST['row'];
    $col = (int)$_POST['col'];
    $direction = $_POST['direction'];

    // PuzzleManager に任せて入れ替え
    $swiped = $puzzleManager->swipePiece($row, $col, $direction);

    // 成功したら盤面を取得して返す
    $newBoard = $puzzleManager->getBoard();

    // セッションに保存
    $_SESSION['puzzleManager'] = serialize($puzzleManager);

    header('Content-Type: application/json');
    echo json_encode(['board' => $newBoard, 'success' => $swiped]);
    exit;
}

// ===== 画面遷移処理 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'gameScene':
            $_SESSION['current_scene'] = 'game';

            // 毎回PuzzleManagerを初期化
            $puzzleManager = new PuzzleManager();
            $puzzleManager->startGame();
/*
            // テスト盤面をセット
            $testBoard = [
                [1,2,3,4,5,1,2,3,4],
                [2,3,2,5,1,2,3,4,5],
                [3,4,5,1,2,3,4,5,1],
                [4,5,1,2,3,4,5,1,2],
                [5,1,2,3,4,5,1,2,3],
                [1,2,3,4,5,1,2,3,4],
                [2,3,4,5,1,2,3,4,5],
                [3,4,5,1,2,3,4,5,1],
                [4,5,1,2,3,4,5,1,2],
            ];
            $puzzleManager->setBoard($testBoard);
*/
            $_SESSION['puzzleManager'] = serialize($puzzleManager);
            break;

        case 'titleScene':
            $_SESSION['current_scene'] = 'title';
            unset($_SESSION['puzzleManager']); // もしゲームデータを破棄したい場合
            break;
    }

    // 二重送信防止
    header('Location: index.php');
    exit;
}


// 現在のシーン
if (!isset($_SESSION['current_scene'])) {
    $_SESSION['current_scene'] = 'title';
}

$view_file = $_SESSION['current_scene'] . 'SceneView.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1280, height=720">
    <title>3 Match Puzzle</title>
    <link rel="stylesheet" href="style.css">
    <script src="main.js" defer></script>
</head>
<body>
    <div id="game-container">
        <?php
            if (file_exists($view_file)) {
                require_once $view_file; 
            } else {
                echo "<div>エラー: ビューファイルが見つかりません。</div>";
            }
        ?>
    </div>
    <div id="game-screen" class="screen">
    </div>
</body>
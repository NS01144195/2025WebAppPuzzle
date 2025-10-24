<?php
session_start();

// 画面の状態管理：現在のシーンをセッションから取得
if (!isset($_SESSION['current_scene'])) {
    $_SESSION['current_scene'] = 'title';
}

// POSTリクエストがあれば、画面遷移の処理を行う
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'gameScene') {
        $_SESSION['current_scene'] = 'game';
    } elseif ($_POST['action'] === 'titleScene') {
        $_SESSION['current_scene'] = 'title';
        // タイトルに戻るときは盤面をリセット
        unset($_SESSION['board']);
    } elseif ($_POST['action'] === 'resultScene') {
        $_SESSION['current_scene'] = 'result';
    }
    // 画面遷移後はリダイレクトしてPOSTデータをクリアする（二重送信防止）
    header('Location: index.php');
    exit;
}

// puzzleManagerの読み込み
require_once 'puzzleManager.php';
$puzzleManager = new PuzzleManager();

if ($_SESSION['current_scene'] === 'game') {
    if (!isset($_SESSION['board'])) {
        // 盤面がセッションに存在しない場合のみ初期化
        $puzzleManager->initBoard();
        $_SESSION['board'] = $puzzleManager->getBoard(); // セッションに保存
    } else {
        // 盤面がセッションに存在する場合、それをロード
        $puzzleManager->setBoard($_SESSION['board']);
    }
}

// 現在のシーンに基づいてビューファイルを決定
$view_file = $_SESSION['current_scene'] . 'SceneView.php';

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <!-- viewPortをレスポンシブな設定に変更 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3 Match Puzzle</title>
    <link rel="stylesheet" href="style.css">
    <script src="main.js" defer></script>
</head>

<body>
    <div id="game-container">
        <!-- タイトルシーン以外ではタイトルへ戻るボタンを表示 -->
        <?php if ($view_file !== "titleSceneView.php"): ?>
            <form method="POST" action="index.php" id="title-return-form">
                <input type="hidden" name="action" value="titleScene">
                <button id="to-title-button" class="control-button" type="submit">タイトルへ戻る</button>
            </form>
        <?php endif; ?>
        <?php
        if (file_exists($view_file)) {
            // $puzzleManagerのインスタンスをビューファイルで使えるようにする
            require_once $view_file;
        } else {
            echo "<div>エラー: ビューファイルが見つかりません。</div>";
        }
        ?>
    </div>
</body>
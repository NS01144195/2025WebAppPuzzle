<?php
// PHPがメインの制御を行う
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
    }
    // 画面遷移後はリダイレクトしてPOSTデータをクリアする（二重送信防止）
    header('Location: index.php');
    exit;
}

// TODO: Modelの読み込み
// require_once 'model/GameModel.php';

// 現在のシーンに基づいてビューファイルを決定
$view_file = $_SESSION['current_scene'] . 'SceneView.php';

// TODO:Modelの準備
/*
$gameModel = new GameModel(); // PHP版のModelクラス
if ($_SESSION['current_scene'] === 'game') {
    $gameModel->initializeGame(); // ゲーム開始時にModelを初期化
}
*/
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
                // $gameModelなど、PHPの変数をビューファイルで使えるようにする
                require_once $view_file; 
            } else {
                echo "<div>エラー: ビューファイルが見つかりません。</div>";
            }
        ?>
    </div>
</body>
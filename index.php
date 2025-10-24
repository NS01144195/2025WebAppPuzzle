<?php
session_start();

// 必要なクラスファイルを読み込む
require_once 'Model/Util/SceneManager.php';
require_once 'Model/GameController.php';

// SceneManagerを生成し、現在のシーン情報を取得
$sceneManager = new SceneManager();
$view_file = $sceneManager->getSceneViewFile();
$current_scene = $sceneManager->getCurrentScene();

// ゲームシーンの場合、ゲームロジックの準備を行う
if ($current_scene === 'game') {
    $gameController = new GameController();
    $gameController->prepareGame(); // ゲームの準備を指示
    $viewData = $gameController->getViewData(); // View用のデータを取得
    
    // Viewに変数を展開
    extract($viewData);
}

// リザルトシーンの場合、ビューに渡すデータを準備する
if ($current_scene === 'result') {
    // セッションからゲーム結果を取得
    $gameState = $_SESSION['gameState'] ?? 0;
    $finalScore = $_SESSION['score'] ?? 0;
    $movesLeft = $_SESSION['movesLeft'] ?? 0;

    // 表示するテキストを決定
    $resultText = '';
    if ($gameState === 2) { // GameStatus::CLEAR->value
        $resultText = 'ゲームクリア！';
    } elseif ($gameState === 3) { // GameStatus::OVER->value
        $resultText = 'ゲームオーバー…';
    }
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3 Match Puzzle</title>
    <link rel="stylesheet" href="css/common.css">
    <?php
    // SceneManagerが決定したシーンのcssを読み込んで表示
    $scene_css_file = 'css/' . $current_scene . 'Scene.css';
    if (file_exists($scene_css_file)) {
        echo '<link rel="stylesheet" href="' . $scene_css_file . '">';
    }
    ?>
    <script src="js/main.js" type="module" defer></script>
</head>

<body>
    <div id="game-container">
        <?php if ($current_scene !== 'title'): ?>
            <form method="POST" action="index.php" id="title-return-form">
                <input type="hidden" name="action" value="titleScene">
                <button id="to-title-button" type="submit">タイトルへ戻る</button>
            </form>
        <?php endif; ?>

        <?php
        // SceneManagerが決定したシーンのSceneViewを読み込んで表示
        if (file_exists($view_file)) {
            // require_once 'gameSceneView.php' などが実行される
            require_once $view_file;
        } else {
            echo "<div>エラー: ビューファイルが見つかりません。</div>";
        }
        ?>
    </div>
</body>

</html>
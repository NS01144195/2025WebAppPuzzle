<?php
session_start();

// 必要なクラスファイルを読み込む
require_once 'SceneManager.php';
// require_once 'GameController.php'; // ←今後作成するクラス
// ... 他のクラスファイル

// SceneManagerを生成し、現在のシーン情報を取得
$sceneManager = new SceneManager();
$view_file = $sceneManager->getSceneViewFile();
$current_scene = $sceneManager->getCurrentScene();

// ゲームシーンの場合のみ、ゲームロジックの準備を行う
if ($current_scene === 'game') {
    // ※これは今後作成するGameControllerの呼び出し例です
    // $gameController = new GameController();
    // $gameController->prepareGame();
    // $viewData = $gameController->getViewData();
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
    <script src="main.js" defer></script>
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
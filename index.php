<?php
session_start();

// INFO: 必要なクラスを読み込み、シーン制御とゲーム処理を利用可能にする。
require_once __DIR__ . '/Model/Util/SceneManager.php';
require_once __DIR__ . '/Model/GameController.php';

// INFO: 現在のシーン情報を取得して表示内容を切り替える。
$sceneManager = new SceneManager();
$view_file = $sceneManager->getSceneViewFile();
$currentScene = $sceneManager->getCurrentScene();
$sceneDataPack = $sceneManager->getDataPack();
$viewData = [];
$shouldAcknowledgeHighScore = false;

switch ($currentScene) {
    case 'game':
        // INFO: データパックに保存された難易度を利用し、未設定なら normal を使う。
        if ($sceneDataPack instanceof GameSceneDataPack) {
            $difficulty = $sceneDataPack->getDifficulty();
        } else {
            $difficulty = 'normal';
        }

        // INFO: 難易度に応じたゲーム状態をロードする。
        $gameController = new GameController($difficulty);
        $gameController->prepareGame();
        $viewData = $gameController->getViewData();
        break;

    case 'result':
        if ($sceneDataPack instanceof ResultSceneDataPack && $sceneDataPack->isNewHighScore()) {
            $shouldAcknowledgeHighScore = true;
        }
        break;
}

if (!empty($viewData)) {
    extract($viewData);
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
    // INFO: シーンに合わせたスタイルを追加する。
    $sceneCssFile = 'css/' . $currentScene . 'Scene.css';
    if (file_exists($sceneCssFile)) {
        echo '<link rel="stylesheet" href="' . $sceneCssFile . '">';
    }
    ?>
    <script src="js/main.js" type="module" defer></script>
</head>

<body>
    <div id="game-container">
        <?php if ($currentScene !== 'title'): ?>
            <form method="POST" action="index.php" id="title-return-form">
                <input type="hidden" name="action" value="titleScene">
                <button id="to-title-button" type="submit">タイトルへ戻る</button>
            </form>
        <?php endif; ?>

        <?php
        // INFO: 選択されたシーンのビューを組み込む。
        if (file_exists($view_file)) {
            // NOTE: require_once でビューを読み込むと副作用として HTML が生成される。
            require_once $view_file;
        } else {
            echo "<div>エラー: ビューファイルが見つかりません。</div>";
        }

        if ($shouldAcknowledgeHighScore) {
            // NOTE: ビュー表示後にハイスコア更新フラグをリセットして再表示を防ぐ。
            $sceneDataPack->acknowledgeHighScoreMessage();
            SceneDataPackStorage::update($sceneDataPack);
        }
        ?>
    </div>
</body>

</html>
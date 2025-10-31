<?php
session_start();

// INFO: 必要なクラスを読み込み、シーン制御とゲーム処理を利用可能にする。
require_once 'Model/Util/SceneManager.php';
require_once 'Model/GameController.php';

// INFO: 現在のシーン情報を取得して表示内容を切り替える。
$sceneManager = new SceneManager();
$view_file = $sceneManager->getSceneViewFile();
$current_scene = $sceneManager->getCurrentScene();
$viewData = [];

switch ($current_scene) {
    case 'game':
        // INFO: セッションに保存された難易度を利用し、未設定なら normal を使う。
        $difficulty = $_SESSION['difficulty'] ?? 'normal';

        // INFO: 難易度に応じたゲーム状態をロードする。
        $gameController = new GameController($difficulty);
        $gameController->prepareGame();
        $viewData = $gameController->getViewData();
        break;

    case 'result':
        // INFO: ゲーム終了時に保存した結果を読み込む。
        $gameState = $_SESSION['gameState'] ?? 0;
        $finalScore = $_SESSION['score'] ?? 0;
        $movesLeft = $_SESSION['movesLeft'] ?? 0;

        // INFO: 状態に合わせて文言を切り替える。
        $resultText = match ($gameState) {
            2 => 'ゲームクリア！',
            3 => 'ゲームオーバー…',
            default => ''
        };

        $isNewHighScore = $_SESSION['isNewHighScore'] ?? false;
        unset($_SESSION['isNewHighScore']); // NOTE: 再表示を防ぐためにフラグを破棄する。
        break;

    case 'select':
        // INFO: ステージセレクトでは Cookie のハイスコアを参照する。
        $highScore = $_COOKIE['highscore'] ?? 0;
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
        // INFO: 選択されたシーンのビューを組み込む。
        if (file_exists($view_file)) {
            // NOTE: require_once でビューを読み込むと副作用として HTML が生成される。
            require_once $view_file;
        } else {
            echo "<div>エラー: ビューファイルが見つかりません。</div>";
        }
        ?>
    </div>
</body>

</html>
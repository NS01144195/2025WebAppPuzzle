<div id="result-screen" class="scene-view screen active">
    <?php
    $gameState = isset($_SESSION['gameState']) ? $_SESSION['gameState'] : 0;

    // gameState が 2（クリア）か 3（ゲームオーバー）の場合だけ表示
    if ($gameState === 2) {
        $resultText = "ゲームクリア！";
    } elseif ($gameState === 3) {
        $resultText = "ゲームオーバー…";
    } else {
        $resultText = ""; // それ以外は表示しない
    }
    ?>

    <?php if ($resultText !== ""): ?>
        <h1><?= $resultText ?></h1>
        <p>スコア: <?= isset($_SESSION['score']) ? $_SESSION['score'] : 0 ?></p>
        <p>残りライフ: <?= isset($_SESSION['movesLeft']) ? $_SESSION['movesLeft'] : 0 ?></p>
    <?php endif; ?>
</div>
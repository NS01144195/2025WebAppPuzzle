<div id="select-screen" class="scene-view screen active">
    <h1>ステージ選択</h1>
    <?php
    $displayHighScore = 0;
    if (isset($sceneDataPack) && $sceneDataPack instanceof SelectSceneDataPack) {
        $displayHighScore = $sceneDataPack->getHighScore();
    } else {
        $displayHighScore = isset($_COOKIE['highscore']) ? (int)$_COOKIE['highscore'] : 0;
    }
    ?>
    <p class="highscore">ハイスコア: <?= htmlspecialchars($displayHighScore) ?></p>
    <div class="stage-buttons">
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="gameScene">
            <input type="hidden" name="difficulty" value="tutorial">
            <button type="submit" class="stage-button tutorial">チュートリアル</button>
        </form>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="gameScene">
            <input type="hidden" name="difficulty" value="easy">
            <button type="submit" class="stage-button easy">かんたん</button>
        </form>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="gameScene">
            <input type="hidden" name="difficulty" value="normal">
            <button type="submit" class="stage-button normal">ふつう</button>
        </form>

        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="gameScene">
            <input type="hidden" name="difficulty" value="hard">
            <button type="submit" class="stage-button hard">むずかしい</button>
        </form>
    </div>
</div>
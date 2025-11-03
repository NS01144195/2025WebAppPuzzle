<?php
// INFO: SceneManager から読み込まれるリザルトシーンのテンプレート。
?>
<div id="result-screen" class="scene-view screen active">

    <?php
    $resultText = '';
    $finalScore = 0;
    $movesLeft = 0;
    $isNewHighScore = false;

    if (isset($sceneDataPack) && $sceneDataPack instanceof ResultSceneDataPack) {
        $resultText = match ($sceneDataPack->getGameState()) {
            2 => 'ゲームクリア！',
            3 => 'ゲームオーバー…',
            default => ''
        };
        $finalScore = $sceneDataPack->getFinalScore();
        $movesLeft = $sceneDataPack->getMovesLeft();
        $isNewHighScore = $sceneDataPack->isNewHighScore();
    }
    ?>

    <?php if ($resultText !== ''): ?>
        <h1><?= htmlspecialchars($resultText) ?></h1>
        <?php if ($isNewHighScore): ?>
            <h1 class="new-highscore">ハイスコア更新！</h1>
        <?php endif; ?>
        <p>スコア: <?= htmlspecialchars($finalScore) ?></p>
        <p>残りライフ: <?= htmlspecialchars($movesLeft) ?></p>
    <?php endif; ?>

</div>
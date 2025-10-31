<?php
// INFO: SceneManager から読み込まれるリザルトシーンのテンプレート。
?>
<?php
/**
 * このビューを読み込む前に、以下の変数が定義されている必要がある
 * @var string $resultText  ("ゲームクリア！" または "ゲームオーバー…")
 * @var int    $finalScore  (最終スコア)
 * @var int    $movesLeft   (残り手数)
 */
?>
<div id="result-screen" class="scene-view screen active">
    
    <?php // INFO: 必要なデータがある場合のみ結果を描画する。 ?>
    <?php if (isset($resultText)): ?>
        <h1><?= htmlspecialchars($resultText) ?></h1>
        <?php if (isset($isNewHighScore) && $isNewHighScore): ?>
            <h1 class="new-highscore">ハイスコア更新！</h1>
        <?php endif; ?>
        <p>スコア: <?= htmlspecialchars($finalScore) ?></p>
        <p>残りライフ: <?= htmlspecialchars($movesLeft) ?></p>
    <?php endif; ?>

</div>
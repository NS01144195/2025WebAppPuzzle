<?php
// このファイルは SceneManager によって読み込まれる
// ゲームシーン
?>
<?php
/**
 * このビューを読み込む前に、以下の変数が定義されている必要がある
 * @var array $board         (盤面のピース色を格納した2次元配列)
 * @var int   $targetScore   (目標スコア)
 * @var int   $currentScore  (現在のスコア)
 * @var int   $movesLeft     (残り手数)
 */
?>
<div id="game-screen" class="scene-view screen active">
    <div id="puzzle-board">
        <?php
        // 盤面データが渡されているかチェック
        if (!empty($board)) {
            $boardSize = count($board);
            for ($row = 0; $row < $boardSize; $row++) {
                for ($col = 0; $col < $boardSize; $col++) {
                    // htmlspecialcharsで安全に色を出力
                    $color = htmlspecialchars($board[$row][$col]);

                    echo '<div class="cell" data-row="' . $row . '" data-col="' . $col . '">';
                    echo '<div class="piece" style="background-color:' . $color . '"></div>';
                    echo '</div>';
                }
            }
        } else {
            // 盤面データがない場合のエラー表示
            echo '<div style="color: white; padding: 20px;">盤面データがありません。</div>';
        }
        ?>
    </div>

    <div id="right-ui">
        <div id="target-score">目標スコア: <span id="target-score-value"><?= htmlspecialchars($targetScore) ?></span></div>
        <div id="score-display">スコア: <span id="score-value"><?= htmlspecialchars($currentScore) ?></span></div>
        <div id="moves-display">残りライフ: <span id="moves-left-value"><?= htmlspecialchars($movesLeft) ?></span></div>
    </div>

    <div id="result-overlay">
        <div id="result-message"></div>
    </div>
</div>
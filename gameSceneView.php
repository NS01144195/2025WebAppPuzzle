<div id="game-screen" class="scene-view screen active">
    <div id="puzzle-board">
        <?php
        // index.phpで$puzzleManagerが初期化されていることを前提とする
        $board = $puzzleManager->getBoard();
        $boardSize = count($board); // 盤面のサイズを取得
        $targetScore = isset($puzzleManager) ? $puzzleManager->getTargetScore() : 0;
        $currentScore = isset($puzzleManager) ? $puzzleManager->getScore() : 0;
        $movesLeft = isset($puzzleManager) ? $puzzleManager->getMoves() : 0;

        // 盤面が空でないことを確認
        if ($boardSize > 0) {
            for ($row = 0; $row < $boardSize; $row++) {
                for ($col = 0; $col < $boardSize; $col++) {
                    // $board[$row][$col] には色の文字列（例: 'red'）が格納されている
                    $color = htmlspecialchars($board[$row][$col]);

                    echo '<div class="cell" data-row="' . $row . '" data-col="' . $col . '">';
                    // .piece divがピースを表す
                    echo '<div class="piece" style="background-color:' . $color . '"></div>';
                    echo '</div>';
                }
            }
        } else {
            echo '<div style="color: white; padding: 20px;">盤面データがありません。</div>';
        }
        ?>
    </div>
    <div id="right-ui">
        <div id="target-score">目標スコア: <span id="target-score-value"><?= $targetScore ?></span></div>
        <div id="score-display">スコア: <span id="score-value"><?= $currentScore ?></span></div>
        <div id="moves-display">残りライフ: <span id="moves-left-value"><?= $movesLeft ?></span></div>
    </div>
    <!-- リザルト用オーバーレイ -->
    <div id="result-overlay">
        <div id="result-message"></div>
    </div>
</div>
<form method="POST" action="index.php" id="title-return-form">
    <input type="hidden" name="action" value="titleScene">
    <button id="to-title-button" class="control-button" type="submit">タイトルへ戻る</button>
</form>

<div id="game-screen" class="scene-view screen active">
    <div id="puzzle-board">
        <?php
        // index.phpで$puzzleManagerが初期化されていることを前提とする
        $board = $puzzleManager->getBoard();
        $boardSize = count($board); // 盤面のサイズを取得
        
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
</div>
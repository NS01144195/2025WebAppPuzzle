<?php
require_once 'PuzzleManager.php';
$manager = new PuzzleManager();
$boardData = $manager->getBoard();
?>

<div id="game-container">
    <form method="POST" action="index.php" id="title-return-form">
        <input type="hidden" name="action" value="titleScene">
        <button id="to-title-button" class="control-button" type="submit">タイトルへ戻る</button>
    </form>

    <div id="game-screen" class="scene-view screen active">
        <div id="puzzle-board">
            <?php
            for ($row = 0; $row < 9; $row++) {
                for ($col = 0; $col < 9; $col++) {
                    $pieceId = $boardData[$row][$col];
                    echo '<div class="cell" data-row="' . $row . '" data-col="' . $col . '">';

                    // ピースはセルの中に完全に収まるように配置する
                    echo '  <div 
                            class="piece piece-' . $pieceId . '" 
                            data-piece-id="' . $pieceId . '"
                        ></div>';

                    // マス目の終了タグ
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</div>
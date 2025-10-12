<?php
$testBoard = [
    [1,2,3,4,5,1,2,3,4],
    [2,3,2,5,1,2,3,4,5],
    [3,4,5,1,2,3,4,5,1],
    [4,5,1,2,3,4,5,1,2],
    [5,1,2,3,4,5,1,2,3],
    [1,2,3,4,5,1,2,3,4],
    [2,3,4,5,1,2,3,4,5],
    [3,4,5,1,2,3,4,5,1],
    [4,5,1,2,3,4,5,1,2],
];
$puzzleManager->setBoard($testBoard);
$boardData = $puzzleManager->getBoard();
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
                    echo '<div class="piece piece-' . $pieceId . '" data-piece-id="' . $pieceId . '"></div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</div>
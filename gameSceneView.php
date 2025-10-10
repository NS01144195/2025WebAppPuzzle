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
                    echo '<div class="cell" data-row="' . $row . '" data-col="' . $col . '"></div>';
                }
            }
            ?>
        </div>
    </div>
</div>
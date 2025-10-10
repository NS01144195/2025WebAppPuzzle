<div id="game-screen" class="scene-view screen active">
    <div id="side-panel">
        <div class="score-display">
            <h2>スコア</h2>
            <p id="score"><?php echo $gameModel->score; ?></p>
        </div>
        <hr>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="titleScene">
            <button id="back-to-title" class="control-button" type="submit">タイトルへ戻る</button>
        </form>
    </div>
</div>
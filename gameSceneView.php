
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

<script>
document.addEventListener("DOMContentLoaded", () => {
    const cells = document.querySelectorAll(".cell");

    cells.forEach(cell => {
        cell.addEventListener("click", (event) => {
            const row = event.target.dataset.row;
            const col = event.target.dataset.col;

            console.log(`クリックされたセル: (${row}, ${col})`);

            // 例：セルを一時的にハイライト表示
            event.target.classList.add("active");
            setTimeout(() => event.target.classList.remove("active"), 300);
        });

        // モバイル向けにタッチも対応したい場合
        cell.addEventListener("touchstart", (event) => {
            const row = event.target.dataset.row;
            const col = event.target.dataset.col;

            console.log(`タップされたセル: (${row}, ${col})`);
        });
    });
});
</script>
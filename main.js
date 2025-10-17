document.addEventListener("DOMContentLoaded", () => {
    console.log("main.js loaded");

    // 最初にクリックされたセルを保持する変数
    let selectedCell = null;

    /**
     * 交換処理をシミュレートする関数
     * 実際にはここで隣接チェックとサーバー通信を行う
     * @param {HTMLElement} cell1 1つ目のセル
     * @param {HTMLElement} cell2 2つ目のセル
     */
    function handleExchange(cell1, cell2) {
        // ここに隣接チェックロジックが入ります
        
        // 現状はデモとして、選択情報をコンソールに出力
        console.log("================================");
        console.log("2つのセルが選択されました。交換処理に進みます。");
        console.log(`1つ目: (${cell1.dataset.row}, ${cell1.dataset.col})`);
        console.log(`2つ目: (${cell2.dataset.row}, ${cell2.dataset.col})`);
        console.log("================================");

        // 選択状態を解除
        cell1.classList.remove("selected");
        cell2.classList.remove("selected");
    }

    // セルのクリックイベントリスナー
    document.addEventListener("click", (event) => {
        const cell = event.target.closest(".cell");
        
        // セル以外がクリックされた場合は無視
        if (!cell) {
            // セル以外の場所をクリックした場合、選択解除するロジックを入れても良い
            if (selectedCell) {
                selectedCell.classList.remove("selected");
                selectedCell = null;
                console.log("盤面外がクリックされ、選択が解除されました。");
            }
            return;
        }

        // 座標を取得 (データ属性から)
        const row = cell.dataset.row;
        const col = cell.dataset.col;
        
        console.log(`クリックされたセル: (${row}, ${col})`);

        // 既に選択されているピースがあるかチェック
        if (selectedCell) {
            // 既に選択されているセルを再度クリックした場合（選択解除）
            if (selectedCell === cell) {
                cell.classList.remove("selected");
                selectedCell = null;
                console.log("ピースの選択が解除されました。");
            } else {
                // 2つ目のピースが選択された
                
                // 交換ロジックを実行
                handleExchange(selectedCell, cell);
                
                // 選択状態をリセット
                selectedCell = null;
            }
        } else {
            // 1つ目のピースが選択された
            
            // 視覚的な選択状態を反映
            cell.classList.add("selected");
            
            // 選択されたセルを保持
            selectedCell = cell;
            console.log("1つ目のピースが選択されました。");
        }
    });
});
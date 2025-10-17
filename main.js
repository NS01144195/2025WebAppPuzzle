document.addEventListener("DOMContentLoaded", () => {
    console.log("main.js loaded");

    // 最初にクリックされたセルを保持する変数
    let selectedCell = null;

    /**
     * セル内のピース要素を取得するユーティリティ関数
     * @param {HTMLElement} cell 
     * @returns {HTMLElement | null}
     */
    function getPiece(cell) {
        return cell ? cell.querySelector('.piece') : null;
    }

    /**
     * 2つのセルが上下左右に隣接しているかチェックする
     * @param {HTMLElement} cell1 1つ目のセル
     * @param {HTMLElement} cell2 2つ目のセル
     * @returns {boolean} 隣接していれば true
     */
    function isAdjacent(cell1, cell2) {
        // データ属性から行と列を取得し、数値に変換
        const r1 = parseInt(cell1.dataset.row);
        const c1 = parseInt(cell1.dataset.col);
        const r2 = parseInt(cell2.dataset.row);
        const c2 = parseInt(cell2.dataset.col);

        // 行と列の差分の絶対値を計算
        const rowDiff = Math.abs(r1 - r2);
        const colDiff = Math.abs(c1 - c2);

        // 隣接条件:
        // 1. (行差が1 かつ 列差が0) -> 上下隣接
        // 2. (行差が0 かつ 列差が1) -> 左右隣接
        // 斜め（行差も列差も1）は不可
        return (rowDiff + colDiff === 1);
    }

    /**
     * 交換処理をシミュレートする関数
     * 実際にはここで隣接チェックとサーバー通信を行う
     * @param {HTMLElement} cell1 1つ目のセル
     * @param {HTMLElement} cell2 2つ目のセル
     */
    function handleExchange(cell1, cell2) {
        const piece1 = getPiece(cell1);
        const piece2 = getPiece(cell2);
        
        // 隣接チェック
        if (isAdjacent(cell1, cell2)) {
            // 隣接している場合: 交換処理を続行
            console.log("隣接しています。サーバーに交換リクエストを送信します。");
            
            // TODO: ここにサーバーへのAJAXリクエスト送信ロジックが入る (次のステップ)
            
            // デモとして、選択状態を解除して終了
            if (piece1) piece1.classList.remove("selected");
            if (piece2) piece2.classList.remove("selected");
            return true; // 交換成功
        } else {
            // 隣接していない場合: 1つ目のピースの選択を解除しない
            // 2つ目のピースを新たな1つ目のピースとして扱うため、ここでは何もしない
            console.log("隣接していません。選択を更新します。");
            if (piece1) piece1.classList.remove("selected"); // 古い選択を解除
            // piece2には後続のロジックでselectedクラスが付与される
            return false; // 交換失敗
        }
    }

    // セルのクリックイベントリスナー
    document.addEventListener("click", (event) => {
        const cell = event.target.closest(".cell");
        
        // セル以外がクリックされた場合は無視
        if (!cell) {
            if (selectedCell) {
                const piece = getPiece(selectedCell);
                if (piece) piece.classList.remove("selected");
                selectedCell = null;
                console.log("盤面外がクリックされ、選択が解除されました。");
            }
            return;
        }

        // 座標を取得 (データ属性から)
        const row = cell.dataset.row;
        const col = cell.dataset.col;
        
        console.log(`クリックされたセル: (${row}, ${col})`);

        // 現在クリックされたセル内のピース要素を取得
        const currentPiece = getPiece(cell);
        if (!currentPiece) return; // ピースがない場合は何もしない

        // 既に選択されているピースがあるかチェック
        if (selectedCell) {
            // 既に選択されているセルを再度クリックした場合（選択解除）
            if (selectedCell === cell) {
                currentPiece.classList.remove("selected"); // ピースのクラスを削除
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
            
            // 視覚的な選択状態を反映 (ピースにクラスを付与)
            currentPiece.classList.add("selected");
            
            // 選択されたセルを保持
            selectedCell = cell;
            console.log("1つ目のピースが選択されました。");
        }
    });
});
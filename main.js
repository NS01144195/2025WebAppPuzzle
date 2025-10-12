
document.addEventListener("DOMContentLoaded", () => {
    const board = document.querySelector("#puzzle-board"); // 盤面全体の要素を指定
    if (!board) return;

    let startX, startY;
    let startRow, startCol;
    let isSwiping = false;

    // タッチ開始
    board.addEventListener("touchstart", (event) => {
        const touch = event.touches[0];
        startX = touch.clientX;
        startY = touch.clientY;

        const pieceElement = event.target.closest(".piece");
        if (!pieceElement) return;
        const parent = pieceElement.parentElement;
        startRow = parent.dataset.row;
        startCol = parent.dataset.col;
        isSwiping = true;
    });

    // タッチ終了（スワイプ方向を検出）
    board.addEventListener("touchend", (event) => {
        if (!isSwiping) return;
        const touch = event.changedTouches[0];
        handleSwipe(touch.clientX, touch.clientY);
        isSwiping = false;
    });

    // PC用マウス操作
    let isMouseDown = false;
    board.addEventListener("mousedown", (event) => {
        const pieceElement = event.target.closest(".piece");
        if (!pieceElement) return;
        startX = event.clientX;
        startY = event.clientY;
        const parent = pieceElement.parentElement;
        startRow = parent.dataset.row;
        startCol = parent.dataset.col;
        isMouseDown = true;
    });

    board.addEventListener("mouseup", (event) => {
        if (!isMouseDown) return;
        handleSwipe(event.clientX, event.clientY);
        isMouseDown = false;
    });

    function handleSwipe(endX, endY) {
        const diffX = endX - startX;
        const diffY = endY - startY;
        let direction = Math.abs(diffX) > Math.abs(diffY)
            ? (diffX > 0 ? "right" : "left")
            : (diffY > 0 ? "down" : "up");

        sendSwipe(startRow, startCol, direction);
    }

    function sendSwipe(row, col, direction) {
        const params = new URLSearchParams();
        params.append("action", "swipe");
        params.append("row", row);
        params.append("col", col);
        params.append("direction", direction);

        fetch("index.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: params.toString()
        })
        .then(res => res.json())
        .then(data => {
            // 盤面を更新
            const board = document.querySelector("#puzzle-board");
            const cells = board.querySelectorAll(".cell");
            for (let r = 0; r < 9; r++) {
                for (let c = 0; c < 9; c++) {
                    const piece = cells[r*9 + c].querySelector(".piece");
                    piece.className = "piece piece-" + data.board[r][c];
                    piece.dataset.pieceId = data.board[r][c];
                }
            }
        })
        .catch(err => console.error("通信エラー:", err));
    }
});
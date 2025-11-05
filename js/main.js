import { ApiController } from './ApiController.js';
import { ViewManager } from './ViewManager.js';

document.addEventListener('DOMContentLoaded', () => {

    const boardElement = document.getElementById('puzzle-board');
    if (!boardElement) return;

    const api = new ApiController();
    const view = new ViewManager();

    // シーン遷移は SceneManager (index.php 経由) に委譲するため
    // 隠しフォームで POST 送信して遷移させるユーティリティ
    const postScene = (action, extra = {}) => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        form.appendChild(actionInput);

        for (const [k, v] of Object.entries(extra)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = k;
            input.value = v;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    };

    // リザルトシーン遷移は SceneManager へ POST で指示
    document.addEventListener('app:result', async () => {
        postScene('resultScene');
    });

    let selectedCell = null;
    // INFO: アニメーション中の操作をブロックするフラグ。
    let isAnimating = false;

    /**
     * 2つのセルが隣接しているか判定する。
     * @param {HTMLElement} cell1
     * @param {HTMLElement} cell2
     * @returns {boolean}
     */
    const isAdjacent = (cell1, cell2) => {
        const r1 = parseInt(cell1.dataset.row);
        const c1 = parseInt(cell1.dataset.col);
        const r2 = parseInt(cell2.dataset.row);
        const c2 = parseInt(cell2.dataset.col);
        return Math.abs(r1 - r2) + Math.abs(c1 - c2) === 1;
    };

    // INFO: クリック操作を受け付けるイベントリスナー。
    boardElement.addEventListener('click', async (event) => {
        if (isAnimating) return; // NOTE: アニメーション中は入力を無効化する。

        const clickedCell = event.target.closest('.cell');
        if (!clickedCell || !view.getPiece(clickedCell)) {
            // NOTE: セル外をクリックした場合は選択状態を解除する。
            if (selectedCell) view.deselectPiece(view.getPiece(selectedCell));
            selectedCell = null;
            return;
        }

        if (!selectedCell) {
            // INFO: 1つ目のピースを選択状態にする。
            selectedCell = clickedCell;
            view.selectPiece(view.getPiece(selectedCell));
        } else {
            // INFO: 2つ目のピースを選択して入れ替え処理を開始する。
            const piece1 = view.getPiece(selectedCell);
            view.deselectPiece(piece1); // NOTE: 先に選択表示をリセットする。

            if (selectedCell === clickedCell) {
                // NOTE: 同じピースが選択された場合は処理を中断する。
                selectedCell = null;
                return;
            }

            if (!isAdjacent(selectedCell, clickedCell)) {
                // NOTE: 隣接していない場合は選択を解除して終了する。
                console.log("隣接していません。選択をリセットします。");
                selectedCell = null;
                return;
            }

            isAnimating = true;

            const r1 = parseInt(selectedCell.dataset.row);
            const c1 = parseInt(selectedCell.dataset.col);
            const r2 = parseInt(clickedCell.dataset.row);
            const c2 = parseInt(clickedCell.dataset.col);
            
            // NOTE: 見た目の入れ替えアニメーションを先に実施する。
            await view.animateSwap(piece1, view.getPiece(clickedCell));

            // INFO: API に交換リクエストを送信する。
            const result = await api.swapPieces(r1, c1, r2, c2);

            // INFO: サーバー結果に応じてアニメーションを分岐する。
            if (result.status === 'success' && result.chainSteps.length > 0) {
                for (const step of result.chainSteps) {
                    await view.animateRemove(step.matchedCoords);

                    // NOTE: 落下演出が自然になるよう短い待機を挟む。
                    await new Promise(resolve => setTimeout(resolve, 50));

                    await view.animateFallAndRefill(step.refillData);
                }
                view.updateScore(result.score);
                view.updateMoves(result.movesLeft);

                // INFO: ゲーム状態を判定して必要なら結果を表示する。
                if (result.gameState === 2) { // NOTE: CLEAR 状態。
                    view.showResult("ゲームクリア！");
                } else if (result.gameState === 3) { // NOTE: OVER 状態。
                    view.showResult("ゲームオーバー…");
                }

            } else {
                // NOTE: マッチしない場合は元の配置に戻す。
                await view.animateSwap(view.getPiece(selectedCell), view.getPiece(clickedCell));
            }

            selectedCell = null;
            isAnimating = false;
        }
    });
});

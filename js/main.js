import { ApiController } from './ApiController.js';
import { ViewManager } from './ViewManager.js';

document.addEventListener('DOMContentLoaded', () => {
    
    // --- 初期化処理 ---
    const boardElement = document.getElementById('puzzle-board');
    if (!boardElement) return;

    const api = new ApiController();
    const view = new ViewManager();
    
    let selectedCell = null;
    let isAnimating = false; // アニメーション中の操作をブロックするフラグ

    // --- イベントリスナー ---
    boardElement.addEventListener('click', async (event) => {
        if (isAnimating) return; // アニメーション中は操作不可

        const clickedCell = event.target.closest('.cell');
        if (!clickedCell || !view.getPiece(clickedCell)) {
            // セルやピース以外がクリックされたら選択解除
            if(selectedCell) view.deselectPiece(view.getPiece(selectedCell));
            selectedCell = null;
            return;
        }

        if (!selectedCell) {
            // 1. ピースの初回選択
            selectedCell = clickedCell;
            view.selectPiece(view.getPiece(selectedCell));
        } else {
            // 2. 2つ目のピース選択
            const piece1 = view.getPiece(selectedCell);
            view.deselectPiece(piece1); // 最初のピースの選択表示を解除

            if (selectedCell === clickedCell) {
                // 同じピースをクリックしたら選択解除
                selectedCell = null;
                return;
            }
            
            // --- メインロジック ---
            isAnimating = true;

            const r1 = parseInt(selectedCell.dataset.row);
            const c1 = parseInt(selectedCell.dataset.col);
            const r2 = parseInt(clickedCell.dataset.row);
            const c2 = parseInt(clickedCell.dataset.col);
            
            // 2-1. 見た目の交換アニメーション
            await view.animateSwap(piece1, view.getPiece(clickedCell));

            // 2-2. APIにリクエストを送信
            const result = await api.swapPieces(r1, c1, r2, c2);

            // 2-3. 結果に応じて連鎖アニメーションを再生
            if (result.status === 'success' && result.chainSteps.length > 0) {
                for (const step of result.chainSteps) {
                    await view.animateRemove(step.matchedCoords);

                    // ピースが消えた後、次のピースが落ちてくるまでに少しだけ待つ
                    await new Promise(resolve => setTimeout(resolve, 50));
                    
                    await view.animateFallAndRefill(step.refillData);
                }
                view.updateScore(result.score);
                view.updateMoves(result.movesLeft);

                // ゲーム状態をチェック
                if (result.gameState === 2) { // CLEAR
                    view.showResult("ゲームクリア！");
                } else if (result.gameState === 3) { // OVER
                    view.showResult("ゲームオーバー…");
                }

            } else {
                // マッチしなかった場合は元に戻すアニメーション
                await view.animateSwap(view.getPiece(selectedCell), view.getPiece(clickedCell));
            }
            
            selectedCell = null;
            isAnimating = false;
        }
    });
});
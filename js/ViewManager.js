export class ViewManager {
    /**
     * @constructor
     * UI 要素の参照を取得して初期化する。
     */
    constructor() {
        this.boardElement = document.getElementById('puzzle-board');
        this.scoreElement = document.getElementById('score-value');
        this.movesElement = document.getElementById('moves-left-value');
        this.resultOverlay = document.getElementById('result-overlay');
        this.resultMessage = document.getElementById('result-message');
    }

    /**
     * 指定されたセルのピース要素を取得する。
     * @param {HTMLElement} cell
     * @returns {HTMLElement | null}
     */
    getPiece(cell) {
        return cell ? cell.querySelector('.piece') : null;
    }

    /**
     * スコア表示を更新する。
     * @param {number} newScore
     * @returns {void}
     */
    updateScore(newScore) {
        if (this.scoreElement) this.scoreElement.textContent = newScore;
    }

    /**
     * 残り手数表示を更新する。
     * @param {number} newMoves
     * @returns {void}
     */
    updateMoves(newMoves) {
        if (this.movesElement) this.movesElement.textContent = newMoves;
    }

    /**
     * ピースを選択状態のデザインにする。
     * @param {HTMLElement} piece
     * @returns {void}
     */
    selectPiece(piece) {
        if (piece) piece.classList.add('selected');
    }

    /**
     * ピースの選択状態を解除する。
     * @param {HTMLElement} piece
     * @returns {void}
     */
    deselectPiece(piece) {
        if (piece) piece.classList.remove('selected');
    }

    /**
     * 2つのピースを交換するアニメーション。
     * @param {HTMLElement} piece1
     * @param {HTMLElement} piece2
     * @returns {Promise<void>}
     */
    async animateSwap(piece1, piece2) {
        if (!piece1 || !piece2) return;

        // INFO: 各ピースの位置情報を取得する。
        const rect1 = piece1.getBoundingClientRect();
        const rect2 = piece2.getBoundingClientRect();

        // INFO: CSS transform でスムーズに移動させる。
        piece1.style.transform = `translate(${rect2.left - rect1.left}px, ${rect2.top - rect1.top}px)`;
        piece2.style.transform = `translate(${rect1.left - rect2.left}px, ${rect1.top - rect2.top}px)`;

        // NOTE: トランジションが完了するまで待機する。
        await new Promise(resolve => setTimeout(resolve, 150));

        // NOTE: transform をリセットし、DOM 構造を入れ替える。
        piece1.style.transform = '';
        piece2.style.transform = '';
        const cell1 = piece1.parentElement;
        const cell2 = piece2.parentElement;
        cell1.appendChild(piece2);
        cell2.appendChild(piece1);
    }

    /**
     * マッチしたピースを消すアニメーション。
     * @param {Array<object>} matchedCoords
     * @returns {Promise<void>}
     */
    async animateRemove(matchedCoords) {
        const piecesToRemove = [];
        // NOTE: 一斉にクラスを付与して消滅エフェクトを開始する。
        for (const coord of matchedCoords) {
            const cell = this.boardElement.querySelector(`.cell[data-row="${coord.row}"][data-col="${coord.col}"]`);
            const piece = this.getPiece(cell);
            if (piece) {
                piece.classList.add('disappearing');
                piecesToRemove.push(piece);
            }
        }

        if (piecesToRemove.length === 0) return;

        // NOTE: アニメーション完了まで 200ms 待機する。
        await new Promise(resolve => setTimeout(resolve, 200));

        // NOTE: エフェクト終了後にピースを DOM から削除する。
        for (const piece of piecesToRemove) {
            piece.remove();
        }
    }

    /**
     * ピースの落下と補充のアニメーション。
     * @param {object} refillData
     * @returns {Promise<void>}
     */
    async animateFallAndRefill(refillData) {
        // NOTE: 補充前にピースを落下させる。
        for (const move of refillData.fallMoves) {
            const fromCell = this.boardElement.querySelector(`.cell[data-row="${move.from.row}"][data-col="${move.from.col}"]`);
            const toCell = this.boardElement.querySelector(`.cell[data-row="${move.to.row}"][data-col="${move.to.col}"]`);
            const piece = this.getPiece(fromCell);
            if (piece) toCell.appendChild(piece);
        }

        // NOTE: 空いたセルに新しいピースを追加する。
        for (const newPieceData of refillData.newPieces) {
            const cell = this.boardElement.querySelector(`.cell[data-row="${newPieceData.row}"][data-col="${newPieceData.col}"]`);
            if (cell) {
                const newPiece = document.createElement('div');
                newPiece.classList.add('piece');
                newPiece.style.backgroundColor = newPieceData.color;
                cell.appendChild(newPiece);
            }
        }
        await new Promise(resolve => setTimeout(resolve, 200)); // NOTE: 補充演出の余韻として 0.2 秒待つ。
    }

    /**
     * リザルト画面を表示し、指定秒数後にリダイレクトする。
     * @param {string} message
     * @returns {void}
     */
    showResult(message) {
        if (this.resultOverlay && this.resultMessage) {
            this.resultMessage.textContent = message;
            this.resultOverlay.style.display = 'flex';

            setTimeout(() => {
                // NOTE: メイン側に結果シーン遷移を依頼するイベントを発火
                document.dispatchEvent(new CustomEvent('app:result'));
            }, 2000);
        }
    }
}

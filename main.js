document.addEventListener("DOMContentLoaded", () => {
    console.log("main.js loaded");
    let isAnimating = false;

    /**
     * アニメーションの種類を定義する定数
     * Swap: ピース交換時の素早い移動
     * Drop: ピースが落下する際の重力感のある移動
     */
    const AnimationType = {
        Swap: 'swap',
        Drop: 'drop'
    };

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
     * ピースを指定されたセルへアニメーション付きで移動させる汎用関数 (改善版)
     * @param {HTMLElement} piece 動かすピース
     * @param {HTMLElement} targetCell 移動先のセル
     * @param {string} animationType AnimationType定数の値 ('swap' or 'drop')
     * @param {boolean} isPermanent DOM構造を恒久的に変更するか
     * @returns {Promise<void>} アニメーション完了時に解決するPromise
     */
    async function animateGoTo(piece, targetCell, animationType, isPermanent = false) {
        return new Promise(resolve => {
            if (!piece || !targetCell) return resolve();

            const sourceCell = piece.parentElement;
            if (!sourceCell) return resolve();

            const sourceRect = sourceCell.getBoundingClientRect();
            const targetRect = targetCell.getBoundingClientRect();
            const dx = targetRect.left - sourceRect.left;
            const dy = targetRect.top - sourceRect.top;

            const animationClass = animationType === AnimationType.Drop ? 'drop-animation' : 'swap-animation';

            // transitionを設定
            piece.style.transition = 'transform 0.3s ease';
            piece.classList.add(animationClass);

            const onTransitionEnd = (e) => {
                if (e.propertyName !== 'transform') return;
                clearTimeout(timeout);
                piece.removeEventListener('transitionend', onTransitionEnd);
                piece.style.removeProperty('transition');
                piece.style.removeProperty('transform');
                piece.classList.remove(animationClass);
                if (isPermanent) targetCell.appendChild(piece);
                resolve();
            };

            piece.addEventListener('transitionend', onTransitionEnd);

            // 強制レイアウト確定
            void piece.offsetWidth;

            // 実際に動かす
            piece.style.transform = `translate(${dx}px, ${dy}px)`;

            // 保険タイマー（transitionendが来なかった場合）
            const timeout = setTimeout(() => {
                piece.removeEventListener('transitionend', onTransitionEnd);
                piece.style.removeProperty('transition');
                piece.style.removeProperty('transform');
                piece.classList.remove(animationClass);
                if (isPermanent) targetCell.appendChild(piece);
                resolve();
            }, 600); // transition時間 + 余裕
        });
    }

    /**
     * 指定された座標にあるピースをアニメーション付きで削除する
     * @param {Array<Object>} coordsToRemove 削除するピースの座標リスト [{row: r, col: c}, ...]
     */
    /**
     * マッチしたピースを消すアニメーションを実行する (時間ベースの確実なバージョン)
     * @param {Array<object>} matchedCoords 消えるピースの座標配列
     */
    async function animateRemovePieces(matchedCoords) {
        if (!matchedCoords || matchedCoords.length === 0) {
            return;
        }

        const piecesToRemove = [];
        // 1. 消えるピースすべてに、同時にアニメーション開始クラスを付与
        for (const coord of matchedCoords) {
            const cell = document.querySelector(`.cell[data-row="${coord.row}"][data-col="${coord.col}"]`);
            const piece = getPiece(cell);
            if (piece) {
                piece.classList.add('disappearing');
                piecesToRemove.push(piece);
            }
        }

        // ピースがなければここで処理を終了
        if (piecesToRemove.length === 0) {
            return;
        }

        // 2. CSSで指定したアニメーション時間 (200ms = 0.2s) だけ待つ
        await new Promise(resolve => setTimeout(resolve, 200));

        // 3. 時間が来たら、アニメーションが終わったとみなし、すべてのピースをDOMから削除
        for (const piece of piecesToRemove) {
            if (piece.parentElement) {
                piece.parentElement.removeChild(piece);
            }
        }
        
        console.log("すべてのピースの削除が完了しました。");
    }

    /**
     * ピース交換から始まる一連の処理（交換、マッチ、連鎖）を実行し、アニメーションを再生する
     * @param {HTMLElement} cell1 
     * @param {HTMLElement} cell2 
     */
    async function handleExchange(cell1, cell2) {
        const piece1 = getPiece(cell1);
        const piece2 = getPiece(cell2);

        // 隣接していなければ処理を中断
        if (!isAdjacent(cell1, cell2)) {
            console.log("隣接していません。選択を更新します。");
            // 2つ目のピースを選択状態にする
            if (piece2) piece2.classList.add("selected");
            selectedCell = cell2;
            return;
        }

        console.log("隣接しています。交換アニメーションを開始します。");

        const r1 = parseInt(cell1.dataset.row);
        const c1 = parseInt(cell1.dataset.col);
        const r2 = parseInt(cell2.dataset.row);
        const c2 = parseInt(cell2.dataset.col);

        // 1. 最初にピースを交換する「見た目」のアニメーションを実行
        await Promise.all([
            animateGoTo(piece1, cell2, AnimationType.Swap, false),
            animateGoTo(piece2, cell1, AnimationType.Swap, false)
        ]);

        try {
            // 2. サーバーにリクエストを送信し、連鎖の全手順を受け取る
            const requestData = { action: 'swapPieces', r1, c1, r2, c2 };
            const response = await fetch('apiManager.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) throw new Error('サーバーとの通信に失敗しました。');

            const result = await response.json();

            console.log('APIからのレスポンス:', result);

            const chainSteps = result.chainSteps;

            // 3. マッチしたかどうかの判定（連鎖ステップが1つ以上あるか）
            if (chainSteps && chainSteps.length > 0) {
                console.log(`${chainSteps.length}回の連鎖が見つかりました。アニメーションを再生します。`);

                // マッチしたので、交換後のピース位置をDOM上で確定させる
                cell1.appendChild(piece2);
                cell2.appendChild(piece1);

                // 4. 連鎖アニメーションをループで再生 (修正版：僅かな遅延を追加)
                for (const step of chainSteps) {
                    // まず、ピースが消えるアニメーションが完全に終わるのを待つ
                    await animateRemovePieces(step.matchedCoords);

                    // ブラウザの描画タイミング問題を回避するため、ごく僅かなポーズを挟む
                    await new Promise(resolve => setTimeout(resolve, 50)); // 50ミリ秒(0.05秒)待つ

                    // その後、ピースの落下と補充のアニメーションを実行し、それが終わるのを待つ
                    await animateFallAndRefill(step.refillData);
                }

            } else {
                console.log("マッチしなかったため、ピースを元に戻します。");
                // マッチしなかった場合は、元に戻すアニメーションを実行
                await Promise.all([
                    animateGoTo(piece1, cell1, AnimationType.Swap, false),
                    animateGoTo(piece2, cell2, AnimationType.Swap, false)
                ]);
            }
        } catch (error) {
            console.error('処理中にエラーが発生しました:', error);
            // エラー時もピースを元に戻す
            await Promise.all([
                animateGoTo(piece1, cell1, AnimationType.Swap, false),
                animateGoTo(piece2, cell2, AnimationType.Swap, false)
            ]);
        }
    }

    /**
     * サーバーからのデータに基づき、ピースの落下と補充のアニメーションを実行する (修正版)
     * このバージョンでは、すべてのアニメーションを並行して実行し、完了後にDOMを一括更新することで、
     * アニメーションが途中で停止する問題を解決します。
     * * @param {object} refillData サーバーから受け取った落下・補充情報
     */
    async function animateFallAndRefill(refillData) {
        if (!refillData) return;

        const animationPromises = [];
        // アニメーション完了後にDOMを物理的に更新するための情報を保存する配列
        const fallUpdates = [];

        // --- 1. 落下アニメーションの準備 ---
        if (refillData.fallMoves && refillData.fallMoves.length > 0) {
            for (const move of refillData.fallMoves) {
                const fromCell = document.querySelector(`.cell[data-row="${move.from.row}"][data-col="${move.from.col}"]`);
                const toCell = document.querySelector(`.cell[data-row="${move.to.row}"][data-col="${move.to.col}"]`);
                const piece = getPiece(fromCell);

                if (piece && toCell) {
                    // isPermanentを 'false' に設定し、見た目のアニメーションだけを実行
                    animationPromises.push(animateGoTo(piece, toCell, AnimationType.Drop, false));
                    // アニメーション完了後にDOMを更新するため、移動情報を保存しておく
                    fallUpdates.push({ piece: piece, toCell: toCell });
                }
            }
        }

        // --- 2. 新規ピースの補充アニメーションの準備 ---
        if (refillData.newPieces && refillData.newPieces.length > 0) {
            for (const newPieceData of refillData.newPieces) {
                const targetCell = document.querySelector(`.cell[data-row="${newPieceData.row}"][data-col="${newPieceData.col}"]`);

                if (targetCell) {
                    // 新しいピースを作成して盤面に追加
                    const newPiece = document.createElement('div');
                    newPiece.classList.add('piece', 'new'); // 'new'クラスで初期状態（透明など）を制御
                    newPiece.style.backgroundColor = newPieceData.color;
                    targetCell.appendChild(newPiece);

                    // 少し遅れて 'new' クラスを削除し、出現アニメーションを開始
                    const promise = new Promise(resolve => {
                        setTimeout(() => {
                            newPiece.classList.remove('new');
                            // transitionの完了をもってPromiseを解決
                            newPiece.addEventListener('transitionend', resolve, { once: true });
                        }, 50); // わずかな遅延がアニメーションのトリガーに必要
                    });
                    animationPromises.push(promise);
                }
            }
        }

        // --- 3. すべてのアニメーションが完了するのを待つ ---
        await Promise.all(animationPromises);

        // --- 4. アニメーション完了後、落下したピースのDOM構造を一括で更新 ---
        for (const update of fallUpdates) {
            // アニメーションで適用されたtransformスタイルをリセット
            update.piece.style.transform = '';
            // ピースを新しい親セルに移動
            update.toCell.appendChild(update.piece);
        }

        // このログは、1つの連鎖ステップの落下・補充がすべて完了したことを示す
        console.log("落下/補充ステップが完了しました。");
    }

    // クリックイベントのメインハンドラ
    const board = document.getElementById('puzzle-board');
    if (board) {
        board.addEventListener("click", async (event) => {
            if (isAnimating) {
                console.log("アニメーション中のため入力は無視されます。");
                return;
            }

            const cell = event.target.closest(".cell");
            if (!cell || !getPiece(cell)) {
                if (selectedCell && getPiece(selectedCell)) getPiece(selectedCell).classList.remove("selected");
                selectedCell = null;
                return;
            }

                if (selectedCell) {
                // 先に、1つ目のピースの選択状態を解除する
                if (getPiece(selectedCell)) {
                    getPiece(selectedCell).classList.remove("selected");
                }
                
                // もし違うセルをクリックし、かつ隣接していれば交換処理を実行
                if (selectedCell !== cell && isAdjacent(selectedCell, cell)) {
                    isAnimating = true;
                    await handleExchange(selectedCell, cell);
                    isAnimating = false;
                }
                
                // 最後に、選択状態をリセットする
                selectedCell = null;

            } else {
                getPiece(cell).classList.add("selected");
                selectedCell = cell;
            }
        });
    }
});
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
    function animateGoTo(piece, targetCell, animationType, isPermanent = false) {
        return new Promise(resolve => {
            if (!piece || !targetCell) {
                resolve();
                return;
            }

            // ピース自身の位置ではなく、ピースの親である「出発点セル」を取得
            const sourceCell = piece.parentElement;
            if (!sourceCell) {
                console.error("ピースの親セルが見つかりません。", piece);
                resolve();
                return;
            }

            // 出発点セルと目標セルの位置を取得
            const sourceRect = sourceCell.getBoundingClientRect();
            const targetRect = targetCell.getBoundingClientRect();

            // セル間の距離を計算 (これがピースの移動距離になる)
            const dx = targetRect.left - sourceRect.left;
            const dy = targetRect.top - sourceRect.top;

            // アニメーション用のクラスを設定
            const animationClass = animationType === AnimationType.Drop ? 'drop-animation' : 'swap-animation';
            
            // アニメーション完了時の処理を一度だけ実行するリスナー
            const onTransitionEnd = () => {
                piece.removeEventListener('transitionend', onTransitionEnd);

                // アニメーション用のスタイルとクラスを解除
                piece.style.transform = '';
                piece.classList.remove(animationClass);
                
                // DOM構造を恒久的に変更する場合
                if (isPermanent) {
                    targetCell.appendChild(piece);
                }
                resolve();
            };
            piece.addEventListener('transitionend', onTransitionEnd);

            // アニメーションクラスを適用し、transformで移動を開始
            piece.classList.add(animationClass);
            piece.style.transform = `translate(${dx}px, ${dy}px)`;
        });
    }

    /**
     * 指定された座標にあるピースをアニメーション付きで削除する
     * @param {Array<Object>} coordsToRemove 削除するピースの座標リスト [{row: r, col: c}, ...]
     */
    async function animateRemovePieces(coordsToRemove) {
        // 削除アニメーションのPromiseを格納する配列
        const removalPromises = [];

        // 座標リストをループして、各ピースに削除アニメーションを適用
        for (const coord of coordsToRemove) {
            const cell = document.querySelector(`.cell[data-row="${coord.row}"][data-col="${coord.col}"]`);
            const piece = getPiece(cell);

            if (piece) {
                // Promiseを作成し、アニメーション完了後に解決する
                const promise = new Promise(resolve => {
                    // アニメーション完了を検知するイベントリスナー
                    piece.addEventListener('transitionend', () => {
                        piece.remove(); // DOMから完全に削除
                        resolve(); // Promiseを解決
                    }, { once: true }); // イベントを一回だけ実行する

                    // このクラスを付与することで、CSSで定義したアニメーションが開始される
                    piece.classList.add('disappearing');
                });
                removalPromises.push(promise);
            }
        }

        // すべての削除アニメーションが終わるまで待つ
        await Promise.all(removalPromises);
        console.log("すべてのピースの削除が完了しました。");
    }

    /**
     * 2つの隣接ピースを交換し、マッチングをチェックする処理
     * @param {HTMLElement} cell1 1つ目のセル (selectedCell)
     * @param {HTMLElement} cell2 2つ目のセル (current click)
     * @returns {boolean} 交換処理が続行されたかどうか (隣接していたか)
     */
    async function handleExchange(cell1, cell2) {
        const piece1 = getPiece(cell1);
        const piece2 = getPiece(cell2);

        // 選択状態の解除
        if (piece1) piece1.classList.remove("selected");
        if (piece2) piece2.classList.remove("selected");

        // 隣接チェック
        if (!isAdjacent(cell1, cell2)) {
            console.log("隣接していません。選択を更新します。");
            // 2つ目のピースを選択状態にする
            if (piece2) piece2.classList.add("selected");
            selectedCell = cell2; // 選択セルを更新
            return; // 処理を中断
        }

        // --- ここからが隣接していた場合の処理 ---
        isAnimating = true;
        console.log("隣接しています。交換アニメーションを開始します。");

        // 座標を整数に変換
        const r1 = parseInt(cell1.dataset.row);
        const c1 = parseInt(cell1.dataset.col);
        const r2 = parseInt(cell2.dataset.row);
        const c2 = parseInt(cell2.dataset.col);

        // 1. ピースをそれぞれのターゲットセルへ同時に移動
        await Promise.all([
            animateGoTo(piece1, cell2, AnimationType.Swap, false), // DOM変更はまだしない
            animateGoTo(piece2, cell1, AnimationType.Swap, false)
        ]);

        // 2. サーバーに交換情報を送信し、マッチングをチェック
        try {
            // APIに送信するデータを作成
            const requestData = {
                action: 'swapPieces',
                r1: r1, c1: c1,
                r2: r2, c2: c2
            };

            // apiManager.phpにPOSTリクエストを送信
            const response = await fetch('apiManager.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData) // JavaScriptオブジェクトをJSON文字列に変換
            });

            if (!response.ok) {
                // サーバーからの応答がエラーだった場合
                const errorData = await response.json();
                throw new Error(errorData.message || 'サーバーとの通信に失敗しました。');
            }

            const result = await response.json();
            console.log('APIからのレスポンス:', result);

            const matchedCoords = result.matchedCoords;

            // マッチが成立したか（座標リストが空でないか）をチェック
            if (matchedCoords && matchedCoords.length > 0) {
                console.log("マッチしました！ピースを確定し、削除します。");
                // DOM構造を物理的に変更してピースの位置を確定
                cell1.appendChild(piece2);
                cell2.appendChild(piece1);
                
                const removalPromise = animateRemovePieces(matchedCoords);
                const fallAndRefillPromise = animateFallAndRefill(result.refillData);
                
                // 両方のアニメーションが完了するのを待つ
                await Promise.all([removalPromise, fallAndRefillPromise]);

            } else {
                console.log("マッチしなかったため、ピースを元に戻します。");
                // 元に戻すアニメーション
                await Promise.all([
                    animateGoTo(piece1, cell1, AnimationType.Swap, false),
                    animateGoTo(piece2, cell2, AnimationType.Swap, false)
                ]);
            }

        } catch (error) {
            console.error('APIリクエスト中にエラーが発生しました:', error);
            // エラーが発生した場合も、見た目を元に戻す
            await Promise.all([
                animateGoTo(piece1, cell1, AnimationType.Swap, false),
                animateGoTo(piece2, cell2, AnimationType.Swap, false)
            ]);
        } finally {
            // アニメーション完了後に状態をリセット
            isAnimating = false;
            selectedCell = null;
        }
    }

    /**
     * サーバーからのデータに基づき、ピースの落下と補充のアニメーションを実行する
     * @param {object} refillData サーバーから受け取った落下・補充情報
     */
    async function animateFallAndRefill(refillData) {
        if (!refillData) return;

        const animationPromises = [];

        // 1. 落下アニメーションの処理 (fallMoves)
        if (refillData.fallMoves && refillData.fallMoves.length > 0) {
            for (const move of refillData.fallMoves) {
                const fromCell = document.querySelector(`.cell[data-row="${move.from.row}"][data-col="${move.from.col}"]`);
                const toCell = document.querySelector(`.cell[data-row="${move.to.row}"][data-col="${move.to.col}"]`);
                const piece = getPiece(fromCell);

                if (piece && toCell) {
                    console.log(`ピースを (${move.from.row}, ${move.from.col}) から (${move.to.row}, ${move.to.col}) へ落下させます。`);
                    // 落下専用のアニメーション(Drop)を使い、DOM構造も変更する
                    animationPromises.push(animateGoTo(piece, toCell, AnimationType.Drop, true));
                }
            }
        }

        // 2. 新規ピースの補充アニメーションの処理 (newPieces)
        if (refillData.newPieces && refillData.newPieces.length > 0) {
            for (const newPieceData of refillData.newPieces) {
                const targetCell = document.querySelector(`.cell[data-row="${newPieceData.row}"][data-col="${newPieceData.col}"]`);
                
                if (targetCell) {
                    // 新しいピースのDOM要素を生成
                    const newPiece = document.createElement('div');
                    newPiece.classList.add('piece', 'new'); // 最初は非表示
                    newPiece.style.backgroundColor = newPieceData.color;
                    
                    // セルに追加
                    targetCell.appendChild(newPiece);

                    // アニメーションのPromiseを作成
                    const promise = new Promise(resolve => {
                        // 少し遅延させてからアニメーションを開始すると自然に見える
                        setTimeout(() => {
                            // 'new'クラスを外すと、通常のスタイルが適用されてアニメーションが開始
                            newPiece.classList.remove('new');
                            
                            // アニメーション完了を待つ
                            newPiece.addEventListener('transitionend', resolve, { once: true });
                        }, 50); // 50ミリ秒の遅延
                    });
                    animationPromises.push(promise);
                }
            }
        }
        
        // すべての落下・補充アニメーションが終わるまで待つ
        await Promise.all(animationPromises);
        console.log("すべての落下と補充が完了しました。");
    }

    // クリックイベントのメインハンドラ
    const board = document.getElementById('puzzle-board');
    board.addEventListener("click", async (event) => {
        // isAnimatingフラグがtrue（アニメーション中）なら、いかなる操作も受け付けない
        if (isAnimating) {
            console.log("アニメーション中のため入力は無視されます。");
            return;
        }

        // クリックされた要素が.cellまたはその子要素かを確認
        const cell = event.target.closest(".cell");

        // 盤面の外側がクリックされた場合の処理
        if (!cell) {
            // もしピースを選択中だったら、選択を解除する
            if (selectedCell) {
                const piece = getPiece(selectedCell);
                if (piece) piece.classList.remove("selected");
                selectedCell = null;
                console.log("盤面外がクリックされ、選択が解除されました。");
            }
            return;
        }

        // クリックされたセルにピースが存在するか確認
        const currentPiece = getPiece(cell);
        if (!currentPiece) {
            // ピースがないセルがクリックされた場合、選択を解除
            if(selectedCell) {
                const piece = getPiece(selectedCell);
                if (piece) piece.classList.remove("selected");
                selectedCell = null;
            }
            return;
        }
        
        // --- ここからピース選択のロジック ---

        // 1つ目のピースが既に選択されている場合
        if (selectedCell) {
            // 選択中のセルをもう一度クリックした場合（選択解除）
            if (selectedCell === cell) {
                currentPiece.classList.remove("selected");
                selectedCell = null;
                console.log("ピースの選択が解除されました。");
            
            // 2つ目の、異なるセルがクリックされた場合
            } else {
                // --- ここから一連のアニメーション処理を開始 ---
                isAnimating = true; // 操作ロックを開始
                
                // ピースの交換、マッチ判定、削除、落下、補充の一連の処理を呼び出す
                await handleExchange(selectedCell, cell);
                
                // 【TODO】: 本来はこの後に「落下した結果、さらにマッチしていないか？」という
                // 連鎖判定のループ処理が入ります。
                
                isAnimating = false; // 全ての処理が終わったので操作ロックを解除
                // --- アニメーション処理ここまで ---

                // 処理が終わったら、選択状態をリセット
                if (getPiece(selectedCell)) getPiece(selectedCell).classList.remove("selected");
                selectedCell = null;
            }

        // 1つ目のピースがまだ選択されていない場合
        } else {
            currentPiece.classList.add("selected");
            selectedCell = cell;
            console.log(`セル (${cell.dataset.row}, ${cell.dataset.col}) を選択しました。`);
        }
    });
});
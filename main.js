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
     * 指定されたピースをターゲットのセルの位置までアニメーションで移動させる
     * @param {HTMLElement} piece ピース要素
     * @param {HTMLElement} targetCell 移動先のセル要素
     * @param {string} animationType AnimationTypeで定義されたアニメーションの種類
     * @param {boolean} permanent DOMの構造（親要素）も実際に変更するかどうか
     * @returns {Promise<void>} アニメーション完了時に解決するPromise
     */
    function animateGoTo(piece, targetCell, animationType = AnimationType.Swap, permanent = true) {
        return new Promise(resolve => {
            if (!piece || !targetCell) {
                resolve();
                return;
            }

            const currentCell = piece.parentElement;

            // 現在のセルの位置とターゲットセルの位置を取得
            const rectA = currentCell.getBoundingClientRect();
            const rectB = targetCell.getBoundingClientRect();

            // ピースが移動するために必要なX/Y移動距離
            const deltaX = rectB.left - rectA.left;
            const deltaY = rectB.top - rectA.top;

            // アニメーションタイプに応じてトランジション設定を決定
            let duration = '1s';
            let easing = 'ease-in-out'; // Swapのデフォルト

            if (animationType === AnimationType.Drop) {
                // 落下アニメーションの場合は、重力感のあるイージング (ease-in) を使用
                // 落下距離に応じて時間を調整することも可能だが、ここでは固定
                duration = '0.15s';
                easing = 'ease-in';
            }

            // アニメーションに必要なCSS変数を設定
            piece.style.setProperty('--dx', `${deltaX}px`);
            piece.style.setProperty('--dy', `${deltaY}px`);
            piece.style.setProperty('--duration', duration);
            piece.style.setProperty('--easing', easing);

            // アニメーションをトリガーするクラスを追加
            piece.classList.add('moving');

            // アニメーション完了を待つ
            piece.addEventListener('transitionend', function handler() {
                piece.removeEventListener('transitionend', handler);

                // アニメーション完了後、クラスとCSS変数を削除
                piece.classList.remove('moving');
                piece.style.removeProperty('--dx');
                piece.style.removeProperty('--dy');
                piece.style.removeProperty('--duration');
                piece.style.removeProperty('--easing');

                if (permanent) {
                    // 永続的な移動の場合、DOM構造を物理的に移動
                    targetCell.appendChild(piece);
                }

                resolve(); // Promiseを解決
            }, { once: true }); // イベントリスナーを一度だけ実行する
        });
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
        if (isAdjacent(cell1, cell2)) {
            isAnimating = true;
            console.log("隣接しています。交換アニメーションを開始します。");

            // 1. ピースをそれぞれのターゲットセルへ同時に移動 (Swapアニメーションを使用)
            await Promise.all([
                // permanent: true でDOM構造も物理的に変更される
                animateGoTo(piece1, cell2, AnimationType.Swap, true),
                animateGoTo(piece2, cell1, AnimationType.Swap, true)
            ]);

            // 【TODO: サーバー通信】
            // 2. サーバーへAJAXリクエストを送信し、マッチング判定を要求
            // ここではまだダミーのロジックを使用しますが、サーバー連携の場所を明確にします

            // 仮のAJAXリクエスト（実際は非同期通信を行う）
            const isMatch = await new Promise(resolve => {
                console.log("サーバーへのマッチングチェックをシミュレーション中...");
                // サーバー通信に時間がかかることをシミュレート
                setTimeout(() => {
                    // 暫定的に「マッチしなかった」として元の位置に戻る動作を確認
                    resolve(false);
                }, 300);
            });


            if (isMatch) {
                console.log("マッチしました。ピースの永続化と連鎖処理に進みます。");
                // 【TODO】: マッチした場合は、サーバーから返された新しい盤面データに基づいて
                // 削除・落下・補充のアニメーションとDOM更新を行う。

            } else {
                console.log("マッチしなかったため、ピースを元に戻します。");
                // 3. マッチしなかった場合、元に戻すアニメーションを実行
                await Promise.all([
                    // piece1 は現在 cell2 にあるので cell1 に戻す
                    animateGoTo(piece1, cell1, AnimationType.Swap, true),
                    // piece2 は現在 cell1 にあるので cell2 に戻す
                    animateGoTo(piece2, cell2, AnimationType.Swap, true)
                ]);
            }

            isAnimating = false;
            return true; // 交換処理が完了
        } else {
            // 隣接していない場合
            console.log("隣接していません。選択を更新します。");
            return false; // 交換失敗
        }
    }

    // セルのクリックイベントリスナー
    document.addEventListener("click", (event) => {
        if (isAnimating) {
            console.log("アニメーション中のためクリックを無視します。");
            return;
        }

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
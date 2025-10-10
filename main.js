// ====================
// 画面要素の取得
// ====================
const titleScreen = document.getElementById("title-screen");
const gameScreen = document.getElementById("game-screen");
const resultScreen = document.getElementById("result-screen");

const startButton = document.getElementById("start-button");
const backToTitle = document.getElementById("back-to-title");

const board = document.getElementById("board");

// ====================
// 画面切り替え関数
// ====================
function showScreen(screen) {
    // すべて非表示
    document.querySelectorAll(".screen").forEach(s => s.classList.remove("active"));
    // 指定画面のみ表示
    screen.classList.add("active");
}

// ====================
// タイトルからゲームへ
// ====================
startButton.addEventListener("click", () => {
    showScreen(gameScreen);
});

// ====================
// 結果からタイトルへ
// ====================
backToTitle.addEventListener("click", () => {
    showScreen(titleScreen);
});

// ====================
// ピースクリック処理
// ====================
let selectedPiece = null;

board.addEventListener('click', (e) => {
    const piece = e.target.closest('.piece');
    if (!piece) return; // ピース以外をクリックしたら無視

    const x = parseInt(piece.dataset.x, 10);
    const y = parseInt(piece.dataset.y, 10);

    // ↓↓↓ ピースの選択ロジックをここに移す ↓↓↓
    if (!selectedPiece) {
        // 1つ目のピースを選択
        selectedPiece = { x, y };
        piece.style.outline = '3px solid white';
    } else {
        // 2つ目のピース
        const second = { x, y };
        const direction = getDirection(selectedPiece, second);

        if (direction) {
            // PHPに送信し、thenでアニメーション処理を呼び出す
            sendMoveToServer({ ...selectedPiece, direction })
                .then(data => {
                    animateSwapAndFall(data.board, data.removed, selectedPiece, second);
                })
                .catch(error => {
                    console.error('移動処理エラー:', error);
                });
        }

        // 選択解除（即座にアウトラインを消す）
        // イベント移譲を使っているため、画面上の全てのピースに対してセレクタを実行する
        document.querySelectorAll(".piece").forEach(p => p.style.outline = '');
        selectedPiece = null;
    }
});

// アニメーションの制御と順序付け
function animateSwapAndFall(newBoardData, removedCoords, firstPiece, secondPiece) {
    const isMatched = removedCoords && removedCoords.length > 0;

    // スワップアニメーションの時間を変数化
    const SWAP_DURATION = 200; // 0.2秒
    const REMOVE_DURATION = 300; // 0.3秒
    const UPDATE_DELAY = 300; // 0.3秒

    // 1. スワップアニメーションの実行
    // マッチが発生した場合、スワップは「有効」と見なしアニメーションを行う
    if (isMatched) {
        animateSwap(firstPiece, secondPiece);
    }
    // ※ マッチしなかった場合に、スワップアニメーション後に元の位置に戻す処理（リバーススワップ）は
    //    今回は省略し、DOM更新を遅らせる時間としてのみ利用します。


    // 2. 削除アニメーションの実行 (スワップ時間後に開始)
    setTimeout(() => {

        // 削除ピースを非表示/消去
        removedCoords.forEach(coord => {
            // coord.x, coord.y を安全に参照
            const piece = document.querySelector(`.piece[data-x='${coord.x}'][data-y='${coord.y}']`);
            if (piece) removePiece(piece);
        });

        // 3. 落下と補充のUI更新
        // 削除アニメーション完了後に実行
        setTimeout(() => {
            updateBoardUI(newBoardData); // 落下アニメーションを開始
        }, REMOVE_DURATION);

    }, isMatched ? SWAP_DURATION : 0); // マッチした場合のみスワップアニメーションの時間だけ遅延
}

// 隣接方向判定
function getDirection(first, second) {
    const dx = second.x - first.x;
    const dy = second.y - first.y;

    if (dx === 1 && dy === 0) return 'right';
    if (dx === -1 && dy === 0) return 'left';
    if (dx === 0 && dy === 1) return 'down';
    if (dx === 0 && dy === -1) return 'up';

    return null; // 隣接していない場合
}

// PHPに送信
function sendMoveToServer(payload) {
    return fetch('move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
            return res.json();
        })
        .catch(err => {
            console.error('通信エラー', err);
            // UI にエラーメッセージ表示等
            throw err;
        });
}

// 既存の消去アニメーションを利用
function removePiece(piece) {
    piece.style.transition = 'transform 0.3s, opacity 0.3s';
    piece.style.transform = 'scale(0)';
    piece.style.opacity = '0';
    setTimeout(() => piece.remove(), 300);
}

function updateBoardUI(newBoardData) {
    const boardEl = document.getElementById("board");

    // 既存のピースリストを取得（新しいピース生成用にも使用）
    const existingPieces = {};
    document.querySelectorAll(".piece").forEach(p => {
        const key = `${p.dataset.x},${p.dataset.y}`;
        existingPieces[key] = p;
    });

    // 最終盤面に合わせてUIを更新/新規生成
    newBoardData.forEach(row => {
        row.forEach(cell => {
            const pieceKey = `${cell.x},${cell.y}`;
            let pieceEl = existingPieces[pieceKey];

            if (pieceEl) {
                // 既存のピース: 色と座標を更新 (座標の変更で落下アニメーションが発生)
                pieceEl.dataset.color = cell.color;
                pieceEl.style.background = cell.color;

                // CSS Gridで位置が制御されているため、座標のデータ属性だけを更新
                pieceEl.dataset.x = cell.x;
                pieceEl.dataset.y = cell.y;

            } else {
                // 2つ目のピース
                const second = { x, y };
                const direction = getDirection(selectedPiece, second);

                // ★重要: アニメーション用のピースデータをローカル変数にコピー
                const first = { ...selectedPiece };

                if (direction) {
                    // PHPに送信し、thenでアニメーション処理を呼び出す
                    sendMoveToServer({ ...first, direction }) // 送信ペイロードもコピーを使用
                        .then(data => {
                            // ★アニメーション関数にローカル変数 'first' を渡す
                            animateSwapAndFall(data.board, data.removed, first, second);
                        })
                        .catch(error => {
                            console.error('移動処理エラー:', error);
                        });
                }

                // 選択解除（即座にアウトラインを消す）
                document.querySelectorAll(".piece").forEach(p => p.style.outline = '');
                selectedPiece = null; // selectedPieceはここで安全にリセット
            }
        });
    });

    // スコアやタイマーの更新もここに追加
}

function animateSwap(firstPiece, secondPiece) {
    // 重要な修正: 引数が有効なオブジェクトかチェックする
    if (!firstPiece || !secondPiece || typeof firstPiece.x === 'undefined' || typeof secondPiece.x === 'undefined') {
        console.error("animateSwap: 無効なピースデータが渡されました。");
        return;
    }

    // 最新のDOMからピースを検索
    const el1 = document.querySelector(`.piece[data-x='${firstPiece.x}'][data-y='${firstPiece.y}']`);
    const el2 = document.querySelector(`.piece[data-x='${secondPiece.x}'][data-y='${secondPiece.y}']`);

    // どちらかのピースが見つからなかったら、即座に処理を中断する
    if (!el1 || !el2) {
        console.warn("animateSwap: スワップ対象のピースがDOMで見つかりません。");
        return;
    }

    // ↓ el1, el2 が存在する前提で処理を続ける ↓

    el1.style.transition = "transform 0.2s";
    el2.style.transition = "transform 0.2s";

    // 移動距離の計算
    // el1.offsetWidth が null の可能性があるため、el1 が存在することを確認
    const offsetWidth = el1.offsetWidth || 50; // デフォルト値を設定

    const dx = (secondPiece.x - firstPiece.x) * offsetWidth;
    const dy = (secondPiece.y - firstPiece.y) * offsetWidth;

    // 実際に移動
    el1.style.transform = `translate(${dx}px, ${dy}px)`;
    el2.style.transform = `translate(${-dx}px, ${-dy}px)`;

    // 終了後にアニメーション設定をクリアする
    setTimeout(() => {
        el1.style.transition = "";
        el2.style.transition = "";
        el1.style.transform = "";
        el2.style.transform = "";

    }, 200);
}
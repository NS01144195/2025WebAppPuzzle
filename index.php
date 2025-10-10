<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1600, height=900">
    <title>3 Match Puzzle</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div id="game-container">
        <!-- タイトル画面 -->
        <section id="title-screen" class="screen active">
            <h1>3 Match Puzzle</h1>
            <button id="start-button">スタート</button>
        </section>

        <!-- ゲーム画面 -->
        <section id="game-screen" class="screen">
            <div id="hud">
                <div id="score">スコア: 0</div>
                <div id="timer">タイマー: 60</div>
            </div>

            <div id="board">
                <?php
                include 'PuzzleManager.php';
                $puzzle = new PuzzleManager(); // 9x9盤面生成
                echo $puzzle->renderBoardHtml(); // HTMLとして出力
                ?>
            </div>

            <div id="controls">
                <button id="shuffle-button">シャッフル</button>
                <button id="restart-button">リスタート</button>
            </div>
        </section>

        <!-- 結果画面 -->
        <section id="result-screen" class="screen">
            <h2>ゲーム終了！</h2>
            <p id="final-score">スコア: 0</p>
            <button id="back-to-title">タイトルに戻る</button>
        </section>
    </div>

    <script src="main.js"></script>
</body>

</html>
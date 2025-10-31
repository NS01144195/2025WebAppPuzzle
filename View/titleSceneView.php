<?php
// このファイルは SceneManager によって読み込まれる
// タイトルシーン
?>
<div id="title-screen" class="scene-view screen active">
    <h1>3 MATCH PUZZLE</h1>

    <form method="POST" action="index.php">
        <input type="hidden" name="action" value="selectScene"> <button id="start-button" type="submit">ゲームスタート</button>
    </form>
    <form method="POST" action="index.php" style="margin-top: 40px;" onsubmit="return checkDebugPassword()">
        <input type="hidden" name="action" value="resetHighScore">
        <input type="hidden" name="password" id="debug-password-input">
        <button type="submit" id="reset-button">ハイスコアをリセット(デバッグ用)</button>
    </form>
    <script>
        function checkDebugPassword() {
            // JavaScriptのpromptでパスワード入力を求める
            const input = prompt("デバッグ用のパスワードを入力してください:");
            const correctPassword = "debug";

            if (input === correctPassword) {
                // 正しければ隠しフィールドにパスワードをセットしてフォームを送信
                document.getElementById('debug-password-input').value = input;
                return true; // trueを返すとフォームが送信される
            } else {
                // 間違っていれば警告を出し、フォームの送信をキャンセル
                alert("パスワードが違います。");
                return false; // falseを返すと送信がキャンセルされる
            }
        }
    </script>
</div>
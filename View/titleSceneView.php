<?php
// INFO: SceneManager から読み込まれるタイトルシーン。
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
            // INFO: prompt でパスワードを受け取り、デバッグ機能を制御する。
            const input = prompt("デバッグ用のパスワードを入力してください:");
            const correctPassword = "debug";

            if (input === correctPassword) {
                // NOTE: 正しい場合はフォームに値を設定して送信する。
                document.getElementById('debug-password-input').value = input;
                return true; // INFO: true を返すとフォーム送信が継続する。
            } else {
                // NOTE: 間違った場合は警告して送信を止める。
                alert("パスワードが違います。");
                return false; // INFO: false を返すと送信がキャンセルされる。
            }
        }
    </script>
</div>
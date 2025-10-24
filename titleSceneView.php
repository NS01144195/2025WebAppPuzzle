<?php
// このファイルは SceneManager によって読み込まれる
// タイトルシーン
?>
<div id="title-screen" class="scene-view screen active">
    <h1>3 MATCH PUZZLE</h1>

    <form method="POST" action="index.php">
        <input type="hidden" name="action" value="gameScene">
        <button id="start-button" type="submit">ゲームスタート</button>
    </form>
</div>
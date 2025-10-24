<?php
class SceneManager
{
    private string $currentScene;

    public function __construct()
    {
        // セッションにシーン情報がなければ 'title' を初期値とする
        $this->currentScene = $_SESSION['current_scene'] ?? 'title';
        $this->handleRequest();
    }

    /**
     * POSTリクエストを処理してシーンを切り替える
     */
    private function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return;
        }

        $action = $_POST['action'];
        $newScene = null;

        switch ($action) {
            case 'selectScene':
                $newScene = 'select';
                break;
            case 'gameScene':
                $newScene = 'game';
                // ゲーム開始時にセッションデータをリセット
                unset($_SESSION['board'], $_SESSION['score'], $_SESSION['movesLeft'], $_SESSION['gameState']);
                break;
            case 'titleScene':
                $newScene = 'title';
                break;
            case 'resultScene':
                $newScene = 'result';
                break;
        }

        if ($newScene) {
            $_SESSION['current_scene'] = $newScene;
            // POSTの再送信を防ぐためにリダイレクト
            header('Location: index.php');
            exit;
        }
    }

    /**
     * 現在のシーン名を返す
     * @return string
     */
    public function getCurrentScene(): string
    {
        return $this->currentScene;
    }

    /**
     * 現在のシーンに対応するビューファイルのパスを返す
     * @return string
     */
    public function getSceneViewFile(): string
    {
        return 'View/' . $this->currentScene . 'SceneView.php';
    }
}
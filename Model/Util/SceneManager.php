<?php
class SceneManager
{
    private string $currentScene;

    /**
     * セッションから現在のシーンを読み込み、リクエストを即時処理する。
     */
    public function __construct()
    {
        require_once 'SessionKeys.php';
        $this->currentScene = $_SESSION[SessionKeys::CURRENT_SCENE] ?? 'title';
        $this->handleRequest();
    }

    /**
     * POSTリクエストを処理してシーンを切り替える。
     */
    private function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return;
        }

        $action = $_POST['action'];
        $newScene = null;

        switch ($action) {
            case 'resetHighScore':
                $correctPassword = "debug";
                $submittedPassword = $_POST['password'] ?? '';

                if ($submittedPassword === $correctPassword) {
                    // NOTE: 正しいパスワード入力時のみハイスコア Cookie を消去する。
                    setcookie('highscore', '', time() - 3600, "/");
                }
                $newScene = 'title';
                break;
            case 'selectScene':
                $newScene = 'select';
                break;
            case 'gameScene':
                $newScene = 'game';
                if (isset($_POST['difficulty'])) {
                    $_SESSION[SessionKeys::DIFFICULTY] = $_POST['difficulty'];
                }
                // NOTE: 新しいゲーム開始時は前回の状態を破棄する。
                unset(
                    $_SESSION[SessionKeys::BOARD],
                    $_SESSION[SessionKeys::SCORE],
                    $_SESSION[SessionKeys::MOVES_LEFT],
                    $_SESSION[SessionKeys::GAME_STATE],
                    $_SESSION[SessionKeys::IS_NEW_HIGHSCORE]
                );
                break;
            case 'titleScene':
                $newScene = 'title';
                break;
            case 'resultScene':
                $newScene = 'result';
                break;
        }

        if ($newScene) {
            $_SESSION[SessionKeys::CURRENT_SCENE] = $newScene;
            // NOTE: F5での再送信を防ぐためにリダイレクトする。
            header('Location: index.php');
            exit;
        }
    }

    /**
     * 現在のシーン名を返す。
     * @return string
     */
    public function getCurrentScene(): string
    {
        return $this->currentScene;
    }

    /**
     * 現在のシーンに対応するビューファイルのパスを返す。
     * @return string
     */
    public function getSceneViewFile(): string
    {
        return 'View/' . $this->currentScene . 'SceneView.php';
    }
}

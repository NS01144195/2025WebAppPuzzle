<?php
require_once 'SceneDataPack.php';

class SceneManager
{
    private string $currentScene;
    private SceneDataPack $dataPack;

    /**
     * セッションから現在のシーンを読み込み、リクエストを即時処理する。
     */
    public function __construct()
    {
        require_once 'SessionKeys.php';
        $this->currentScene = $_SESSION[SessionKeys::CURRENT_SCENE] ?? 'title';
        $this->handleRequest();
        $this->dataPack = SceneDataPackStorage::load($this->currentScene);
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
        $dataPack = null;

        switch ($action) {
            case 'resetHighScore':
                $correctPassword = "debug";
                $submittedPassword = $_POST['password'] ?? '';

                if ($submittedPassword === $correctPassword) {
                    // NOTE: 正しいパスワード入力時のみハイスコア Cookie を消去する。
                    setcookie('highscore', '', time() - 3600, "/");
                }
                $newScene = 'title';
                $dataPack = new TitleSceneDataPack();
                break;
            case 'selectScene':
                $newScene = 'select';
                $highScore = isset($_COOKIE['highscore']) ? (int)$_COOKIE['highscore'] : 0;
                $dataPack = new SelectSceneDataPack($highScore);
                break;
            case 'gameScene':
                $newScene = 'game';
                if (isset($_POST['difficulty'])) {
                    $difficulty = (string)$_POST['difficulty'];
                } else {
                    $difficulty = 'normal';
                }
                $this->resetGameState();
                $dataPack = new GameSceneDataPack($difficulty);
                break;
            case 'titleScene':
                $newScene = 'title';
                $dataPack = new TitleSceneDataPack();
                break;
            case 'resultScene':
                $newScene = 'result';
                $dataPack = new ResultSceneDataPack(
                    (int)($_SESSION[SessionKeys::GAME_STATE] ?? 0),
                    (int)($_SESSION[SessionKeys::SCORE] ?? 0),
                    (int)($_SESSION[SessionKeys::MOVES_LEFT] ?? 0),
                    !empty($_SESSION[SessionKeys::IS_NEW_HIGHSCORE])
                );
                break;
        }

        if ($newScene) {
            $_SESSION[SessionKeys::CURRENT_SCENE] = $newScene;
            if ($dataPack instanceof SceneDataPack) {
                SceneDataPackStorage::save($dataPack);
            } else {
                SceneDataPackStorage::load($newScene);
            }
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

    /**
     * 現在のシーンデータパックを返す。
     */
    public function getDataPack(): SceneDataPack
    {
        return $this->dataPack;
    }

    private function resetGameState(): void
    {
        unset(
            $_SESSION[SessionKeys::BOARD],
            $_SESSION[SessionKeys::SCORE],
            $_SESSION[SessionKeys::MOVES_LEFT],
            $_SESSION[SessionKeys::GAME_STATE],
            $_SESSION[SessionKeys::IS_NEW_HIGHSCORE]
        );
    }
}

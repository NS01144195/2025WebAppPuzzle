<?php
require_once 'Util/Enums.php';
require_once 'GameState.php';
require_once 'Board.php';
require_once 'MatchFinder.php';

class GameController
{
    private GameState $gameState;
    private Board $board;
    private MatchFinder $matchFinder;

    public function __construct(string $difficulty = 'normal')
    {
        $this->gameState = new GameState($difficulty); // ← 難易度を渡す
        $this->board = new Board();
        $this->matchFinder = new MatchFinder();
    }

    /**
     * ゲーム画面の準備を行う
     */
    public function prepareGame(): void
    {
        if (!isset($_SESSION['board'])) {
            // セッションになければ、Boardに初期化を指示
            $this->board->initialize();
            // GameStateは最初から初期状態なので、そのまま保存
            $this->saveStateToSession();
        } else {
            // セッションにあれば、それをロード
            $this->loadStateFromSession();
        }
    }

    /**
     * プレイヤーのアクションを処理し、結果を返す
     * @param string $action 実行するアクション名
     * @param array $data アクションに必要なデータ
     * @return array フロントエンドに返すレスポンスデータ
     */
    public function handlePlayerAction(string $action, array $data): array
    {
        $this->loadStateFromSession();

        switch ($action) {
            case 'swapPieces':
                return $this->processSwap($data['r1'], $data['c1'], $data['r2'], $data['c2']);
            default:
                return ['status' => 'error', 'message' => '不明なアクションです。'];
        }
    }

    /**
     * ピース交換から連鎖までの処理を実行する
     * @param int $r1 交換するピース1の行
     * @param int $c1 交換するピース1の列
     * @param int $r2 交換するピース2の行
     * @param int $c2 交換するピース2の列
     * @return array フロントエンドに返すレスポンスデータ
     */
    private function processSwap(int $r1, int $c1, int $r2, int $c2): array
    {
        // ピースを交換してみる
        $this->board->swapPieces($r1, $c1, $r2, $c2);

        // MatchFinderにマッチがあるか確認してもらう
        $matchedCoords = $this->matchFinder->find($this->board);

        // マッチがなければ元に戻して終了
        if (empty($matchedCoords)) {
            $this->board->swapPieces($r1, $c1, $r2, $c2); // Swap back
            return ['status' => 'success', 'chainSteps' => []];
        }

        // マッチがあれば、連鎖処理を開始
        $this->gameState->useMove(); // 最初に1手消費
        $chainSteps = [];
        $comboCount = 0;

        while (!empty($matchedCoords)) {
            $comboCount++;
            
            // スコアを加算
            $this->gameState->addScoreForPieces(count($matchedCoords));

            // ピースを削除し、新しいピースを補充
            $this->board->removePieces($matchedCoords);
            $refillData = $this->board->refill();
            
            // フロント用のアニメーション情報を記録
            $chainSteps[] = [
                'matchedCoords' => $matchedCoords,
                'refillData' => $refillData
            ];

            // 補充後に新たなマッチがあるか再度確認
            $matchedCoords = $this->matchFinder->find($this->board);
        }

        // コンボボーナスを加算
        if ($comboCount > 1) {
            $this->gameState->addComboBonus($comboCount);
        }

        // 最終的なゲーム状態をセッションに保存
        $this->saveStateToSession();

        // フロントに返すレスポンスを作成
        $gameStatus = $this->gameState->getStatus();

        // isNewHighScoreフラグを初期化
        $isNewHighScore = false;

        // ゲームが終了したかチェック
        if ($gameStatus === GameStatus::CLEAR || $gameStatus === GameStatus::OVER) {
            // 現在のハイスコアをCookieから読み込む (なければ0)
            $highScore = $_COOKIE['highscore'] ?? 0;
            $currentScore = $this->gameState->getScore();

            // 今回のスコアがハイスコアを上回っていたら更新
            if ($currentScore > $highScore) {
                // Cookieに新しいハイスコアを保存
                setcookie('highscore', $currentScore, time() + (365 * 24 * 60 * 60), "/");
                $isNewHighScore = true; // ハイスコアを更新したのでフラグを立てる
            }
        }

        // ハイスコアを更新したかどうかの情報もセッションに保存
        if ($isNewHighScore) {
            $_SESSION['isNewHighScore'] = true;
        }

        return [
            'status' => 'success',
            'chainSteps' => $chainSteps,
            'score' => $this->gameState->getScore(),
            'movesLeft' => $this->gameState->getMovesLeft(),
            'gameState' => $gameStatus->value // Enumの値を返す
        ];
    }

    /**
     * セッションからゲーム状態をロードする
     */
    private function loadStateFromSession(): void
    {
        if (isset($_SESSION['board'])) {
            $this->board->setGrid($_SESSION['board']);
            $this->gameState->setScore($_SESSION['score'] ?? 0);
            $this->gameState->setMovesLeft($_SESSION['movesLeft'] ?? 0);
        }
    }

    /**
     * 現在のゲーム状態をセッションに保存する
     */
    private function saveStateToSession(): void
    {
        $_SESSION['board'] = $this->board->getGrid();
        $_SESSION['score'] = $this->gameState->getScore();
        $_SESSION['movesLeft'] = $this->gameState->getMovesLeft();
        $_SESSION['gameState'] = $this->gameState->getStatus()->value;
    }

    /**
     * Viewに渡すためのデータを返す
     */
    public function getViewData(): array
    {
        return [
            'board'        => $this->board->getGrid(),
            'currentScore' => $this->gameState->getScore(),
            'movesLeft'    => $this->gameState->getMovesLeft(),
            'targetScore'  => $this->gameState->getTargetScore(),
        ];
    }
}
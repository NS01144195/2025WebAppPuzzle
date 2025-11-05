<?php
require_once 'Util/Enums.php';
require_once 'Util/SessionKeys.php';
require_once 'GameState.php';
require_once 'Board.php';
require_once 'MatchFinder.php';

class GameController
{

    private GameState $gameState;
    private Board $board;
    private MatchFinder $matchFinder;

    /**
     * 難易度に応じたゲーム管理オブジェクトを初期化する。
     */
    public function __construct(string $difficulty = 'normal')
    {
        $this->gameState = new GameState($difficulty);
        $this->board = new Board();
        $this->matchFinder = new MatchFinder();
    }

    /**
     * ゲーム画面の準備を行う。
     */
    public function prepareGame(): void
    {
        if (!isset($_SESSION[SessionKeys::BOARD])) {
            // NOTE: 新規ゲームでは盤面を初期化してから保存する。
            $this->board->initialize();
            // INFO: 初期状態のスコアと手数をセッションに保持する。
            $this->saveStateToSession();
        } else {
            // INFO: 続きから再開する際はセッションデータを反映する。
            $this->loadStateFromSession();
        }
    }

    /**
     * プレイヤーのアクションを処理し、結果を返す。
     * @param string $action 実行するアクション名
     * @param array $data アクションに必要なデータ
     * @return array フロントエンドに返すレスポンスデータ
     */
    public function handlePlayerAction(string $action, array $data): array
    {
        $this->loadStateFromSession();

        if ($action === 'swapPieces') {
            if (!$this->isValidSwapRequest($data)) {
                return ['status' => 'error', 'message' => '不正な座標が指定されました。'];
            }

            return $this->processSwap((int)$data['r1'], (int)$data['c1'], (int)$data['r2'], (int)$data['c2']);
        }

        return ['status' => 'error', 'message' => '不明なアクションです。'];
    }

    /**
     * ピース交換から連鎖までの処理を実行する。
     * @param int $r1 交換するピース1の行
     * @param int $c1 交換するピース1の列
     * @param int $r2 交換するピース2の行
     * @param int $c2 交換するピース2の列
     * @return array フロントエンドに返すレスポンスデータ
     */
    private function processSwap(int $r1, int $c1, int $r2, int $c2): array
    {
        // NOTE: まずは見た目通りにピースを入れ替えてマッチを確認する。
        $this->board->swapPieces($r1, $c1, $r2, $c2);

        // INFO: 交換後の盤面にマッチが存在するか走査する。
        $matchedCoords = $this->matchFinder->find($this->board);

        // NOTE: マッチがなければ盤面を即座に元に戻す。
        if (empty($matchedCoords)) {
            $this->board->swapPieces($r1, $c1, $r2, $c2); // NOTE: マッチしなかったため盤面を元に戻す。
            return ['status' => 'success', 'chainSteps' => []];
        }

        // NOTE: マッチ成立時は連鎖処理と手数消費を開始する。
        $this->gameState->useMove();
        $chainSteps = [];
        $comboCount = 0;

        while (!empty($matchedCoords)) {
            $comboCount++;

            // INFO: 消えたピース分のスコアを反映する。
            $this->gameState->addScoreForPieces(count($matchedCoords));

            // NOTE: 盤面からピースを消し、落下と補充を適用する。
            $this->board->removePieces($matchedCoords);
            $refillData = $this->board->refill();

            // INFO: フロントエンドがアニメーションできるよう結果を保存する。
            $chainSteps[] = [
                'matchedCoords' => $matchedCoords,
                'refillData' => $refillData
            ];

            // INFO: 連鎖が続くか確認するため再度マッチ探索する。
            $matchedCoords = $this->matchFinder->find($this->board);
        }

        // INFO: 連鎖数に応じたボーナスを追加する。
        if ($comboCount > 1) {
            $this->gameState->addComboBonus($comboCount);
        }

        // INFO: 最新の盤面とスコア情報をセッションに残す。
        $this->saveStateToSession();

        // INFO: フロントへ返却するゲーム状態をまとめる。
        $gameStatus = $this->gameState->getStatus();

        // INFO: ハイスコア更新フラグを初期化する。
        $isNewHighScore = false;

        // NOTE: クリア・ゲームオーバー時はハイスコアを検証する。
        if ($gameStatus === GameStatus::CLEAR || $gameStatus === GameStatus::OVER) {
            // INFO: Cookie から既存ハイスコアを取得する。
            $highScore = $_COOKIE['highscore'] ?? 0;
            $currentScore = $this->gameState->getScore();

            // NOTE: スコアが上回った場合は Cookie を上書きする。
            if ($currentScore > $highScore) {
                // NOTE: Cookie の有効期限を1年に設定して保持する。
                setcookie('highscore', $currentScore, time() + (365 * 24 * 60 * 60), "/");
                $isNewHighScore = true; // NOTE: ビュー側で演出できるようフラグを残す。
            }
        }

        // INFO: ハイスコア更新時のみセッションにフラグを記録する。
        if ($isNewHighScore) {
            $_SESSION[SessionKeys::IS_NEW_HIGHSCORE] = true;
        }

        return [
            'status' => 'success',
            'chainSteps' => $chainSteps,
            'score' => $this->gameState->getScore(),
            'movesLeft' => $this->gameState->getMovesLeft(),
            'gameState' => $gameStatus->value // INFO: フロントで扱いやすいよう enum の値を渡す。
        ];
    }

    /**
     * セッションからゲーム状態をロードする。
     */
    private function loadStateFromSession(): void
    {
        if (isset($_SESSION[SessionKeys::BOARD])) {
            $this->board->setGrid($_SESSION[SessionKeys::BOARD]);
            $this->gameState->setScore($_SESSION[SessionKeys::SCORE] ?? 0);
            $this->gameState->setMovesLeft($_SESSION[SessionKeys::MOVES_LEFT] ?? 0);
        }
    }

    /**
     * 現在のゲーム状態をセッションに保存する。
     */
    private function saveStateToSession(): void
    {
        $_SESSION[SessionKeys::BOARD] = $this->board->getGrid();
        $_SESSION[SessionKeys::SCORE] = $this->gameState->getScore();
        $_SESSION[SessionKeys::MOVES_LEFT] = $this->gameState->getMovesLeft();
        $_SESSION[SessionKeys::GAME_STATE] = $this->gameState->getStatus()->value;
    }

    /**
     * Viewに渡すためのデータを返す。
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

    /**
     * swapPieces リクエストの入力値を検証する。
     */
    private function isValidSwapRequest(array $data): bool
    {
        foreach (['r1', 'c1', 'r2', 'c2'] as $key) {
            if (!isset($data[$key]) || !is_numeric($data[$key])) {
                return false;
            }
        }

        return true;
    }
}
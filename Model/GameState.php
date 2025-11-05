<?php
require_once 'Util/Enums.php';
class GameState
{
    private const DEFAULT_DIFFICULTY = 'normal';
    private const DIFFICULTY_SETTINGS = [
        'tutorial' => ['targetScore' => 500, 'moves' => 99],
        'easy'     => ['targetScore' => 1000, 'moves' => 30],
        'normal'   => ['targetScore' => 1500, 'moves' => 20],
        'hard'     => ['targetScore' => 2000, 'moves' => 15],
    ];
    private const PIECE_SCORE = 5;
    private const COMBO_BONUS = 10;

    private int $score = 0;
    private int $movesLeft;
    private int $targetScore;

    /**
     * 選択された難易度に応じて初期状態を構築する。
     * @param string $difficulty 難易度キー
     */
    public function __construct(string $difficulty = self::DEFAULT_DIFFICULTY)
    {
        $settings = self::DIFFICULTY_SETTINGS[$difficulty] ?? self::DIFFICULTY_SETTINGS[self::DEFAULT_DIFFICULTY];

        $this->targetScore = $settings['targetScore'];
        $this->movesLeft = $settings['moves'];
    }

    /**
     * 現在のスコアを返す。
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * 残り手数を返す。
     * @return int
     */
    public function getMovesLeft(): int
    {
        return $this->movesLeft;
    }

    /**
     * クリア条件となる目標スコアを返す。
     * @return int
     */
    public function getTargetScore(): int
    {
        return $this->targetScore;
    }

    /**
     * 現在のゲーム状態（プレイ中, クリア, オーバー）を判定して返す。
     * @return GameStatus
     */
    public function getStatus(): GameStatus
    {
        if ($this->score >= $this->targetScore) {
            return GameStatus::CLEAR;
        }
        if ($this->movesLeft <= 0) {
            return GameStatus::OVER;
        }
        return GameStatus::PLAYING;
    }

    /**
     * 現在のスコアを上書きする。
     * @param int $score 新しいスコア
     * @return void
     */
    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    /**
     * 残り手数を上書きする。
     * @param int $moves 新しい残り手数
     * @return void
     */
    public function setMovesLeft(int $moves): void
    {
        $this->movesLeft = $moves;
    }

    /**
     * 消したピース数に応じてスコアを加算する。
     * @param int $pieceCount ピース数
     * @return void
     */
    public function addScoreForPieces(int $pieceCount): void
    {
        $this->score += $pieceCount * self::PIECE_SCORE;
    }

    /**
     * 連鎖数に応じたコンボボーナスを加算する。
     * @param int $comboCount 連鎖数
     * @return void
     */
    public function addComboBonus(int $comboCount): void
    {
        $this->score += $comboCount * self::COMBO_BONUS;
    }

    /**
     * 残り手数を1消費する。
     * @return void
     */
    public function useMove(): void
    {
        if ($this->movesLeft > 0) {
            $this->movesLeft--;
        }
    }
}
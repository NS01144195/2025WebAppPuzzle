<?php
require_once 'Util/Enums.php';
class GameState
{
    private int $score = 0;
    private int $movesLeft;

    /**
     * ルール関連のしきい値を保持する。
     * デフォルト値は難易度により上書きされる。
     */
    private int $targetScore = 100;
    private int $startMovesLeft = 20;
    private int $pieceScore = 5;
    private int $comboBonus = 10;

    /**
     * 選択された難易度に応じて初期状態を構築する。
     */
    public function __construct(string $difficulty = 'normal')
    {
        switch ($difficulty) {
            case 'tutorial':
                $this->targetScore = 500;
                $this->startMovesLeft = 99;
                break;
            case 'easy':
                $this->targetScore = 1000;
                $this->startMovesLeft = 30;
                break;
            case 'hard':
                $this->targetScore = 2000;
                $this->startMovesLeft = 15;
                break;
            case 'normal':
            default:
                $this->targetScore = 1500;
                $this->startMovesLeft = 20;
                break;
        }
        $this->movesLeft = $this->startMovesLeft;
    }

    /**
     * 現在のスコアを返す。
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * 残り手数を返す。
     */
    public function getMovesLeft(): int
    {
        return $this->movesLeft;
    }

    /**
     * クリア条件となる目標スコアを返す。
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
     */
    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    /**
     * 残り手数を上書きする。
     */
    public function setMovesLeft(int $moves): void
    {
        $this->movesLeft = $moves;
    }

    /**
     * 消したピース数に応じてスコアを加算する。
     */
    public function addScoreForPieces(int $pieceCount): void
    {
        $this->score += $pieceCount * $this->pieceScore;
    }

    /**
     * 連鎖数に応じたコンボボーナスを加算する。
     */
    public function addComboBonus(int $comboCount): void
    {
        $this->score += $comboCount * $this->comboBonus;
    }

    /**
     * 残り手数を1消費する。
     */
    public function useMove(): void
    {
        if ($this->movesLeft > 0) {
            $this->movesLeft--;
        }
    }
}
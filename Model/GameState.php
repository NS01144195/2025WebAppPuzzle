<?php
require_once 'Util/Enums.php';
class GameState
{
    private int $score = 0;
    private int $movesLeft;
    
    // ゲームのルール設定
    private int $targetScore = 1000;
    private int $startMovesLeft = 20;
    private int $pieceScore = 5;
    private int $comboBonus = 10;

    public function __construct()
    {
        $this->movesLeft = $this->startMovesLeft;
    }

    // --- 状態の取得 (Getter) ---
    public function getScore(): int { return $this->score; }
    public function getMovesLeft(): int { return $this->movesLeft; }
    public function getTargetScore(): int { return $this->targetScore; }
    
    /**
     * 現在のゲーム状態（プレイ中, クリア, オーバー）を判定して返す
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

    // --- 状態の更新 (Setter/Mutator) ---
    public function setScore(int $score): void { $this->score = $score; }
    public function setMovesLeft(int $moves): void { $this->movesLeft = $moves; }
    
    public function addScoreForPieces(int $pieceCount): void
    {
        $this->score += $pieceCount * $this->pieceScore;
    }

    public function addComboBonus(int $comboCount): void
    {
        $this->score += $comboCount * $this->comboBonus;
    }

    public function useMove(): void
    {
        if ($this->movesLeft > 0) {
            $this->movesLeft--;
        }
    }
}
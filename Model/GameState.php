<?php
require_once 'Util/Enums.php';
class GameState
{
    private int $score = 0;
    private int $movesLeft;
    
    // ゲームのルール設定
    private int $targetScore = 100;
    private int $startMovesLeft = 20;
    private int $pieceScore = 5;
    private int $comboBonus = 10;

    public function __construct(string $difficulty = 'normal')
    {
        // 難易度に応じてパラメータを設定
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

    // 状態の取得
    public function getScore(): int { return $this->score; }
    public function getMovesLeft(): int { return $this->movesLeft; }
    public function getTargetScore(): int { return $this->targetScore; }
    
    /**
     * 現在のゲーム状態（プレイ中, クリア, オーバー）を判定して返す
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

    // 状態の更新
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
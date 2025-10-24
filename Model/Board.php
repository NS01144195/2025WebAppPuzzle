<?php
require_once 'Util/Enums.php';

class Board
{
    private array $grid;
    private int $size;

    public function __construct(int $size = 9)
    {
        $this->size = $size;
        $this->grid = array_fill(0, $size, array_fill(0, $size, null));
    }

    public function getSize(): int { return $this->size; }
    public function getGrid(): array { return $this->grid; }
    public function setGrid(array $grid): void { $this->grid = $grid; }
    public function getPiece(int $r, int $c): ?string { return $this->grid[$r][$c] ?? null; }

    /**
     * マッチが発生しないように盤面を初期化する
     */
    public function initialize(): void
    {
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                do {
                    $color = PieceColor::getRandomColor();
                    $this->grid[$r][$c] = $color;
                } while ($this->hasInitialMatch($r, $c));
            }
        }
    }
    
    /**
     * 初期配置でマッチが発生しているかチェックする
     */
    private function hasInitialMatch(int $r, int $c): bool
    {
        $color = $this->grid[$r][$c];
        if ($c >= 2 && $this->grid[$r][$c-1] === $color && $this->grid[$r][$c-2] === $color) return true;
        if ($r >= 2 && $this->grid[$r-1][$c] === $color && $this->grid[$r-2][$c] === $color) return true;
        return false;
    }

    /**
     * 指定した2つのピースを交換する
     */
    public function swapPieces(int $r1, int $c1, int $r2, int $c2): void
    {
        $tmp = $this->grid[$r1][$c1];
        $this->grid[$r1][$c1] = $this->grid[$r2][$c2];
        $this->grid[$r2][$c2] = $tmp;
    }

    /**
     * 指定した座標のピースを削除する
     */
    public function removePieces(array $coords): void
    {
        foreach ($coords as $coord) {
            $this->grid[$coord['row']][$coord['col']] = null;
        }
    }

    /**
     * ピースを下に詰めて、新しいピースを生成する
     * @return array 生成された新しいピースの情報
     */
    public function refill(): array
    {
        $fallMoves = [];
        $newPieces = [];

        // ピースを下に詰める
        for ($c = 0; $c < $this->size; $c++) {
            $emptyRow = -1;
            for ($r = $this->size - 1; $r >= 0; $r--) {
                if ($emptyRow === -1 && $this->grid[$r][$c] === null) $emptyRow = $r;
                if ($emptyRow !== -1 && $this->grid[$r][$c] !== null) {
                    $this->grid[$emptyRow][$c] = $this->grid[$r][$c];
                    $this->grid[$r][$c] = null;
                    $fallMoves[] = ['from' => ['row' => $r, 'col' => $c], 'to' => ['row' => $emptyRow, 'col' => $c]];
                    $emptyRow--;
                }
            }
        }

        // 新しいピースを生成
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->grid[$r][$c] === null) {
                    $color = PieceColor::getRandomColor();
                    $this->grid[$r][$c] = $color;
                    $newPieces[] = ['row' => $r, 'col' => $c, 'color' => $color];
                }
            }
        }
        return ['fallMoves' => $fallMoves, 'newPieces' => $newPieces];
    }
}
<?php
class MatchFinder
{
    /**
     * 盤面を受け取り、全てのマッチを検出して座標リストを返す
     * @param Board $board
     * @return array
     */
    public function find(Board $board): array
    {
        $size = $board->getSize();
        $grid = $board->getGrid();
        $matchBoard = array_fill(0, $size, array_fill(0, $size, false));
        
        // 水平方向のスキャン
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size - 2; $c++) {
                if ($grid[$r][$c] && $grid[$r][$c] === $grid[$r][$c+1] && $grid[$r][$c] === $grid[$r][$c+2]) {
                    $matchBoard[$r][$c] = $matchBoard[$r][$c+1] = $matchBoard[$r][$c+2] = true;
                }
            }
        }

        // 垂直方向のスキャン
        for ($c = 0; $c < $size; $c++) {
            for ($r = 0; $r < $size - 2; $r++) {
                if ($grid[$r][$c] && $grid[$r][$c] === $grid[$r+1][$c] && $grid[$r][$c] === $grid[$r+2][$c]) {
                    $matchBoard[$r][$c] = $matchBoard[$r+1][$c] = $matchBoard[$r+2][$c] = true;
                }
            }
        }

        $matches = [];
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matchBoard[$r][$c]) {
                    $matches[] = ['row' => $r, 'col' => $c];
                }
            }
        }
        return $matches;
    }
}
?>
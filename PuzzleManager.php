<?php
session_start();

class PuzzleManager
{
    public $size;
    public $colors;
    public $board; // 2次元配列で盤面を管理

    public function __construct($size = 9, $colors = ['red', 'blue', 'green', 'yellow', 'purple'])
    {
        $this->size = $size;
        $this->colors = $colors;
        if (isset($_SESSION['board'])) {
            $this->board = $_SESSION['board'];
        } else {
            $this->initBoard();
        }
    }

    // 盤面生成
    public function initBoard()
    {
        $this->board = [];
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                do {
                    $color = $this->colors[array_rand($this->colors)];
                    $conflict = false;

                    // 左2つと同じ色ならやり直し
                    if (
                        $x >= 2 &&
                        $this->board[$y][$x - 1]['color'] === $color &&
                        $this->board[$y][$x - 2]['color'] === $color
                    ) {
                        $conflict = true;
                    }

                    // 上2つと同じ色ならやり直し
                    if (
                        $y >= 2 &&
                        $this->board[$y - 1][$x]['color'] === $color &&
                        $this->board[$y - 2][$x]['color'] === $color
                    ) {
                        $conflict = true;
                    }

                } while ($conflict); // 同じ色が連続する場合は再抽選

                $this->board[$y][$x] = [
                    'x' => $x,
                    'y' => $y,
                    'color' => $color,
                    'special' => false // 後でギミック用
                ];
            }
        }
        $_SESSION['board'] = $this->board;
    }

    // HTML生成用
    public function renderBoardHtml()
    {
        $html = '';
        foreach ($this->board as $row) {
            foreach ($row as $cell) {
                $html .= "<div class='piece' data-x='{$cell['x']}' data-y='{$cell['y']}' data-color='{$cell['color']}' style='background: {$cell['color']}'></div>";
            }
        }
        return $html;
    }

    // ピース入れ替え
    public function swap($x, $y, $direction)
    {
        $dx = 0;
        $dy = 0;
        switch ($direction) {
            case 'up':
                $dy = -1;
                break;
            case 'down':
                $dy = 1;
                break;
            case 'left':
                $dx = -1;
                break;
            case 'right':
                $dx = 1;
                break;
        }

        $x2 = $x + $dx;
        $y2 = $y + $dy;

        if ($this->isValid($x2, $y2)) {
            // ピースデータを一旦保存
            $temp = $this->board[$y][$x];

            // 入れ替え: $this->board[$y][$x] に $this->board[$y2][$x2] のピースを代入
            $this->board[$y][$x] = $this->board[$y2][$x2];
            // 座標を更新
            $this->board[$y][$x]['x'] = $x;
            $this->board[$y][$x]['y'] = $y;

            // 入れ替え: $this->board[$y2][$x2] に $temp（元々の$this->board[$y][$x]のピース）を代入
            $this->board[$y2][$x2] = $temp;
            // 座標を更新
            $this->board[$y2][$x2]['x'] = $x2;
            $this->board[$y2][$x2]['y'] = $y2;

            $_SESSION['board'] = $this->board;
            return true;
        }
        return false;
    }

    // マッチ判定
    public function checkMatches()
    {
        $matchedCoords = []; // マッチした全ての座標を記録する配列
        $size = $this->size;

        // 1. 横方向のチェック
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size - 2; $x++) {
                $color = $this->board[$y][$x]['color'];
                if ($color === $this->board[$y][$x + 1]['color'] && $color === $this->board[$y][$x + 2]['color']) {
                    // 3つ以上マッチしている行の開始点を見つけた
                    $currentX = $x;
                    $matchGroup = [];
                    while ($currentX < $size && $this->board[$y][$currentX]['color'] === $color) {
                        $matchGroup[] = ['x' => $currentX, 'y' => $y];
                        $currentX++;
                    }

                    // マッチグループ全体を記録
                    $matchedCoords = array_merge($matchedCoords, $matchGroup);

                    // 次のチェックをマッチの終点から開始
                    $x = $currentX - 1;
                }
            }
        }

        // 2. 縦方向のチェック
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size - 2; $y++) {
                $color = $this->board[$y][$x]['color'];
                if ($color === $this->board[$y + 1][$x]['color'] && $color === $this->board[$y + 2][$x]['color']) {
                    // 3つ以上マッチしている列の開始点を見つけた
                    $currentY = $y;
                    $matchGroup = [];
                    while ($currentY < $size && $this->board[$currentY][$x]['color'] === $color) {
                        $matchGroup[] = ['x' => $x, 'y' => $currentY];
                        $currentY++;
                    }

                    // マッチグループ全体を記録
                    $matchedCoords = array_merge($matchedCoords, $matchGroup);

                    // 次のチェックをマッチの終点から開始
                    $y = $currentY - 1;
                }
            }
        }

        // 3. 重複の削除 (L字やT字のマッチに対応)
        // 座標 {x, y} をキーとして重複を排除
        $uniqueCoords = [];
        foreach ($matchedCoords as $coord) {
            $key = "{$coord['x']},{$coord['y']}";
            $uniqueCoords[$key] = $coord;
        }

        // 値（座標配列）のみを返す
        return array_values($uniqueCoords);
    }

    // 消去後ピース補充
    public function refillPieces()
    {
        $size = $this->size;

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                // 色が設定されていない (null) マスを見つける
                if (!isset($this->board[$y][$x]['color']) || $this->board[$y][$x]['color'] === null) {

                    // 新しいピースの情報を生成
                    $color = $this->colors[array_rand($this->colors)];
                    $this->board[$y][$x] = [
                        'x' => $x,
                        'y' => $y,
                        'color' => $color,
                        'special' => false
                    ];
                }
            }
        }
        // セッションに保存
        $_SESSION['board'] = $this->board;
    }

    public function dropPieces()
    {
        $size = $this->size;

        // 各列 (x) ごとに処理
        for ($x = 0; $x < $size; $x++) {
            $writeY = $size - 1; // ピースを書き込む最も下の行

            // 下から上へ (y) 逆順にスキャン
            for ($y = $size - 1; $y >= 0; $y--) {
                // 空のセルではない場合 (colorが設定されている場合)
                if (isset($this->board[$y][$x]['color']) && $this->board[$y][$x]['color'] !== null) {

                    // 書き込み位置 ($writeY) と現在の位置 ($y) が異なる場合、ピースを移動
                    if ($y !== $writeY) {
                        // ピースを下に移動
                        $this->board[$writeY][$x] = $this->board[$y][$x];

                        // 新しい座標を更新
                        $this->board[$writeY][$x]['y'] = $writeY;

                        // 元の場所を空にする
                        $this->board[$y][$x]['color'] = null;
                    }

                    // 書き込み位置を1つ上に移動
                    $writeY--;
                }
            }
        }
        // セッションに保存
        $_SESSION['board'] = $this->board;
    }

    // 盤面範囲チェック
    private function isValid($x, $y)
    {
        return $x >= 0 && $x < $this->size && $y >= 0 && $y < $this->size;
    }
}
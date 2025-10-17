<?php
enum PieceColor: string {
    case Red = 'red';
    case Blue = 'blue';
    case Green = 'green';
    case Yellow = 'yellow';
    case Purple = 'purple';
}

class PuzzleManager
{
    private $size;      // 盤面のサイズ
    private $colors;    // 使用する色
    private $board;     // 盤面

    // コンストラクタ
    public function __construct($size = 9) {
        $this->size = $size;
        $this->board = [];
    }

    // 盤面初期化
    /**
     * 新しいランダムな盤面を初期化する
     * 初期状態で3つ以上マッチしないようにピースを配置する
     */
    public function initBoard()
    {
        $colors = PieceColor::cases();
        $this->board = [];

        for ($row = 0; $row < $this->size; $row++) {
            $this->board[$row] = [];
            for ($col = 0; $col < $this->size; $col++) {
                
                // 3つマッチしない色を見つけるまでループ
                do {
                    $randomColorObject = $colors[array_rand($colors)];
                    $pieceColor = $randomColorObject->value;
                    
                    $isMatch = false;

                    // 1. 水平方向のマッチチェック (左2マス)
                    // 現在の列が2以上（col >= 2）の場合のみチェック可能
                    if ($col >= 2) {
                        $left1 = $this->board[$row][$col - 1]; // 左隣
                        $left2 = $this->board[$row][$col - 2]; // 左々隣
                        if ($left1 === $pieceColor && $left2 === $pieceColor) {
                            $isMatch = true;
                        }
                    }

                    // 2. 垂直方向のマッチチェック (上2マス)
                    // 現在の行が2以上（row >= 2）の場合のみチェック可能
                    if ($row >= 2) {
                        $up1 = $this->board[$row - 1][$col]; // 上隣
                        $up2 = $this->board[$row - 2][$col]; // 上々隣
                        if ($up1 === $pieceColor && $up2 === $pieceColor) {
                            $isMatch = true;
                        }
                    }

                } while ($isMatch);
                
                // マッチしない色が見つかったら盤面に設定
                $this->board[$row][$col] = $pieceColor;
            }
        }
    }

    /**
     * 現在の盤面データを返す
     * @return array
     */
    public function getBoard()
    {
        return $this->board;
    }

    /**
     * 外部から盤面データを設定する (セッションからのロード用)
     * @param array $board
     */
    public function setBoard(array $board)
    {
        $this->board = $board;
        // 盤面のサイズが変更される可能性を考慮し、サイズも更新
        $this->size = count($board);
    }
}
?>
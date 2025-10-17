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
     * 指定された2つの座標のピースを交換し、マッチが成立するか判定する。
     * マッチしない場合は、盤面を元に戻す。
     * * @param int $r1 1つ目のピースの行
     * @param int $c1 1つ目のピースの列
     * @param int $r2 2つ目のピースの行
     * @param int $c2 2つ目のピースの列
     * @return bool マッチが成立すればtrue、しなければfalseを返す
     */
    public function swapPieces($r1, $c1, $r2, $c2)
    {
        // 盤面の範囲外の座標が指定されていないかチェック
        if (!isset($this->board[$r1][$c1]) || !isset($this->board[$r2][$c2])) {
            return false; // エラーとしてfalseを返す
        }
        
        // --- 実際にピースを入れ替える ---
        $tempPiece = $this->board[$r1][$c1];
        $this->board[$r1][$c1] = $this->board[$r2][$c2];
        $this->board[$r2][$c2] = $tempPiece;

        // --- マッチ判定を実行 ---
        $isMatch = $this->checkMatches();

        // --- マッチしなかった場合の処理 ---
        if (!$isMatch) {
            // マッチしなかったので、盤面を元に戻す
            $tempPiece = $this->board[$r1][$c1];
            $this->board[$r1][$c1] = $this->board[$r2][$c2];
            $this->board[$r2][$c2] = $tempPiece;
        }
        
        // 判定結果を返す
        return $isMatch;
    }

    /**
     * 盤面全体をスキャンし、3つ以上のマッチがあるかチェックする
     * @return bool マッチがあればtrue、なければfalseを返す
     */
    public function checkMatches()
    {
        // 盤面全体をループ
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                
                $currentColor = $this->board[$r][$c];
                if ($currentColor === null) continue; // 空のセルはスキップ

                // --- 水平方向のチェック ---
                // 右に2マス以上ある場合のみチェック
                if ($c + 2 < $this->size) {
                    // 現在のピースと、右隣、右々隣のピースが同じ色か
                    if ($this->board[$r][$c+1] === $currentColor && $this->board[$r][$c+2] === $currentColor) {
                        return true; // マッチが見つかった
                    }
                }

                // --- 垂直方向のチェック ---
                // 下に2マス以上ある場合のみチェック
                if ($r + 2 < $this->size) {
                    // 現在のピースと、下隣、下々隣のピースが同じ色か
                    if ($this->board[$r+1][$c] === $currentColor && $this->board[$r+2][$c] === $currentColor) {
                        return true; // マッチが見つかった
                    }
                }
            }
        }

        // ループが終了してもマッチが見つからなかった場合
        return false;
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
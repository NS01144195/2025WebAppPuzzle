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
     * @return array マッチしたピースの座標リスト。マッチしなければ空の配列を返す。
     */
    public function swapPieces($r1, $c1, $r2, $c2)
    {
        if (!isset($this->board[$r1][$c1]) || !isset($this->board[$r2][$c2])) {
            return []; // エラーとして空の配列を返す
        }
        
        // ピースを入れ替え
        $tempPiece = $this->board[$r1][$c1];
        $this->board[$r1][$c1] = $this->board[$r2][$c2];
        $this->board[$r2][$c2] = $tempPiece;

        // マッチ判定を実行
        $matchedCoords = $this->findMatches();

        // マッチしなかった場合
        if (empty($matchedCoords)) {
            // 盤面を元に戻す
            $tempPiece = $this->board[$r1][$c1];
            $this->board[$r1][$c1] = $this->board[$r2][$c2];
            $this->board[$r2][$c2] = $tempPiece;
        }
        
        // マッチしたピースの座標リストを返す (マッチしなければ空配列が返る)
        return $matchedCoords;
    }

    /**
     * 盤面上の全てのマッチを検出し、マッチしたピースの座標リストを返す
     * @return array マッチしたピースの座標 [[row, col], [row, col], ...]
     */
    public function findMatches()
    {
        $matches = [];
        $boardSize = $this->size;
        
        // マッチ判定用の盤面を準備（重複して追加しないようにするため）
        $matchBoard = array_fill(0, $boardSize, array_fill(0, $boardSize, false));

        // 盤面全体をスキャン
        for ($r = 0; $r < $boardSize; $r++) {
            for ($c = 0; $c < $boardSize; $c++) {
                $currentColor = $this->board[$r][$c];
                if ($currentColor === null) continue;

                // 水平方向の3マッチをチェック
                if ($c + 2 < $boardSize && $this->board[$r][$c+1] === $currentColor && $this->board[$r][$c+2] === $currentColor) {
                    $matchBoard[$r][$c] = true;
                    $matchBoard[$r][$c+1] = true;
                    $matchBoard[$r][$c+2] = true;
                }

                // 垂直方向の3マッチをチェック
                if ($r + 2 < $boardSize && $this->board[$r+1][$c] === $currentColor && $this->board[$r+2][$c] === $currentColor) {
                    $matchBoard[$r][$c] = true;
                    $matchBoard[$r+1][$c] = true;
                    $matchBoard[$r+2][$c] = true;
                }
            }
        }

        // マッチ判定用盤面を元に、座標のリストを作成
        for ($r = 0; $r < $boardSize; $r++) {
            for ($c = 0; $c < $boardSize; $c++) {
                if ($matchBoard[$r][$c]) {
                    $matches[] = ['row' => $r, 'col' => $c];
                }
            }
        }

        return $matches;
    }

    /**
     * 盤面の空マスを下に詰め（補充）、空いた上部を新しいピースで生成する。
     * @return array 落下したピースの移動情報と、新しく生成されたピースの情報
     */
    public function generateAndRefillPieces()
    {
        $fallMoves = []; // どのピースがどこからどこへ落ちたか
        $newPieces = []; // どの座標にどの色のピースが新しく生まれたか
        
        // --- 1. 補充処理 (ピースを下に詰める) ---
        // 各「列」ごとに下から上へチェックしていく
        for ($c = 0; $c < $this->size; $c++) {
            $emptyRow = -1; // その列で最初に見つかった空マスの行を記録

            // 下の行から上の行へスキャン
            for ($r = $this->size - 1; $r >= 0; $r--) {
                
                // まだ空マスが見つかっていない状態で、空マスを発見した場合
                if ($emptyRow === -1 && $this->board[$r][$c] === null) {
                    $emptyRow = $r;
                    continue; // 次の行へ
                }

                // 空マスが見つかっている状態で、ピースのあるマスを発見した場合
                if ($emptyRow !== -1 && $this->board[$r][$c] !== null) {
                    // ピースを空マスへ移動させる
                    $this->board[$emptyRow][$c] = $this->board[$r][$c];
                    $this->board[$r][$c] = null;

                    // 「どこからどこへ動いたか」を記録
                    $fallMoves[] = [
                        'from' => ['row' => $r, 'col' => $c],
                        'to'   => ['row' => $emptyRow, 'col' => $c]
                    ];

                    // 次の空マスは、今移動させた空マスの1つ上になる
                    $emptyRow--;
                }
            }
        }

        // --- 2. 生成処理 (新しいピースを作る) ---
        // 盤面を再度スキャンし、残っているnullのマスを新しいピースで埋める
        $colors = PieceColor::cases();
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->board[$r][$c] === null) {
                    // 新しいランダムなピースを生成
                    $randomColorObject = $colors[array_rand($colors)];
                    $pieceColor = $randomColorObject->value;
                    $this->board[$r][$c] = $pieceColor;

                    // 「どの座標にどの色のピースが生まれたか」を記録
                    $newPieces[] = [
                        'row' => $r,
                        'col' => $c,
                        'color' => $pieceColor
                    ];
                }
            }
        }

        // フロントエンドでアニメーションさせるための情報を返す
        return [
            'fallMoves' => $fallMoves,
            'newPieces' => $newPieces
        ];
    }

    /**
     * 指定された座標リストにあるピースを盤面から削除（nullに設定）する
     * @param array $matchedCoords 削除するピースの座標リスト
     */
    public function removePieces($matchedCoords)
    {
        foreach ($matchedCoords as $coords) {
            $this->board[$coords['row']][$coords['col']] = null;
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
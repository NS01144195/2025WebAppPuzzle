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
    private $board;     // 盤面
    private $score;
    private $movesLeft;

    private $targetScore = 1000; // 目標スコア
    private $pieceScore = 5; // ピース1つあたりの得点
    private $comboBonus = 10; // 連鎖ごとのボーナス点
    private $startMovesLeft = 1; // ゲーム開始時の手数

    // ゲーム進行状態
    const GAME_STATE_PLAYING = 1;   // まだプレイ中
    const GAME_STATE_CLEAR   = 2;   // クリア
    const GAME_STATE_OVER    = 3;   // ゲームオーバー

    // コンストラクタ
    public function __construct($size = 9) {
        $this->size = $size;
        $this->board = [];
        $this->score = 0;
        $this->movesLeft = $this->startMovesLeft;
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
        $this->score = 0;
        $this->movesLeft = $this->startMovesLeft;
        $_SESSION['score'] = $this->score;
        $_SESSION['movesLeft'] = $this->movesLeft;

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
     * プレイヤーのピース交換操作から始まる一連の処理（交換、連鎖）をすべて実行する
     * @param int $r1 1つ目のピースの行
     * @param int $c1 1つ目のピースの列
     * @param int $r2 2つ目のピースの行
     * @param int $c2 2つ目のピースの列
     * @return array 全てのアニメーションステップを格納した配列
     */
    public function processPlayerSwap($r1, $c1, $r2, $c2)
    {
        $chainSteps = [];
        $matchedCoords = $this->swapPieces($r1, $c1, $r2, $c2);
        
        if (empty($matchedCoords)) {
            return []; // マッチしなければここで終了
        }

        $isMatch = true;
        while ($isMatch) {
            $this->removePieces($matchedCoords);
            $refillData = $this->generateAndRefillPieces();
            $chainSteps[] = [
                'matchedCoords' => $matchedCoords,
                'refillData' => $refillData
            ];
            $matchedCoords = $this->findMatches();
            $isMatch = !empty($matchedCoords); // マッチがあればループが続く
        }

        $this->useMove(); // 手数を減らす
        // スコア計算
        // コンボ数ボーナス
        $this->addScore(count($chainSteps) * $this->comboBonus);

        return $chainSteps;
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
     * @return array マッチしたピースの座標リスト
     */
    public function findMatches()
    {
        $matchBoard = array_fill(0, $this->size, array_fill(0, $this->size, false));

        // --- 水平方向のスキャン ---
        for ($r = 0; $r < $this->size; $r++) {
            $count = 1;
            for ($c = 1; $c < $this->size; $c++) {
                // 現在のピースが左隣と同じ色で、nullでなければカウントを増やす
                if ($this->board[$r][$c] !== null && $this->board[$r][$c] === $this->board[$r][$c-1]) {
                    $count++;
                } else {
                    // 色が変わった時点で、それまでのカウントが3以上ならマッチとして記録
                    if ($count >= 3) {
                        for ($i = 0; $i < $count; $i++) {
                            $matchBoard[$r][$c - 1 - $i] = true;
                        }
                    }
                    // カウントをリセット
                    $count = 1;
                }
            }
            // 行の最後に到達した時点で、カウントが3以上ならマッチとして記録
            if ($count >= 3) {
                for ($i = 0; $i < $count; $i++) {
                    $matchBoard[$r][$this->size - 1 - $i] = true;
                }
            }
        }

        // --- 垂直方向のスキャン ---
        for ($c = 0; $c < $this->size; $c++) {
            $count = 1;
            for ($r = 1; $r < $this->size; $r++) {
                // 現在のピースが上隣と同じ色で、nullでなければカウントを増やす
                if ($this->board[$r][$c] !== null && $this->board[$r][$c] === $this->board[$r-1][$c]) {
                    $count++;
                } else {
                    // 色が変わった時点で、それまでのカウントが3以上ならマッチとして記録
                    if ($count >= 3) {
                        for ($i = 0; $i < $count; $i++) {
                            $matchBoard[$r - 1 - $i][$c] = true;
                        }
                    }
                    // カウントをリセット
                    $count = 1;
                }
            }
            // 列の最後に到達した時点で、カウントが3以上ならマッチとして記録
            if ($count >= 3) {
                for ($i = 0; $i < $count; $i++) {
                    $matchBoard[$this->size - 1 - $i][$c] = true;
                }
            }
        }

        // マッチ判定用盤面を元に、座標のリストを作成
        $matches = [];
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
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
            $this->addScore($this->pieceScore); // ピース1つあたり5点加算
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

    public function setScore(int $score) { $this->score = $score; }
    public function setMoves(int $moves) { $this->movesLeft = $moves; }

    public function getScore() { return $this->score; }
    public function getMoves() { return $this->movesLeft; }

    public function addScore(int $points) { $this->score += $points; }
    public function useMove() { if ($this->movesLeft > 0) $this->movesLeft--; }
    
    /**
     * ゲームの現在状態を返す
     * @return int 1:プレイ中, 2:クリア, 3:ゲームオーバー
     */
    public function getGameState()
    {
        if ($this->score >= $this->targetScore) {
            return self::GAME_STATE_CLEAR; // スコア到達でクリア
        }

        if ($this->movesLeft <= 0) {
            return self::GAME_STATE_OVER; // 手数ゼロでゲームオーバー
        }

        return self::GAME_STATE_PLAYING; // それ以外はプレイ中
    }
}
?>
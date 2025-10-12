<?php

class PuzzleManager
{
    // 盤面のサイズ
    private const ROWS = 9;
    private const COLS = 9;

    // ブロックの種類とIDを定義（1=赤、2=青、3=緑、4=黄、5=紫）
    private const PIECE_TYPES = [1, 2, 3, 4, 5];
    // ゲーム盤のデータ (9x9の二次元配列)
    private $board = [];

    /**
     * ゲーム盤を初期化し、初期ブロックを配置します。
     */
    public function startGame()
    {
        // まず盤面を初期化
        $this->initializeBoard();

        // 3マッチが発生しないようにブロックをランダムに配置
        $this->populateInitialBoard();
    }

    /**
     * 盤面を空（0）で初期化します。
     */
    private function initializeBoard()
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            // 各行を初期化
            $this->board[$r] = array_fill(0, self::COLS, 0);
        }
    }

    /**
     * 初期盤面にブロックをランダムに配置します。
     * ただし、初期状態で3マッチが発生しないことを保証します。
     */
    private function populateInitialBoard()
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                $block = $this->generateNonMatchingBlock($r, $c);
                $this->board[$r][$c] = $block;
            }
        }
    }

    /**
     * 指定された位置(r, c)で、既に配置されているブロックと3マッチしないブロックIDをランダムに選んで返します。
     * @param int $r 行インデックス
     * @param int $c 列インデックス
     * @return int 新しいブロックID
     */
    private function generateNonMatchingBlock(int $r, int $c): int
    {
        // 利用可能なピースタイプからランダムに選ぶ
        $availableTypes = self::PIECE_TYPES;
        $newBlock = 0;

        do {
            // ランダムにタイプを選ぶ
            $newBlock = $availableTypes[array_rand($availableTypes)];

            // 垂直方向のマッチチェック（上の2マスをチェック）
            $matchV = ($r >= 2 &&
                $this->board[$r - 1][$c] === $newBlock &&
                $this->board[$r - 2][$c] === $newBlock);

            // 水平方向のマッチチェック（左の2マスをチェック）
            $matchH = ($c >= 2 &&
                $this->board[$r][$c - 1] === $newBlock &&
                $this->board[$r][$c - 2] === $newBlock);

            // マッチが発生した場合、そのブロックIDを除外リストに追加し、再度選択を試みる
            if ($matchV || $matchH) {
                // 選択したブロックを一時的に利用可能リストから削除
                // これにより無限ループのリスクを減らし、別のブロックを選ぶように強制する
                $key = array_search($newBlock, $availableTypes);
                if ($key !== false) {
                    unset($availableTypes[$key]);
                }

                // 全てのブロックタイプを試してしまった場合、ループを抜ける（実際には起こりにくい）
                if (empty($availableTypes)) {
                    // やむを得ずマッチするブロックを配置する（非常に稀なケース）
                    return $newBlock;
                }
            }

        } while ($matchV || $matchH); // マッチが発生しなくなるまで繰り返す

        return $newBlock;
    }

    /**
     * 現在のゲーム盤の状態を返します。
     * @return array 盤面データ
     */
    public function getBoard(): array
    {
        return $this->board;
    }

    /**
     * ゲーム盤をセット
     */
    public function setBoard(array $newBoard)
    {
        $this->board = $newBoard;
    }
}
?>
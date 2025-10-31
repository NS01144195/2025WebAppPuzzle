<?php
/**
 * パズルのピースの色を定義するEnum
 */
enum PieceColor: string
{
    case Red = '#E74C3C';     // フラットな赤
    case Blue = '#3498DB';    // フラットな青
    case Green = '#2ECC71';   // フラットな緑
    case Yellow = '#F1C40F';  // フラットな黄色
    case Purple = '#9B59B6';  // フラットな紫

    /**
     * ランダムな色を返す静的メソッド
     */
    public static function getRandomColor(): string
    {
        $cases = self::cases();
        return $cases[array_rand($cases)]->value;
    }
}


/**
 * ゲームの進行状態を定義するEnum
 */
enum GameStatus: int
{
    case PLAYING = 1;
    case CLEAR   = 2;
    case OVER    = 3;
}
?>
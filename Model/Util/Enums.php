<?php
/**
 * パズルのピースの色を定義するEnum
 */
enum PieceColor: string
{
    case Red = 'red';
    case Blue = 'blue';
    case Green = 'green';
    case Yellow = 'yellow';
    case Purple = 'purple';

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
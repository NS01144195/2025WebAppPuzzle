<?php
/**
 * セッションで使用するキーを一元管理するクラス
 */
class SessionKeys
{
    // ゲーム状態に関するキー
    public const BOARD = 'board';
    public const SCORE = 'score';
    public const MOVES_LEFT = 'movesLeft';
    public const GAME_STATE = 'gameState';
    public const IS_NEW_HIGHSCORE = 'isNewHighScore';

    // シーン管理に関するキー
    public const CURRENT_SCENE = 'currentScene';
    public const DIFFICULTY = 'difficulty';
}
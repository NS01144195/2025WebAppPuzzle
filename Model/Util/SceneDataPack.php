<?php
require_once __DIR__ . '/SessionKeys.php';

interface SceneDataPack
{
}

final class TitleSceneDataPack implements SceneDataPack
{
    public const SCENE = 'title';
}

final class SelectSceneDataPack implements SceneDataPack
{
    public const SCENE = 'select';

    /**
     * ハイスコア値を受け取りデータパックを初期化する。
     */
    public function __construct(private int $highScore)
    {
    }

    /**
     * 保存されているハイスコアを取得する。
     */
    public function getHighScore(): int
    {
        return $this->highScore;
    }
}

final class GameSceneDataPack implements SceneDataPack
{
    public const SCENE = 'game';

    /**
     * 選択された難易度を保持してデータパックを構築する。
     */
    public function __construct(private string $difficulty)
    {
    }

    /**
     * 現在設定されている難易度名を返す。
     */
    public function getDifficulty(): string
    {
        return $this->difficulty;
    }
}

final class ResultSceneDataPack implements SceneDataPack
{
    public const SCENE = 'result';

    /**
     * ゲーム結果とハイスコア情報をまとめて保持する。
     */
    public function __construct(
        private int $gameState,
        private int $finalScore,
        private int $movesLeft,
        private bool $isNewHighScore
    ) {
    }

    /**
     * 最終的なゲームステータス値を返す。
     */
    public function getGameState(): int
    {
        return $this->gameState;
    }

    /**
     * 記録された最終スコアを返す。
     */
    public function getFinalScore(): int
    {
        return $this->finalScore;
    }

    /**
     * 終了時点で残っていた手数を返す。
     */
    public function getMovesLeft(): int
    {
        return $this->movesLeft;
    }

    /**
     * ハイスコアを更新したかどうかを返す。
     */
    public function isNewHighScore(): bool
    {
        return $this->isNewHighScore;
    }

    /**
     * ハイスコア演出済みフラグをリセットし、再表示を防ぐ。
     */
    public function acknowledgeHighScoreMessage(): void
    {
        if ($this->isNewHighScore) {
            unset($_SESSION[SessionKeys::IS_NEW_HIGHSCORE]);
            $this->isNewHighScore = false;
        }
    }

}

final class SceneDataPackStorage
{
    /**
     * データパックを適用・保存してシーン状態を更新する。
     */
    public static function save(SceneDataPack $pack): void
    {
        self::apply($pack);
        self::store($pack);
    }

    /**
     * 既存のデータパックを更新してセッションに反映する。
     */
    public static function update(SceneDataPack $pack): void
    {
        self::apply($pack);
        self::store($pack);
    }

    /**
     * 指定シーンのデータパックを読み込み、必要ならデフォルトを生成する。
     */
    public static function load(string $scene): SceneDataPack
    {
        $stored = $_SESSION[SessionKeys::SCENE_DATA_PACK] ?? null;

        if (is_array($stored)
            && ($stored['scene'] ?? null) === $scene
            && isset($stored['class'], $stored['payload'])
            && class_exists($stored['class'])
            && is_subclass_of($stored['class'], SceneDataPack::class)
        ) {
            $pack = self::hydratePack((string)$stored['class'], (array)$stored['payload']);
            if ($pack instanceof SceneDataPack && self::getSceneName($pack) === $scene) {
                self::apply($pack);
                return $pack;
            }
        }

        $pack = self::createDefaultPack($scene);
        self::apply($pack);
        self::store($pack);

        return $pack;
    }

    /**
     * シーン名に応じたデフォルトのデータパックを生成する。
     */
    private static function createDefaultPack(string $scene): SceneDataPack
    {
        switch ($scene) {
            case 'select':
                $highScore = isset($_COOKIE['highscore']) ? (int)$_COOKIE['highscore'] : 0;
                return new SelectSceneDataPack($highScore);
            case 'game':
                $difficulty = $_SESSION[SessionKeys::DIFFICULTY] ?? 'normal';
                return new GameSceneDataPack((string)$difficulty);
            case 'result':
                return new ResultSceneDataPack(
                    (int)($_SESSION[SessionKeys::GAME_STATE] ?? 0),
                    (int)($_SESSION[SessionKeys::SCORE] ?? 0),
                    (int)($_SESSION[SessionKeys::MOVES_LEFT] ?? 0),
                    !empty($_SESSION[SessionKeys::IS_NEW_HIGHSCORE])
                );
            default:
                return new TitleSceneDataPack();
        }
    }

    /**
     * データパックをシリアライズしてセッションへ保存する。
     */
    private static function store(SceneDataPack $pack): void
    {
        $_SESSION[SessionKeys::SCENE_DATA_PACK] = [
            'scene' => self::getSceneName($pack),
            'class' => get_class($pack),
            'payload' => self::toPayload($pack),
        ];
    }

    /**
     * 保存済みのクラス情報とペイロードからデータパックを再生成する。
     * @param class-string $class
     * @return SceneDataPack|null 保存内容から再構築したデータパック
     */
    private static function hydratePack(string $class, array $payload): ?SceneDataPack
    {
        switch ($class) {
            case TitleSceneDataPack::class:
                return new TitleSceneDataPack();
            case SelectSceneDataPack::class:
                return new SelectSceneDataPack((int)($payload['highScore'] ?? 0));
            case GameSceneDataPack::class:
                $difficulty = isset($payload['difficulty']) ? (string)$payload['difficulty'] : 'normal';
                return new GameSceneDataPack($difficulty);
            case ResultSceneDataPack::class:
                return new ResultSceneDataPack(
                    (int)($payload['gameState'] ?? 0),
                    (int)($payload['finalScore'] ?? 0),
                    (int)($payload['movesLeft'] ?? 0),
                    !empty($payload['isNewHighScore'])
                );
            default:
                return null;
        }
    }

    /**
     * データパックに紐づくシーン識別子を取得する。
     */
    private static function getSceneName(SceneDataPack $pack): string
    {
        return match (true) {
            $pack instanceof TitleSceneDataPack => TitleSceneDataPack::SCENE,
            $pack instanceof SelectSceneDataPack => SelectSceneDataPack::SCENE,
            $pack instanceof GameSceneDataPack => GameSceneDataPack::SCENE,
            $pack instanceof ResultSceneDataPack => ResultSceneDataPack::SCENE,
            default => TitleSceneDataPack::SCENE,
        };
    }

    /**
     * データパックの内容をシリアライズ可能な配列へ変換する。
     * @return array<string, mixed>
     */
    private static function toPayload(SceneDataPack $pack): array
    {
        if ($pack instanceof SelectSceneDataPack) {
            return ['highScore' => $pack->getHighScore()];
        }

        if ($pack instanceof GameSceneDataPack) {
            return ['difficulty' => $pack->getDifficulty()];
        }

        if ($pack instanceof ResultSceneDataPack) {
            return [
                'gameState' => $pack->getGameState(),
                'finalScore' => $pack->getFinalScore(),
                'movesLeft' => $pack->getMovesLeft(),
                'isNewHighScore' => $pack->isNewHighScore(),
            ];
        }

        return [];
    }

    /**
     * データパック内容をセッションへ反映して整合性を保つ。
     */
    private static function apply(SceneDataPack $pack): void
    {
        if ($pack instanceof GameSceneDataPack) {
            $_SESSION[SessionKeys::DIFFICULTY] = $pack->getDifficulty();
            return;
        }

        if ($pack instanceof ResultSceneDataPack) {
            $_SESSION[SessionKeys::GAME_STATE] = $pack->getGameState();
            $_SESSION[SessionKeys::SCORE] = $pack->getFinalScore();
            $_SESSION[SessionKeys::MOVES_LEFT] = $pack->getMovesLeft();
            if ($pack->isNewHighScore()) {
                $_SESSION[SessionKeys::IS_NEW_HIGHSCORE] = true;
            } else {
                unset($_SESSION[SessionKeys::IS_NEW_HIGHSCORE]);
            }
            return;
        }
    }
}

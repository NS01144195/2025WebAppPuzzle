<?php
require_once 'SessionKeys.php';

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

    public function __construct(private int $highScore)
    {
    }

    public function getHighScore(): int
    {
        return $this->highScore;
    }
}

final class GameSceneDataPack implements SceneDataPack
{
    public const SCENE = 'game';

    public function __construct(private string $difficulty)
    {
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }
}

final class ResultSceneDataPack implements SceneDataPack
{
    public const SCENE = 'result';

    public function __construct(
        private int $gameState,
        private int $finalScore,
        private int $movesLeft,
        private bool $isNewHighScore
    ) {
    }

    public function getGameState(): int
    {
        return $this->gameState;
    }

    public function getFinalScore(): int
    {
        return $this->finalScore;
    }

    public function getMovesLeft(): int
    {
        return $this->movesLeft;
    }

    public function isNewHighScore(): bool
    {
        return $this->isNewHighScore;
    }

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
    public static function save(SceneDataPack $pack): void
    {
        self::apply($pack);
        self::store($pack);
    }

    public static function update(SceneDataPack $pack): void
    {
        self::apply($pack);
        self::store($pack);
    }

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

    private static function store(SceneDataPack $pack): void
    {
        $_SESSION[SessionKeys::SCENE_DATA_PACK] = [
            'scene' => self::getSceneName($pack),
            'class' => get_class($pack),
            'payload' => self::toPayload($pack),
        ];
    }

    /**
     * @param class-string $class
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

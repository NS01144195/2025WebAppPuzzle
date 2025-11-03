<?php
require_once 'SessionKeys.php';

interface SceneDataPack
{
    public function getScene(): string;

    /**
     * セッションへ適用する。
     */
    public function apply(): void;

    /**
     * セッション保存用のペイロードを返す。
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * セッションから復元する。
     *
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): SceneDataPack;
}

final class TitleSceneDataPack implements SceneDataPack
{
    public function getScene(): string
    {
        return 'title';
    }

    public function apply(): void
    {
        // タイトルシーンは共有データを必要としない。
    }

    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload): SceneDataPack
    {
        return new self();
    }
}

final class SelectSceneDataPack implements SceneDataPack
{
    public function __construct(private int $highScore)
    {
    }

    public function getScene(): string
    {
        return 'select';
    }

    public function apply(): void
    {
        // シーン切り替え時に特別な処理は不要。
    }

    public function toPayload(): array
    {
        return ['highScore' => $this->highScore];
    }

    public function getHighScore(): int
    {
        return $this->highScore;
    }

    public static function fromPayload(array $payload): SceneDataPack
    {
        return new self((int)($payload['highScore'] ?? 0));
    }
}

final class GameSceneDataPack implements SceneDataPack
{
    public function __construct(private string $difficulty, private bool $shouldResetState = false)
    {
    }

    public function getScene(): string
    {
        return 'game';
    }

    public function apply(): void
    {
        $_SESSION[SessionKeys::DIFFICULTY] = $this->difficulty;

        if ($this->shouldResetState) {
            unset(
                $_SESSION[SessionKeys::BOARD],
                $_SESSION[SessionKeys::SCORE],
                $_SESSION[SessionKeys::MOVES_LEFT],
                $_SESSION[SessionKeys::GAME_STATE],
                $_SESSION[SessionKeys::IS_NEW_HIGHSCORE]
            );
            $this->shouldResetState = false;
        }
    }

    public function toPayload(): array
    {
        return [
            'difficulty' => $this->difficulty,
            'shouldResetState' => $this->shouldResetState,
        ];
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public static function fromPayload(array $payload): SceneDataPack
    {
        $difficulty = isset($payload['difficulty']) ? (string)$payload['difficulty'] : 'normal';
        $shouldResetState = !empty($payload['shouldResetState']);

        return new self($difficulty, $shouldResetState);
    }
}

final class ResultSceneDataPack implements SceneDataPack
{
    public function __construct(
        private int $gameState,
        private int $finalScore,
        private int $movesLeft,
        private bool $isNewHighScore
    ) {
    }

    public function getScene(): string
    {
        return 'result';
    }

    public function apply(): void
    {
        $_SESSION[SessionKeys::GAME_STATE] = $this->gameState;
        $_SESSION[SessionKeys::SCORE] = $this->finalScore;
        $_SESSION[SessionKeys::MOVES_LEFT] = $this->movesLeft;
        $_SESSION[SessionKeys::IS_NEW_HIGHSCORE] = $this->isNewHighScore;
    }

    public function toPayload(): array
    {
        return [
            'gameState' => $this->gameState,
            'finalScore' => $this->finalScore,
            'movesLeft' => $this->movesLeft,
            'isNewHighScore' => $this->isNewHighScore,
        ];
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

    public static function fromPayload(array $payload): SceneDataPack
    {
        return new self(
            (int)($payload['gameState'] ?? 0),
            (int)($payload['finalScore'] ?? 0),
            (int)($payload['movesLeft'] ?? 0),
            !empty($payload['isNewHighScore'])
        );
    }
}

final class SceneDataPackStorage
{
    public static function save(SceneDataPack $pack): void
    {
        $pack->apply();
        self::store($pack);
    }

    public static function update(SceneDataPack $pack): void
    {
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
            $class = $stored['class'];
            /** @var SceneDataPack $pack */
            $pack = $class::fromPayload((array)$stored['payload']);
            $pack->apply();
            return $pack;
        }

        $pack = self::createDefaultPack($scene);
        $pack->apply();
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
            'scene' => $pack->getScene(),
            'class' => get_class($pack),
            'payload' => $pack->toPayload(),
        ];
    }
}

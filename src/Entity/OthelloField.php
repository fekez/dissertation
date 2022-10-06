<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;

class OthelloField {

    /**
     * @var int $position
     */
    private $position;

    /**
     * @var string $side
     */
    private $side;

    /**
     * @var float $score
     */
    private $score = 1;

    /**
     * @var string $uuid
     */
    private $uuid;

    /**
     * @var string $status
     */
    private $status;

    /**
     * @var array $keyEditors
     */
    private static $keyEditors = [1, -1, 99, -99, 100, -100, 101, -101];



    /**
     * @param int $position
     * @param string $side
     * @param string $status
     */
    public function __construct(int $position, string $status = 'fa fa-fw', string $side = '')
    {
        $this->position = $position;
        $this->side = $side;
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return string | null
     */
    public function getSide(): ?string
    {
        return $this->side;
    }

    /**
     * @param string|null $side
     */
    public function setSide(?string $side): void
    {
        $this->side = $side;
    }



    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * @param float $score
     */
    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    /**
     * @param int $score
     */
    public function addScore(int $score): void
    {
        $this->score += $score;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function generateUuid(): void
    {
        $this->uuid = Uuid::uuid4()->toString();
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public static function getKeyEditors(): array
    {
        return self::$keyEditors;
    }
}
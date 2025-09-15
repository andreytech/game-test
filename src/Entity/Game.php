<?php

namespace App\Entity;

class Game
{
    private string $id;
    private array $drawnNumbers;
    private array $playerNumbers;
    private float $stake;
    private float $totalPayout;
    private int $numbersMatched;
    private string $status; // 'waiting', 'playing', 'finished'
    private \DateTime $createdAt;
    private ?string $playerId;

    public function __construct(string $playerId = null)
    {
        $this->id = uniqid('game_', true);
        $this->drawnNumbers = [];
        $this->playerNumbers = [];
        $this->stake = 1.0;
        $this->totalPayout = 0.0;
        $this->numbersMatched = 0;
        $this->status = 'waiting';
        $this->createdAt = new \DateTime();
        $this->playerId = $playerId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDrawnNumbers(): array
    {
        return $this->drawnNumbers;
    }

    public function setDrawnNumbers(array $drawnNumbers): self
    {
        $this->drawnNumbers = $drawnNumbers;
        return $this;
    }

    public function getPlayerNumbers(): array
    {
        return $this->playerNumbers;
    }

    public function setPlayerNumbers(array $playerNumbers): self
    {
        $this->playerNumbers = $playerNumbers;
        return $this;
    }

    public function getStake(): float
    {
        return $this->stake;
    }

    public function setStake(float $stake): self
    {
        $this->stake = max(0.1, min(10.0, $stake));
        return $this;
    }

    public function getTotalPayout(): float
    {
        return $this->totalPayout;
    }

    public function setTotalPayout(float $totalPayout): self
    {
        $this->totalPayout = $totalPayout;
        return $this;
    }

    public function getNumbersMatched(): int
    {
        return $this->numbersMatched;
    }

    public function setNumbersMatched(int $numbersMatched): self
    {
        $this->numbersMatched = $numbersMatched;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getPlayerId(): ?string
    {
        return $this->playerId;
    }

    public function setPlayerId(?string $playerId): self
    {
        $this->playerId = $playerId;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'drawnNumbers' => $this->drawnNumbers,
            'playerNumbers' => $this->playerNumbers,
            'stake' => $this->stake,
            'totalPayout' => $this->totalPayout,
            'numbersMatched' => $this->numbersMatched,
            'status' => $this->status,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'playerId' => $this->playerId
        ];
    }
}

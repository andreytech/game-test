<?php

namespace App\Repository;

use App\Entity\Game;

class GameRepository
{
    private array $games = [];

    public function save(Game $game): void
    {
        $this->games[$game->getId()] = $game;
    }

    public function find(string $id): ?Game
    {
        return $this->games[$id] ?? null;
    }

    public function findByPlayerId(string $playerId): array
    {
        return array_filter($this->games, function (Game $game) use ($playerId) {
            return $game->getPlayerId() === $playerId;
        });
    }

    public function findAll(): array
    {
        return array_values($this->games);
    }

    public function delete(string $id): bool
    {
        if (isset($this->games[$id])) {
            unset($this->games[$id]);
            return true;
        }
        return false;
    }

    public function getActiveGames(): array
    {
        return array_filter($this->games, function (Game $game) {
            return in_array($game->getStatus(), ['waiting', 'playing']);
        });
    }

    public function getFinishedGames(): array
    {
        return array_filter($this->games, function (Game $game) {
            return $game->getStatus() === 'finished';
        });
    }
}

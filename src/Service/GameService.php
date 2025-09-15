<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\GameRepository;

class GameService
{
    private const ERROR_GAME_NOT_FOUND = 'Game not found';
    private const ERROR_INVALID_PLAYER_NUMBERS = 'Invalid player numbers';
    private const ERROR_PLAYER_NUMBERS_NOT_SET = 'Player numbers not set';

    private GameRepository $gameRepository;
    private RandomNumberGenerator $rng;
    private PayoutCalculator $payoutCalculator;

    public function __construct(
        GameRepository $gameRepository,
        RandomNumberGenerator $rng,
        PayoutCalculator $payoutCalculator
    ) {
        $this->gameRepository = $gameRepository;
        $this->rng = $rng;
        $this->payoutCalculator = $payoutCalculator;
    }

    public function createGame(string $playerId = null): Game
    {
        $game = new Game($playerId);
        $this->gameRepository->save($game);
        return $game;
    }

    public function setPlayerNumbers(string $gameId, array $numbers): Game
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException(self::ERROR_GAME_NOT_FOUND);
        }

        if (!$this->rng->validatePlayerNumbers($numbers)) {
            throw new \InvalidArgumentException(self::ERROR_INVALID_PLAYER_NUMBERS);
        }

        $game->setPlayerNumbers($numbers);
        $game->setStatus('ready');
        $this->gameRepository->save($game);

        return $game;
    }

    public function autoPickNumbers(string $gameId, int $count = 5): Game
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException(self::ERROR_GAME_NOT_FOUND);
        }

        $numbers = $this->rng->generatePlayerNumbers($count);
        $game->setPlayerNumbers($numbers);
        $game->setStatus('ready');
        $this->gameRepository->save($game);

        return $game;
    }

    public function setStake(string $gameId, float $stake): Game
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException(self::ERROR_GAME_NOT_FOUND);
        }

        $game->setStake($stake);
        $this->gameRepository->save($game);

        return $game;
    }

    public function drawNumbers(string $gameId): Game
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException(self::ERROR_GAME_NOT_FOUND);
        }

        if (empty($game->getPlayerNumbers())) {
            throw new \InvalidArgumentException(self::ERROR_PLAYER_NUMBERS_NOT_SET);
        }

        $drawnNumbers = $this->rng->drawNumbers();
        $game->setDrawnNumbers($drawnNumbers);
        $game->setStatus('playing');

        // Calculate matches and payout
        $matches = $this->calculateMatches($game->getPlayerNumbers(), $drawnNumbers);
        $game->setNumbersMatched($matches);

        $payout = $this->payoutCalculator->calculatePayout(
            count($game->getPlayerNumbers()),
            $matches,
            $game->getStake()
        );
        $game->setTotalPayout($payout);
        $game->setStatus('finished');

        $this->gameRepository->save($game);

        return $game;
    }

    public function getGameState(string $gameId): array
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException(self::ERROR_GAME_NOT_FOUND);
        }

        $state = $game->toArray();
        
        // Add real-time information
        $state['currentMatches'] = $this->calculateMatches(
            $game->getPlayerNumbers(),
            $game->getDrawnNumbers()
        );

        $state['currentPayout'] = $this->payoutCalculator->calculatePayout(
            count($game->getPlayerNumbers()),
            $state['currentMatches'],
            $game->getStake()
        );

        $state['payTable'] = $this->payoutCalculator->getPayTable();

        return $state;
    }

    public function replayGame(string $gameId): Game
    {
        $game = $this->gameRepository->find($gameId);
        if (!$game) {
            throw new \InvalidArgumentException(self::ERROR_GAME_NOT_FOUND);
        }

        // Reset game state but keep player numbers and stake
        $game->setDrawnNumbers([]);
        $game->setTotalPayout(0.0);
        $game->setNumbersMatched(0);
        $game->setStatus('ready');

        $this->gameRepository->save($game);

        return $game;
    }

    public function getPlayerGames(string $playerId): array
    {
        return $this->gameRepository->findByPlayerId($playerId);
    }

    public function getActiveGames(): array
    {
        return $this->gameRepository->getActiveGames();
    }

    public function getFinishedGames(): array
    {
        return $this->gameRepository->getFinishedGames();
    }

    public function validateHouseEdge(): array
    {
        $validation = [];
        
        for ($picks = 2; $picks <= 10; $picks++) {
            $houseEdge = $this->payoutCalculator->calculateHouseEdge($picks);
            $validation[$picks] = [
                'houseEdge' => round($houseEdge * 100, 2),
                'isValid' => $houseEdge >= 0.05 && $houseEdge <= 0.08
            ];
        }

        return $validation;
    }

    private function calculateMatches(array $playerNumbers, array $drawnNumbers): int
    {
        return count(array_intersect($playerNumbers, $drawnNumbers));
    }
}

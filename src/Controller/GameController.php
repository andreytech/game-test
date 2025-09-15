<?php

namespace App\Controller;

use App\Service\GameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/game', name: 'game_')]
class GameController extends AbstractController
{
    private GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createGame(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $playerId = $data['playerId'] ?? null;

            $game = $this->gameService->createGame($playerId);

            return $this->json([
                'success' => true,
                'game' => $game->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{gameId}/numbers', name: 'set_numbers', methods: ['POST'])]
    public function setPlayerNumbers(string $gameId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $numbers = $data['numbers'] ?? [];

            if (empty($numbers)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Numbers are required'
                ], 400);
            }

            $game = $this->gameService->setPlayerNumbers($gameId, $numbers);

            return $this->json([
                'success' => true,
                'game' => $game->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{gameId}/autopick', name: 'autopick', methods: ['POST'])]
    public function autoPickNumbers(string $gameId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $count = $data['count'] ?? 5;

            $game = $this->gameService->autoPickNumbers($gameId, $count);

            return $this->json([
                'success' => true,
                'game' => $game->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{gameId}/stake', name: 'set_stake', methods: ['POST'])]
    public function setStake(string $gameId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $stake = $data['stake'] ?? 1.0;

            $game = $this->gameService->setStake($gameId, $stake);

            return $this->json([
                'success' => true,
                'game' => $game->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{gameId}/draw', name: 'draw', methods: ['POST'])]
    public function drawNumbers(string $gameId): JsonResponse
    {
        try {
            $game = $this->gameService->drawNumbers($gameId);

            return $this->json([
                'success' => true,
                'game' => $game->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{gameId}/state', name: 'state', methods: ['GET'])]
    public function getGameState(string $gameId): JsonResponse
    {
        try {
            $state = $this->gameService->getGameState($gameId);

            return $this->json([
                'success' => true,
                'state' => $state
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{gameId}/replay', name: 'replay', methods: ['POST'])]
    public function replayGame(string $gameId): JsonResponse
    {
        try {
            $game = $this->gameService->replayGame($gameId);

            return $this->json([
                'success' => true,
                'game' => $game->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/player/{playerId}', name: 'player_games', methods: ['GET'])]
    public function getPlayerGames(string $playerId): JsonResponse
    {
        try {
            $games = $this->gameService->getPlayerGames($playerId);

            return $this->json([
                'success' => true,
                'games' => array_map(fn($game) => $game->toArray(), $games)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/active', name: 'active_games', methods: ['GET'])]
    public function getActiveGames(): JsonResponse
    {
        try {
            $games = $this->gameService->getActiveGames();

            return $this->json([
                'success' => true,
                'games' => array_map(fn($game) => $game->toArray(), $games)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/validation/house-edge', name: 'validate_house_edge', methods: ['GET'])]
    public function validateHouseEdge(): JsonResponse
    {
        try {
            $validation = $this->gameService->validateHouseEdge();

            return $this->json([
                'success' => true,
                'validation' => $validation
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
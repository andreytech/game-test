<?php

namespace App\Command;

use App\Service\GameService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:game:demo',
    description: 'Interactive game demo'
)]
class GameDemoCommand extends Command
{
    private GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('auto', 'a', InputOption::VALUE_NONE, 'Run in auto mode')
            ->addOption('rounds', 'r', InputOption::VALUE_OPTIONAL, 'Number of rounds', 5)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $auto = $input->getOption('auto');
        $rounds = (int) $input->getOption('rounds');

        $io->title('🎲 Game Demo - Interactive Keno Game');

        if ($auto) {
            $this->runAutoDemo($io, $rounds);
        } else {
            $this->runInteractiveDemo($io);
        }

        return Command::SUCCESS;
    }

    private function runAutoDemo(SymfonyStyle $io, int $rounds): void
    {
        $io->writeln("Running auto demo with {$rounds} rounds...\n");

        $totalStake = 0;
        $totalPayout = 0;
        $games = [];

        for ($i = 1; $i <= $rounds; $i++) {
            $io->writeln("=== Round {$i} ===");
            
            // Create game
            $game = $this->gameService->createGame("demo_player_{$i}");
            
            // Set random stake
            $stake = round(0.5 + (4.5 * mt_rand() / mt_getrandmax()), 1);
            $game = $this->gameService->setStake($game->getId(), $stake);
            
            // Auto-pick numbers
            $numberCount = mt_rand(2, 10);
            $game = $this->gameService->autoPickNumbers($game->getId(), $numberCount);
            
            // Draw numbers
            $game = $this->gameService->drawNumbers($game->getId());
            
            $totalStake += $game->getStake();
            $totalPayout += $game->getTotalPayout();
            
            $games[] = $game;
            
            $io->writeln("Stake: {$game->getStake()}");
            $io->writeln("Numbers picked: " . implode(', ', $game->getPlayerNumbers()));
            $io->writeln("Drawn numbers: " . implode(', ', $game->getDrawnNumbers()));
            $io->writeln("Matches: {$game->getNumbersMatched()}");
            $io->writeln("Payout: {$game->getTotalPayout()}");
            $io->writeln("Profit: " . ($game->getTotalPayout() - $game->getStake()));
            $io->writeln("");
        }

        // Summary
        $io->section('Demo Summary');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Rounds', $rounds],
                ['Total Stake', number_format($totalStake, 2)],
                ['Total Payout', number_format($totalPayout, 2)],
                ['House Profit', number_format($totalStake - $totalPayout, 2)],
                ['House Edge', number_format((($totalStake - $totalPayout) / $totalStake) * 100, 2) . '%']
            ]
        );

        // Best and worst games
        $bestGame = array_reduce($games, function($best, $game) {
            $profit = $game->getTotalPayout() - $game->getStake();
            $bestProfit = $best->getTotalPayout() - $best->getStake();
            return $profit > $bestProfit ? $game : $best;
        }, $games[0]);

        $worstGame = array_reduce($games, function($worst, $game) {
            $profit = $game->getTotalPayout() - $game->getStake();
            $worstProfit = $worst->getTotalPayout() - $worst->getStake();
            return $profit < $worstProfit ? $game : $worst;
        }, $games[0]);

        $io->section('Best and Worst Games');
        $io->table(
            ['Type', 'Stake', 'Picked', 'Matched', 'Payout', 'Profit'],
            [
                ['Best', $bestGame->getStake(), count($bestGame->getPlayerNumbers()), 
                 $bestGame->getNumbersMatched(), $bestGame->getTotalPayout(), 
                 '+' . ($bestGame->getTotalPayout() - $bestGame->getStake())],
                ['Worst', $worstGame->getStake(), count($worstGame->getPlayerNumbers()), 
                 $worstGame->getNumbersMatched(), $worstGame->getTotalPayout(), 
                 $worstGame->getTotalPayout() - $worstGame->getStake()]
            ]
        );
    }

    private function runInteractiveDemo(SymfonyStyle $io): void
    {
        $io->writeln("Welcome to the interactive game demo!");
        $io->writeln("You can create games, set numbers, stakes, and draw numbers.\n");

        while (true) {
            $choice = $io->choice(
                'What would you like to do?',
                [
                    'Create new game',
                    'Set player numbers',
                    'Auto-pick numbers',
                    'Set stake',
                    'Draw numbers',
                    'Get game state',
                    'Replay game',
                    'Show house edge validation',
                    'Exit'
                ],
                'Create new game'
            );

            switch ($choice) {
                case 'Create new game':
                    $this->createGame($io);
                    break;
                case 'Set player numbers':
                    $this->setPlayerNumbers($io);
                    break;
                case 'Auto-pick numbers':
                    $this->autoPickNumbers($io);
                    break;
                case 'Set stake':
                    $this->setStake($io);
                    break;
                case 'Draw numbers':
                    $this->drawNumbers($io);
                    break;
                case 'Get game state':
                    $this->getGameState($io);
                    break;
                case 'Replay game':
                    $this->replayGame($io);
                    break;
                case 'Show house edge validation':
                    $this->showHouseEdgeValidation($io);
                    break;
                case 'Exit':
                    $io->writeln('Goodbye!');
                    return;
            }
        }
    }

    private function createGame(SymfonyStyle $io): void
    {
        $game = $this->gameService->createGame('interactive_player');
        $io->writeln("Created game: {$game->getId()}");
        $this->setCurrentGame($game->getId());
    }

    private function setPlayerNumbers(SymfonyStyle $io): void
    {
        $gameId = $this->getCurrentGame();
        if (!$gameId) {
            $io->error('No current game. Please create a game first.');
            return;
        }

        $numbers = $io->ask('Enter numbers (comma-separated, 2-10 numbers, range 1-80)', '1,5,10,15,20');
        $numbers = array_map('intval', explode(',', $numbers));

        try {
            $game = $this->gameService->setPlayerNumbers($gameId, $numbers);
            $io->writeln("Set numbers: " . implode(', ', $game->getPlayerNumbers()));
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function autoPickNumbers(SymfonyStyle $io): void
    {
        $gameId = $this->getCurrentGame();
        if (!$gameId) {
            $io->error('No current game. Please create a game first.');
            return;
        }

        $count = $io->ask('How many numbers to pick?', '5');
        $count = (int) $count;

        try {
            $game = $this->gameService->autoPickNumbers($gameId, $count);
            $io->writeln("Auto-picked numbers: " . implode(', ', $game->getPlayerNumbers()));
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function setStake(SymfonyStyle $io): void
    {
        $gameId = $this->getCurrentGame();
        if (!$gameId) {
            $io->error('No current game. Please create a game first.');
            return;
        }

        $stake = $io->ask('Enter stake amount (0.1-10.0)', '1.0');
        $stake = (float) $stake;

        try {
            $game = $this->gameService->setStake($gameId, $stake);
            $io->writeln("Set stake: {$game->getStake()}");
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function drawNumbers(SymfonyStyle $io): void
    {
        $gameId = $this->getCurrentGame();
        if (!$gameId) {
            $io->error('No current game. Please create a game first.');
            return;
        }

        try {
            $game = $this->gameService->drawNumbers($gameId);
            $io->writeln("Drawn numbers: " . implode(', ', $game->getDrawnNumbers()));
            $io->writeln("Your numbers: " . implode(', ', $game->getPlayerNumbers()));
            $io->writeln("Matches: {$game->getNumbersMatched()}");
            $io->writeln("Payout: {$game->getTotalPayout()}");
            $io->writeln("Profit: " . ($game->getTotalPayout() - $game->getStake()));
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function getGameState(SymfonyStyle $io): void
    {
        $gameId = $this->getCurrentGame();
        if (!$gameId) {
            $io->error('No current game. Please create a game first.');
            return;
        }

        try {
            $state = $this->gameService->getGameState($gameId);
            $io->writeln("Game State:");
            $io->writeln("  ID: {$state['id']}");
            $io->writeln("  Status: {$state['status']}");
            $io->writeln("  Stake: {$state['stake']}");
            $io->writeln("  Player Numbers: " . implode(', ', $state['playerNumbers']));
            $io->writeln("  Drawn Numbers: " . implode(', ', $state['drawnNumbers']));
            $io->writeln("  Matches: {$state['numbersMatched']}");
            $io->writeln("  Payout: {$state['totalPayout']}");
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function replayGame(SymfonyStyle $io): void
    {
        $gameId = $this->getCurrentGame();
        if (!$gameId) {
            $io->error('No current game. Please create a game first.');
            return;
        }

        try {
            $game = $this->gameService->replayGame($gameId);
            $io->writeln("Game replayed. Status: {$game->getStatus()}");
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function showHouseEdgeValidation(SymfonyStyle $io): void
    {
        try {
            $validation = $this->gameService->validateHouseEdge();
            $io->table(
                ['Numbers Picked', 'House Edge %', 'Valid'],
                array_map(function($picks, $data) {
                    return [
                        $picks,
                        $data['houseEdge'],
                        $data['isValid'] ? '✓' : '✗'
                    ];
                }, array_keys($validation), array_values($validation))
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    private function setCurrentGame(string $gameId): void
    {
        $this->currentGameId = $gameId;
    }

    private function getCurrentGame(): ?string
    {
        return $this->currentGameId ?? null;
    }

    private ?string $currentGameId = null;
}

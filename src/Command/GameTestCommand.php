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
    name: 'app:game:test',
    description: 'Test the game API endpoints'
)]
class GameTestCommand extends Command
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
            ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Number of test iterations', 5)
            ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $iterations = (int) $input->getOption('iterations');
        $verbose = $input->getOption('verbose');

        $io->title('Game API Test Suite');

        $testResults = [];
        
        for ($i = 1; $i <= $iterations; $i++) {
            $io->writeln("Running test iteration {$i}...");
            
            try {
                $result = $this->runSingleTest($io, $verbose);
                $testResults[] = $result;
                
                if ($verbose) {
                    $io->writeln("✓ Test {$i} completed successfully");
                }
            } catch (\Exception $e) {
                $io->error("✗ Test {$i} failed: " . $e->getMessage());
                $testResults[] = ['error' => $e->getMessage()];
            }
        }

        $this->displayTestResults($io, $testResults);

        return Command::SUCCESS;
    }

    private function runSingleTest(SymfonyStyle $io, bool $verbose): array
    {
        $testData = [];

        // Test 1: Create game
        $game = $this->gameService->createGame('test_player');
        $testData['game_id'] = $game->getId();
        $testData['created'] = true;

        if ($verbose) {
            $io->writeln("  Created game: {$game->getId()}");
        }

        // Test 2: Set player numbers manually
        $playerNumbers = [1, 5, 10, 15, 20, 25, 30, 35, 40, 45];
        $game = $this->gameService->setPlayerNumbers($game->getId(), $playerNumbers);
        $testData['numbers_set'] = $game->getPlayerNumbers();

        if ($verbose) {
            $io->writeln("  Set numbers: " . implode(', ', $game->getPlayerNumbers()));
        }

        // Test 3: Set stake
        $stake = 2.5;
        $game = $this->gameService->setStake($game->getId(), $stake);
        $testData['stake'] = $game->getStake();

        if ($verbose) {
            $io->writeln("  Set stake: {$game->getStake()}");
        }

        // Test 4: Draw numbers
        $game = $this->gameService->drawNumbers($game->getId());
        $testData['drawn_numbers'] = $game->getDrawnNumbers();
        $testData['matches'] = $game->getNumbersMatched();
        $testData['payout'] = $game->getTotalPayout();
        $testData['profit'] = $game->getTotalPayout() - $game->getStake();

        if ($verbose) {
            $io->writeln("  Drawn numbers: " . implode(', ', $game->getDrawnNumbers()));
            $io->writeln("  Matches: {$game->getNumbersMatched()}");
            $io->writeln("  Payout: {$game->getTotalPayout()}");
            $io->writeln("  Profit: {$testData['profit']}");
        }

        // Test 5: Get game state
        $state = $this->gameService->getGameState($game->getId());
        $testData['state_retrieved'] = !empty($state);

        if ($verbose) {
            $io->writeln("  Game state retrieved: " . ($testData['state_retrieved'] ? 'Yes' : 'No'));
        }

        // Test 6: Auto-pick test
        $game2 = $this->gameService->createGame('test_player_2');
        $game2 = $this->gameService->autoPickNumbers($game2->getId(), 7);
        $testData['autopick_numbers'] = $game2->getPlayerNumbers();

        if ($verbose) {
            $io->writeln("  Auto-pick numbers: " . implode(', ', $game2->getPlayerNumbers()));
        }

        // Test 7: Replay test
        $game = $this->gameService->replayGame($game->getId());
        $testData['replay_successful'] = $game->getStatus() === 'ready' && empty($game->getDrawnNumbers());

        if ($verbose) {
            $io->writeln("  Replay successful: " . ($testData['replay_successful'] ? 'Yes' : 'No'));
        }

        // Test 8: House edge validation
        $validation = $this->gameService->validateHouseEdge();
        $testData['house_edge_valid'] = array_reduce($validation, function($valid, $data) {
            return $valid && $data['isValid'];
        }, true);

        if ($verbose) {
            $io->writeln("  House edge valid: " . ($testData['house_edge_valid'] ? 'Yes' : 'No'));
        }

        return $testData;
    }

    private function displayTestResults(SymfonyStyle $io, array $testResults): void
    {
        $io->section('Test Results Summary');

        $successfulTests = array_filter($testResults, function($result) {
            return !isset($result['error']);
        });

        $io->writeln("Total tests: " . count($testResults));
        $io->writeln("Successful: " . count($successfulTests));
        $io->writeln("Failed: " . (count($testResults) - count($successfulTests)));

        if (!empty($successfulTests)) {
            $io->section('Performance Metrics');
            
            $totalStake = array_sum(array_column($successfulTests, 'stake'));
            $totalPayout = array_sum(array_column($successfulTests, 'payout'));
            $totalProfit = array_sum(array_column($successfulTests, 'profit'));
            $averageMatches = array_sum(array_column($successfulTests, 'matches')) / count($successfulTests);
            $averagePayout = array_sum(array_column($successfulTests, 'payout')) / count($successfulTests);

            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Stake', number_format($totalStake, 2)],
                    ['Total Payout', number_format($totalPayout, 2)],
                    ['Total Profit', number_format($totalProfit, 2)],
                    ['House Edge', number_format((($totalStake - $totalPayout) / $totalStake) * 100, 2) . '%'],
                    ['Average Matches', number_format($averageMatches, 2)],
                    ['Average Payout', number_format($averagePayout, 2)]
                ]
            );

            // Show best and worst results
            $bestTest = array_reduce($successfulTests, function($best, $test) {
                return $test['profit'] > $best['profit'] ? $test : $best;
            }, $successfulTests[0]);

            $worstTest = array_reduce($successfulTests, function($worst, $test) {
                return $test['profit'] < $worst['profit'] ? $worst : $test;
            }, $successfulTests[0]);

            $io->section('Best and Worst Results');
            $io->table(
                ['Type', 'Stake', 'Matches', 'Payout', 'Profit'],
                [
                    ['Best', $bestTest['stake'], $bestTest['matches'], $bestTest['payout'], '+' . $bestTest['profit']],
                    ['Worst', $worstTest['stake'], $worstTest['matches'], $worstTest['payout'], $worstTest['profit']]
                ]
            );
        }

        // Show errors if any
        $failedTests = array_filter($testResults, function($result) {
            return isset($result['error']);
        });

        if (!empty($failedTests)) {
            $io->section('Failed Tests');
            foreach ($failedTests as $index => $test) {
                $io->writeln("Test " . ($index + 1) . ": " . $test['error']);
            }
        }
    }
}

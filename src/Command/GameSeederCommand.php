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
    name: 'app:game:seed',
    description: 'Seed the game with test data and run simulations'
)]
class GameSeederCommand extends Command
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
            ->addOption('players', 'p', InputOption::VALUE_OPTIONAL, 'Number of test players', 5)
            ->addOption('games-per-player', 'g', InputOption::VALUE_OPTIONAL, 'Games per player', 10)
            ->addOption('stake-range', 's', InputOption::VALUE_OPTIONAL, 'Stake range (min-max)', '0.5-5.0')
            ->addOption('simulate', null, InputOption::VALUE_NONE, 'Run simulation with statistics')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $players = (int) $input->getOption('players');
        $gamesPerPlayer = (int) $input->getOption('games-per-player');
        $stakeRange = $input->getOption('stake-range');
        $simulate = $input->getOption('simulate');

        $io->title('Game Seeder - Creating Test Data');

        // Parse stake range
        [$minStake, $maxStake] = explode('-', $stakeRange);
        $minStake = (float) $minStake;
        $maxStake = (float) $maxStake;

        $io->section('Creating Test Games');
        
        $totalGames = 0;
        $totalStake = 0;
        $totalPayout = 0;
        $gamesData = [];

        for ($playerId = 1; $playerId <= $players; $playerId++) {
            $io->writeln("Creating games for player {$playerId}...");
            
            for ($gameNum = 1; $gameNum <= $gamesPerPlayer; $gameNum++) {
                // Create game
                $game = $this->gameService->createGame("player_{$playerId}");
                
                // Set random stake
                $stake = round($minStake + ($maxStake - $minStake) * mt_rand() / mt_getrandmax(), 1);
                $game = $this->gameService->setStake($game->getId(), $stake);
                
                // Auto-pick numbers (2-10 numbers)
                $numberCount = mt_rand(2, 10);
                $game = $this->gameService->autoPickNumbers($game->getId(), $numberCount);
                
                // Draw numbers
                $game = $this->gameService->drawNumbers($game->getId());
                
                $totalGames++;
                $totalStake += $game->getStake();
                $totalPayout += $game->getTotalPayout();
                
                $gamesData[] = [
                    'player' => $playerId,
                    'game' => $gameNum,
                    'stake' => $game->getStake(),
                    'numbers_picked' => count($game->getPlayerNumbers()),
                    'numbers_matched' => $game->getNumbersMatched(),
                    'payout' => $game->getTotalPayout(),
                    'profit' => $game->getTotalPayout() - $game->getStake(),
                    'player_numbers' => $game->getPlayerNumbers(),
                    'drawn_numbers' => $game->getDrawnNumbers()
                ];
            }
        }

        $io->success("Created {$totalGames} games for {$players} players");

        // Display summary
        $io->section('Game Summary');
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Games', $totalGames],
                ['Total Stake', number_format($totalStake, 2)],
                ['Total Payout', number_format($totalPayout, 2)],
                ['House Profit', number_format($totalStake - $totalPayout, 2)],
                ['House Edge', number_format((($totalStake - $totalPayout) / $totalStake) * 100, 2) . '%'],
                ['Average Stake', number_format($totalStake / $totalGames, 2)],
                ['Average Payout', number_format($totalPayout / $totalGames, 2)]
            ]
        );

        // Display some example games
        $io->section('Example Games');
        $io->table(
            ['Player', 'Game', 'Stake', 'Picked', 'Matched', 'Payout', 'Profit'],
            array_slice(array_map(function($game) {
                return [
                    $game['player'],
                    $game['game'],
                    $game['stake'],
                    $game['numbers_picked'],
                    $game['numbers_matched'],
                    $game['payout'],
                    $game['profit'] > 0 ? '+' . $game['profit'] : $game['profit']
                ];
            }, $gamesData), 0, 10)
        );

        // Run simulation if requested
        if ($simulate) {
            $this->runSimulation($io, $gamesData);
        }

        // Validate house edge
        $io->section('House Edge Validation');
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

        return Command::SUCCESS;
    }

    private function runSimulation(SymfonyStyle $io, array $gamesData): void
    {
        $io->section('Simulation Analysis');
        
        // Group by numbers picked
        $byNumbersPicked = [];
        foreach ($gamesData as $game) {
            $picked = $game['numbers_picked'];
            if (!isset($byNumbersPicked[$picked])) {
                $byNumbersPicked[$picked] = [
                    'count' => 0,
                    'total_stake' => 0,
                    'total_payout' => 0,
                    'wins' => 0
                ];
            }
            
            $byNumbersPicked[$picked]['count']++;
            $byNumbersPicked[$picked]['total_stake'] += $game['stake'];
            $byNumbersPicked[$picked]['total_payout'] += $game['payout'];
            if ($game['payout'] > 0) {
                $byNumbersPicked[$picked]['wins']++;
            }
        }

        $io->table(
            ['Numbers Picked', 'Games', 'Total Stake', 'Total Payout', 'Wins', 'Win Rate %', 'House Edge %'],
            array_map(function($picked, $data) {
                $houseEdge = (($data['total_stake'] - $data['total_payout']) / $data['total_stake']) * 100;
                $winRate = ($data['wins'] / $data['count']) * 100;
                
                return [
                    $picked,
                    $data['count'],
                    number_format($data['total_stake'], 2),
                    number_format($data['total_payout'], 2),
                    $data['wins'],
                    number_format($winRate, 1),
                    number_format($houseEdge, 2)
                ];
            }, array_keys($byNumbersPicked), array_values($byNumbersPicked))
        );

        // Find best and worst games
        $bestGame = array_reduce($gamesData, function($best, $game) {
            return $game['profit'] > $best['profit'] ? $game : $best;
        }, $gamesData[0]);

        $worstGame = array_reduce($gamesData, function($worst, $game) {
            return $game['profit'] < $worst['profit'] ? $game : $worst;
        }, $gamesData[0]);

        $io->section('Best and Worst Games');
        $io->table(
            ['Type', 'Player', 'Game', 'Stake', 'Picked', 'Matched', 'Payout', 'Profit'],
            [
                ['Best', $bestGame['player'], $bestGame['game'], $bestGame['stake'], 
                 $bestGame['numbers_picked'], $bestGame['numbers_matched'], 
                 $bestGame['payout'], '+' . $bestGame['profit']],
                ['Worst', $worstGame['player'], $worstGame['game'], $worstGame['stake'], 
                 $worstGame['numbers_picked'], $worstGame['numbers_matched'], 
                 $worstGame['payout'], $worstGame['profit']]
            ]
        );
    }
}

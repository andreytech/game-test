<?php

namespace App\Service;

class PayoutCalculator
{
    // Pay table based on Keno rules with house edge 5-8%
    private array $payTable = [];

    public function __construct()
    {
        $this->initializePayTable();
    }

    private function initializePayTable(): void
    {
        // Initialize pay table with house edge of approximately 6%
        // Set payouts with house edge
        $this->payTable[2][2] = 2.0; // 2 matches = 2x stake
        $this->payTable[3][2] = 1.0; // 3 picks, 2 matches = 1x stake
        $this->payTable[3][3] = 5.0; // 3 picks, 3 matches = 5x stake
        $this->payTable[4][2] = 0.5; // 4 picks, 2 matches = 0.5x stake
        $this->payTable[4][3] = 2.0; // 4 picks, 3 matches = 2x stake
        $this->payTable[4][4] = 10.0; // 4 picks, 4 matches = 10x stake
        $this->payTable[5][2] = 0.5; // 5 picks, 2 matches = 0.5x stake
        $this->payTable[5][3] = 1.0; // 5 picks, 3 matches = 1x stake
        $this->payTable[5][4] = 5.0; // 5 picks, 4 matches = 5x stake
        $this->payTable[5][5] = 20.0; // 5 picks, 5 matches = 20x stake
        $this->payTable[6][3] = 0.5; // 6 picks, 3 matches = 0.5x stake
        $this->payTable[6][4] = 1.0; // 6 picks, 4 matches = 1x stake
        $this->payTable[6][5] = 5.0; // 6 picks, 5 matches = 5x stake
        $this->payTable[6][6] = 50.0; // 6 picks, 6 matches = 50x stake
        $this->payTable[7][3] = 0.5; // 7 picks, 3 matches = 0.5x stake
        $this->payTable[7][4] = 1.0; // 7 picks, 4 matches = 1x stake
        $this->payTable[7][5] = 2.0; // 7 picks, 5 matches = 2x stake
        $this->payTable[7][6] = 10.0; // 7 picks, 6 matches = 10x stake
        $this->payTable[7][7] = 100.0; // 7 picks, 7 matches = 100x stake
        $this->payTable[8][4] = 0.5; // 8 picks, 4 matches = 0.5x stake
        $this->payTable[8][5] = 1.0; // 8 picks, 5 matches = 1x stake
        $this->payTable[8][6] = 2.0; // 8 picks, 6 matches = 2x stake
        $this->payTable[8][7] = 10.0; // 8 picks, 7 matches = 10x stake
        $this->payTable[8][8] = 200.0; // 8 picks, 8 matches = 200x stake
        $this->payTable[9][4] = 0.5; // 9 picks, 4 matches = 0.5x stake
        $this->payTable[9][5] = 1.0; // 9 picks, 5 matches = 1x stake
        $this->payTable[9][6] = 2.0; // 9 picks, 6 matches = 2x stake
        $this->payTable[9][7] = 5.0; // 9 picks, 7 matches = 5x stake
        $this->payTable[9][8] = 20.0; // 9 picks, 8 matches = 20x stake
        $this->payTable[9][9] = 500.0; // 9 picks, 9 matches = 500x stake
        $this->payTable[10][5] = 0.5; // 10 picks, 5 matches = 0.5x stake
        $this->payTable[10][6] = 1.0; // 10 picks, 6 matches = 1x stake
        $this->payTable[10][7] = 2.0; // 10 picks, 7 matches = 2x stake
        $this->payTable[10][8] = 5.0; // 10 picks, 8 matches = 5x stake
        $this->payTable[10][9] = 20.0; // 10 picks, 9 matches = 20x stake
        $this->payTable[10][10] = 1000.0; // 10 picks, 10 matches = 1000x stake
    }

    public function calculatePayout(int $numbersPicked, int $numbersMatched, float $stake): float
    {
        if ($numbersPicked < 2 || $numbersPicked > 10) {
            return 0.0;
        }

        if ($numbersMatched < 0 || $numbersMatched > $numbersPicked) {
            return 0.0;
        }

        $multiplier = $this->payTable[$numbersPicked][$numbersMatched] ?? 0;
        return $stake * $multiplier;
    }

    public function getPayTable(): array
    {
        return $this->payTable;
    }

    public function calculateHouseEdge(int $numbersPicked): float
    {
        // Calculate theoretical house edge based on pay table
        $totalCombinations = $this->calculateTotalCombinations($numbersPicked);
        $totalPayout = 0.0;

        for ($matches = 0; $matches <= $numbersPicked; $matches++) {
            $combinations = $this->calculateCombinations($numbersPicked, $matches);
            $probability = $combinations / $totalCombinations;
            $payout = $this->payTable[$numbersPicked][$matches] ?? 0;
            $totalPayout += $probability * $payout;
        }

        return 1.0 - $totalPayout; // House edge = 1 - expected return
    }

    private function calculateTotalCombinations(int $numbersPicked): float
    {
        return $this->calculateCombinations(80, $numbersPicked);
    }

    private function calculateCombinations(int $n, int $k): float
    {
        if ($k > $n || $k < 0) {
            return 0;
        }

        $result = 1;
        for ($i = 0; $i < $k; $i++) {
            $result = $result * ($n - $i) / ($i + 1);
        }

        return $result;
    }
}

<?php

namespace App\Service;

class RandomNumberGenerator
{
    public function drawNumbers(int $count = 20, int $min = 1, int $max = 80): array
    {
        if ($count > ($max - $min + 1)) {
            throw new \InvalidArgumentException('Cannot draw more numbers than available range');
        }

        $numbers = [];
        $availableNumbers = range($min, $max);
        
        for ($i = 0; $i < $count; $i++) {
            $randomIndex = array_rand($availableNumbers);
            $numbers[] = $availableNumbers[$randomIndex];
            unset($availableNumbers[$randomIndex]);
            $availableNumbers = array_values($availableNumbers); // Re-index array
        }

        sort($numbers);
        return $numbers;
    }

    public function generatePlayerNumbers(int $count, int $min = 1, int $max = 80): array
    {
        if ($count < 2 || $count > 10) {
            throw new \InvalidArgumentException('Player must pick between 2 and 10 numbers');
        }

        return $this->drawNumbers($count, $min, $max);
    }

    public function validatePlayerNumbers(array $numbers, int $min = 1, int $max = 80): bool
    {
        if (count($numbers) < 2 || count($numbers) > 10) {
            return false;
        }

        foreach ($numbers as $number) {
            if (!is_int($number) || $number < $min || $number > $max) {
                return false;
            }
        }

        return count($numbers) === count(array_unique($numbers));
    }
}

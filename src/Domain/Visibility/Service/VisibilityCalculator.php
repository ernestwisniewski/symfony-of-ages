<?php

namespace App\Domain\Visibility\Service;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\ValueObject\UnitType;

final readonly class VisibilityCalculator
{
    /**
     * Oblicza zasięg widoczności dla jednostki
     */
    public function calculateUnitVisibility(Position $position, UnitType $unitType): array
    {
        $radius = $this->getUnitVisibilityRadius($unitType);
        return $this->calculateHexesInRadius($position, $radius);
    }

    /**
     * Oblicza zasięg widoczności dla miasta
     */
    public function calculateCityVisibility(Position $position, int $cityLevel = 1): array
    {
        $radius = $this->getCityVisibilityRadius($cityLevel);
        return $this->calculateHexesInRadius($position, $radius);
    }

    /**
     * Oblicza wszystkie heksy w zadanym promieniu (algorytm heksagonalny)
     */
    private function calculateHexesInRadius(Position $center, int $radius): array
    {
        $hexes = [];
        
        for ($dx = -$radius; $dx <= $radius; $dx++) {
            $start = max(-$radius, -$radius - $dx);
            $end = min($radius, $radius - $dx);
            
            for ($dy = $start; $dy <= $end; $dy++) {
                $x = $center->x + $dx;
                $y = $center->y + $dy;
                
                if ($x >= 0 && $y >= 0) {
                    $hexes[] = new Position($x, $y);
                }
            }
        }
        
        return $hexes;
    }

    /**
     * Zwraca promień widoczności dla jednostki
     */
    private function getUnitVisibilityRadius(UnitType $unitType): int
    {
        return match ($unitType) {
            UnitType::SCOUT => 3,
            UnitType::CAVALRY => 2,
            UnitType::WARRIOR, UnitType::ARCHER, UnitType::SIEGE_ENGINE => 1,
            UnitType::SETTLER => 1,
        };
    }

    /**
     * Zwraca promień widoczności dla miasta
     */
    private function getCityVisibilityRadius(int $cityLevel): int
    {
        return match ($cityLevel) {
            1 => 2,
            2 => 3,
            3 => 4,
            default => 2,
        };
    }

    /**
     * Sprawdza czy dwa heksy są w zasięgu widoczności
     */
    public function isInVisibilityRange(Position $from, Position $to, int $radius): bool
    {
        $distance = $this->calculateHexDistance($from, $to);
        return $distance <= $radius;
    }

    /**
     * Oblicza odległość między dwoma heksami (algorytm heksagonalny)
     */
    private function calculateHexDistance(Position $from, Position $to): int
    {
        $dx = $to->x - $from->x;
        $dy = $to->y - $from->y;
        
        // Konwersja z offset coordinates na cube coordinates
        $q1 = $from->x - ($from->y - ($from->y & 1)) / 2;
        $r1 = $from->y;
        $q2 = $to->x - ($to->y - ($to->y & 1)) / 2;
        $r2 = $to->y;
        
        $s1 = -$q1 - $r1;
        $s2 = -$q2 - $r2;
        
        return (abs($q1 - $q2) + abs($r1 - $r2) + abs($s1 - $s2)) / 2;
    }
} 
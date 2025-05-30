<?php

namespace App\Application\Player\Service;


use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;

/**
 * Builder pattern for modern player creation
 */
class PlayerCreationBuilder
{
    private string $name = 'Player';
    private ?Position $position = null;
    private int $maxMovementPoints = PlayerCreationService::DEFAULT_MOVEMENT_POINTS;
    private array $mapData = [];
    private int $mapRows = 100;
    private int $mapCols = 100;

    public function __construct(
        private readonly PlayerCreationService $service
    )
    {
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function position(int $row, int $col): self
    {
        $this->position = new Position($row, $col);
        return $this;
    }

    public function movementPoints(int $points): self
    {
        $this->maxMovementPoints = $points;
        return $this;
    }

    public function mapData(array $mapData, int $rows = 100, int $cols = 100): self
    {
        $this->mapData = $mapData;
        $this->mapRows = $rows;
        $this->mapCols = $cols;
        return $this;
    }

    public function build(): Player
    {
        if ($this->position !== null) {
            return $this->service->createPlayerWithPosition(
                $this->name,
                $this->position->row,
                $this->position->col,
                $this->maxMovementPoints
            );
        }

        if (!empty($this->mapData)) {
            return $this->service->createPlayer(
                $this->name,
                $this->mapRows,
                $this->mapCols,
                $this->mapData,
                $this->maxMovementPoints
            );
        }

        return $this->service->createTestPlayer($this->name);
    }
}

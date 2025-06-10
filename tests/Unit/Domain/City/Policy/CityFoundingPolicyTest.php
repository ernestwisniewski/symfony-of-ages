<?php

namespace App\Tests\Unit\Domain\City\Policy;

use App\Domain\City\Exception\InvalidTerrainException;
use App\Domain\City\Exception\PositionOccupiedException;
use App\Domain\City\Policy\CityFoundingPolicy;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Shared\ValueObject\Position;
use PHPUnit\Framework\TestCase;

final class CityFoundingPolicyTest extends TestCase
{
    private CityFoundingPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new CityFoundingPolicy();
    }

    public function testCanFoundCityOnValidTerrain(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::PLAINS;
        $existingPositions = [];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertTrue($result);
    }

    public function testCanFoundCityOnForest(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::FOREST;
        $existingPositions = [];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertTrue($result);
    }

    public function testCanFoundCityOnDesert(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::DESERT;
        $existingPositions = [];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertTrue($result);
    }

    public function testCannotFoundCityOnWater(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::WATER;
        $existingPositions = [];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertFalse($result);
    }

    public function testCannotFoundCityOnMountain(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::MOUNTAIN;
        $existingPositions = [];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertFalse($result);
    }

    public function testCannotFoundCityOnSwamp(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::SWAMP;
        $existingPositions = [];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertFalse($result);
    }

    public function testCannotFoundCityOnOccupiedPosition(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::PLAINS;
        $existingPositions = [new Position(10, 10)];

        $result = $this->policy->canFoundCity($position, $terrain, $existingPositions);

        $this->assertFalse($result);
    }

    public function testValidateCityFoundingPassesWithValidConditions(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::PLAINS;
        $existingPositions = [];

        // Should not throw any exception
        $this->policy->validateCityFounding($position, $terrain, $existingPositions);

        $this->assertTrue(true); // If we reach here, test passed
    }

    public function testValidateCityFoundingThrowsExceptionForInvalidTerrain(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::WATER;
        $existingPositions = [];

        $this->expectException(InvalidTerrainException::class);
        $this->expectExceptionMessage('Cannot found city on water terrain at position (10, 10).');

        $this->policy->validateCityFounding($position, $terrain, $existingPositions);
    }

    public function testValidateCityFoundingThrowsExceptionForOccupiedPosition(): void
    {
        $position = new Position(10, 10);
        $terrain = TerrainType::PLAINS;
        $existingPositions = [new Position(10, 10)];

        $this->expectException(PositionOccupiedException::class);
        $this->expectExceptionMessage('Position (10, 10) is already occupied.');

        $this->policy->validateCityFounding($position, $terrain, $existingPositions);
    }
}

<?php

namespace App\Tests\Unit\Domain\Map\Policy;

use App\Domain\Map\Exception\InvalidMapDimensionsException;
use App\Domain\Map\Policy\MapGenerationPolicy;
use PHPUnit\Framework\TestCase;

final class MapGenerationPolicyTest extends TestCase
{
    private MapGenerationPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new MapGenerationPolicy();
    }

    public function testCanGenerateMapWithValidDimensions(): void
    {
        $result = $this->policy->canGenerateMap(10, 10);

        $this->assertTrue($result);
    }

    public function testCanGenerateMapWithMinimumDimensions(): void
    {
        $result = $this->policy->canGenerateMap(5, 5);

        $this->assertTrue($result);
    }

    public function testCanGenerateMapWithMaximumDimensions(): void
    {
        $result = $this->policy->canGenerateMap(50, 50);

        $this->assertTrue($result);
    }

    public function testCannotGenerateMapWithDimensionsBelowMinimum(): void
    {
        $result = $this->policy->canGenerateMap(4, 10);

        $this->assertFalse($result);
    }

    public function testCannotGenerateMapWithDimensionsAboveMaximum(): void
    {
        $result = $this->policy->canGenerateMap(51, 10);

        $this->assertFalse($result);
    }

    public function testValidateMapGenerationPassesWithValidDimensions(): void
    {
        // Should not throw any exception
        $this->policy->validateMapGeneration(15, 20);

        $this->assertTrue(true); // If we reach here, test passed
    }

    public function testValidateMapGenerationThrowsExceptionWithInvalidWidth(): void
    {
        $this->expectException(InvalidMapDimensionsException::class);
        $this->expectExceptionMessage('Invalid map dimensions: 3x10. Both width and height must be greater than 0.');

        $this->policy->validateMapGeneration(3, 10);
    }

    public function testValidateMapGenerationThrowsExceptionWithInvalidHeight(): void
    {
        $this->expectException(InvalidMapDimensionsException::class);
        $this->expectExceptionMessage('Invalid map dimensions: 10x55. Both width and height must be greater than 0.');

        $this->policy->validateMapGeneration(10, 55);
    }

    public function testGetRecommendedDimensionsForTwoPlayers(): void
    {
        $result = $this->policy->getRecommendedDimensions(2);

        $this->assertEquals(['width' => 10, 'height' => 10], $result);
    }

    public function testGetRecommendedDimensionsForFourPlayers(): void
    {
        $result = $this->policy->getRecommendedDimensions(4);

        $this->assertEquals(['width' => 15, 'height' => 15], $result);
    }

    public function testGetRecommendedDimensionsForMoreThanFourPlayers(): void
    {
        $result = $this->policy->getRecommendedDimensions(6);

        $this->assertEquals(['width' => 20, 'height' => 20], $result);
    }
} 
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

    public function testValidateDimensionsPassesWithValidDimensions(): void
    {
        // Should not throw any exception
        $this->policy->validateDimensions(15, 20);

        $this->assertTrue(true); // If we reach here, test passed
    }

    public function testValidateDimensionsPassesWithMinimumDimensions(): void
    {
        // Should not throw any exception
        $this->policy->validateDimensions(10, 10);

        $this->assertTrue(true); // If we reach here, test passed
    }

    public function testValidateDimensionsPassesWithMaximumDimensions(): void
    {
        // Should not throw any exception
        $this->policy->validateDimensions(100, 100);

        $this->assertTrue(true); // If we reach here, test passed
    }

    public function testValidateDimensionsThrowsExceptionWithInvalidWidth(): void
    {
        $this->expectException(InvalidMapDimensionsException::class);

        $this->policy->validateDimensions(9, 10);
    }

    public function testValidateDimensionsThrowsExceptionWithInvalidHeight(): void
    {
        $this->expectException(InvalidMapDimensionsException::class);

        $this->policy->validateDimensions(10, 101);
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
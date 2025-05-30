<?php

namespace Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerAttributeService;
use App\Domain\Player\Service\PlayerAttributeDomainService;
use App\Domain\Player\ValueObject\PlayerId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use InvalidArgumentException;

/**
 * Unit tests for PlayerAttributeService
 */
class PlayerAttributeServiceTest extends TestCase
{
    private PlayerAttributeService $service;
    private PlayerAttributeDomainService|MockObject $attributeDomainService;

    protected function setUp(): void
    {
        $this->attributeDomainService = $this->createMock(PlayerAttributeDomainService::class);
        $this->service = new PlayerAttributeService($this->attributeDomainService);
    }

    public function testGeneratePlayerId(): void
    {
        $expectedPlayerId = new PlayerId('player_12345');

        $this->attributeDomainService->expects($this->once())
            ->method('generatePlayerId')
            ->willReturn($expectedPlayerId);

        $result = $this->service->generatePlayerId();

        $this->assertEquals('player_12345', $result);
    }

    public function testGeneratePlayerColor(): void
    {
        $expectedColor = 0xFF6B6B;

        $this->attributeDomainService->expects($this->once())
            ->method('generatePlayerColor')
            ->willReturn($expectedColor);

        $result = $this->service->generatePlayerColor();

        $this->assertEquals($expectedColor, $result);
    }

    public function testGetAvailableColors(): void
    {
        $expectedColors = [0xFF6B6B, 0x4ECDC4, 0x45B7D1];

        $this->attributeDomainService->expects($this->once())
            ->method('getAvailableColors')
            ->willReturn($expectedColors);

        $result = $this->service->getAvailableColors();

        $this->assertEquals($expectedColors, $result);
    }

    public function testValidateAndNormalizeNameWithValidName(): void
    {
        $rawName = '  Test Player  ';
        $normalizedName = 'Test Player';

        $this->attributeDomainService->expects($this->once())
            ->method('normalizePlayerName')
            ->with($rawName)
            ->willReturn($normalizedName);

        $this->attributeDomainService->expects($this->once())
            ->method('isValidPlayerName')
            ->with($normalizedName)
            ->willReturn(true);

        $result = $this->service->validateAndNormalizeName($rawName);

        $this->assertEquals($normalizedName, $result);
    }

    public function testValidateAndNormalizeNameWithInvalidName(): void
    {
        $rawName = '';
        $normalizedName = '';

        $this->attributeDomainService->expects($this->once())
            ->method('normalizePlayerName')
            ->with($rawName)
            ->willReturn($normalizedName);

        $this->attributeDomainService->expects($this->once())
            ->method('isValidPlayerName')
            ->with($normalizedName)
            ->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid player name');

        $this->service->validateAndNormalizeName($rawName);
    }

    public function testIsValidColorWithValidColor(): void
    {
        $color = 0xFF6B6B;

        $this->attributeDomainService->expects($this->once())
            ->method('isValidPlayerColor')
            ->with($color)
            ->willReturn(true);

        $result = $this->service->isValidColor($color);

        $this->assertTrue($result);
    }

    public function testIsValidColorWithInvalidColor(): void
    {
        $color = 0x123456;

        $this->attributeDomainService->expects($this->once())
            ->method('isValidPlayerColor')
            ->with($color)
            ->willReturn(false);

        $result = $this->service->isValidColor($color);

        $this->assertFalse($result);
    }

    public function testGetColorName(): void
    {
        $color = 0xFF6B6B;
        $expectedName = 'Red';

        $this->attributeDomainService->expects($this->once())
            ->method('getColorName')
            ->with($color)
            ->willReturn($expectedName);

        $result = $this->service->getColorName($color);

        $this->assertEquals($expectedName, $result);
    }
} 
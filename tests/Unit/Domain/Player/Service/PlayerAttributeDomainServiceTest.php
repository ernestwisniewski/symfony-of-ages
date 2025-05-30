<?php

namespace Tests\Unit\Domain\Player\Service;

use App\Domain\Player\Service\PlayerAttributeDomainService;
use App\Domain\Player\ValueObject\PlayerId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for PlayerAttributeDomainService
 */
class PlayerAttributeDomainServiceTest extends TestCase
{
    private PlayerAttributeDomainService $attributeService;

    protected function setUp(): void
    {
        $this->attributeService = new PlayerAttributeDomainService();
    }

    public function testGeneratePlayerIdReturnsValidPlayerId(): void
    {
        $playerId = $this->attributeService->generatePlayerId();

        $this->assertInstanceOf(PlayerId::class, $playerId);
        $this->assertStringStartsWith('player_', $playerId->value);
        $this->assertGreaterThan(10, strlen($playerId->value));
    }

    public function testGeneratePlayerIdCreatesUniqueIds(): void
    {
        $playerId1 = $this->attributeService->generatePlayerId();
        $playerId2 = $this->attributeService->generatePlayerId();
        $playerId3 = $this->attributeService->generatePlayerId();

        $this->assertNotEquals($playerId1->value, $playerId2->value);
        $this->assertNotEquals($playerId2->value, $playerId3->value);
        $this->assertNotEquals($playerId1->value, $playerId3->value);
    }

    public function testGeneratePlayerColorReturnsValidColor(): void
    {
        $color = $this->attributeService->generatePlayerColor();

        $this->assertIsInt($color);
        $this->assertGreaterThanOrEqual(0, $color);
        $this->assertLessThanOrEqual(0xFFFFFF, $color);
    }

    public function testGeneratePlayerColorReturnsFromAvailableColors(): void
    {
        $availableColors = $this->attributeService->getAvailableColors();
        $generatedColor = $this->attributeService->generatePlayerColor();

        $this->assertContains($generatedColor, $availableColors);
    }

    #[DataProvider('validPlayerNameProvider')]
    public function testIsValidPlayerNameReturnsTrueForValidNames(string $name): void
    {
        $this->assertTrue($this->attributeService->isValidPlayerName($name));
    }

    #[DataProvider('invalidPlayerNameProvider')]
    public function testIsValidPlayerNameReturnsFalseForInvalidNames(string $name): void
    {
        $this->assertFalse($this->attributeService->isValidPlayerName($name));
    }

    #[DataProvider('validColorProvider')]
    public function testIsValidPlayerColorReturnsTrueForValidColors(int $color): void
    {
        $this->assertTrue($this->attributeService->isValidPlayerColor($color));
    }

    #[DataProvider('invalidColorProvider')]
    public function testIsValidPlayerColorReturnsFalseForInvalidColors(int $color): void
    {
        $this->assertFalse($this->attributeService->isValidPlayerColor($color));
    }

    public function testGetAvailableColorsReturnsExpectedColors(): void
    {
        $colors = $this->attributeService->getAvailableColors();

        $this->assertIsArray($colors);
        $this->assertNotEmpty($colors);
        $this->assertCount(8, $colors); // Updated to match actual implementation

        $expectedColors = [
            0xFF6B6B, // Red
            0x4ECDC4, // Teal
            0x45B7D1, // Blue
            0x96CEB4, // Green
            0xFECA57, // Yellow
            0xFF9FF3, // Pink
            0x54A0FF, // Light Blue
            0x5F27CD  // Purple
        ];

        $this->assertEquals($expectedColors, $colors);
    }

    #[DataProvider('nameNormalizationProvider')]
    public function testNormalizePlayerName(string $input, string $expected): void
    {
        $normalized = $this->attributeService->normalizePlayerName($input);
        
        $this->assertEquals($expected, $normalized);
    }

    #[DataProvider('colorNameProvider')]
    public function testGetColorName(int $color, string $expectedName): void
    {
        $colorName = $this->attributeService->getColorName($color);
        
        $this->assertEquals($expectedName, $colorName);
    }

    public function testGetColorNameForUnknownColor(): void
    {
        $unknownColor = 0x123456;
        $colorName = $this->attributeService->getColorName($unknownColor);
        
        $this->assertEquals('Unknown', $colorName);
    }

    public static function validPlayerNameProvider(): array
    {
        return [
            'Simple name' => ['Player'],
            'Name with spaces' => ['Player One'],
            'Name with numbers' => ['Player123'],
            'Name with special chars' => ['Player_One'],
            'Minimum length' => ['ABC'],
            'Maximum length' => [str_repeat('A', 50)],
        ];
    }

    public static function invalidPlayerNameProvider(): array
    {
        return [
            'Empty string' => [''],
            'Only spaces' => ['   '],
            'Too long' => [str_repeat('A', 51)],
        ];
    }

    public static function validColorProvider(): array
    {
        return [
            'Red' => [0xFF6B6B],
            'Teal' => [0x4ECDC4],
            'Blue' => [0x45B7D1],
            'Green' => [0x96CEB4],
            'Yellow' => [0xFECA57],
            'Pink' => [0xFF9FF3],
        ];
    }

    public static function invalidColorProvider(): array
    {
        return [
            'Black' => [0x000000], // Not in available colors
            'White' => [0xFFFFFF], // Not in available colors
            'Random color' => [0x123456], // Not in available colors
            'Negative color' => [-1],
            'Too large color' => [0x1000000],
            'Very negative' => [-999999],
        ];
    }

    public static function nameNormalizationProvider(): array
    {
        return [
            'Trim spaces' => ['  Player  ', 'Player'],
            'Already normalized' => ['Player', 'Player'],
        ];
    }

    public static function colorNameProvider(): array
    {
        return [
            'Red' => [0xFF6B6B, 'Red'],
            'Teal' => [0x4ECDC4, 'Teal'],
            'Blue' => [0x45B7D1, 'Blue'],
            'Green' => [0x96CEB4, 'Green'],
            'Yellow' => [0xFECA57, 'Yellow'],
            'Pink' => [0xFF9FF3, 'Pink'],
            'Light Blue' => [0x54A0FF, 'Light Blue'],
            'Purple' => [0x5F27CD, 'Purple'],
        ];
    }
} 
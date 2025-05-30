<?php

namespace Tests\Unit\Domain\Player\ValueObject;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlayerId value object
 */
class PlayerIdTest extends TestCase
{
    public function testCanCreatePlayerIdWithValidValue(): void
    {
        $playerId = new PlayerId('player_123');
        
        $this->assertEquals('player_123', $playerId->value);
    }

    public function testThrowsExceptionForEmptyValue(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Player ID cannot be empty');
        
        new PlayerId('');
    }

    public function testThrowsExceptionForTooShortValue(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Player ID must be at least 3 characters long');
        
        new PlayerId('ab');
    }

    public function testCanCreatePlayerIdWithMinimumLength(): void
    {
        $playerId = new PlayerId('abc');
        
        $this->assertEquals('abc', $playerId->value);
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $playerId1 = new PlayerId('player_123');
        $playerId2 = new PlayerId('player_123');
        
        $this->assertTrue($playerId1->equals($playerId2));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $playerId1 = new PlayerId('player_123');
        $playerId2 = new PlayerId('player_456');
        
        $this->assertFalse($playerId1->equals($playerId2));
    }

    public function testGenerateCreatesValidPlayerId(): void
    {
        $playerId = PlayerId::generate();
        
        $this->assertInstanceOf(PlayerId::class, $playerId);
        $this->assertStringStartsWith('player_', $playerId->value);
        $this->assertGreaterThanOrEqual(10, strlen($playerId->value));
    }

    public function testGenerateCreatesUniqueIds(): void
    {
        $playerId1 = PlayerId::generate();
        $playerId2 = PlayerId::generate();
        
        $this->assertNotEquals($playerId1->value, $playerId2->value);
    }

    public function testToString(): void
    {
        $playerId = new PlayerId('player_123');
        
        $this->assertEquals('player_123', $playerId->__toString());
    }

    public function testToStringWithGeneratedId(): void
    {
        $playerId = PlayerId::generate();
        $value = $playerId->value;
        
        $this->assertEquals($value, (string)$playerId);
    }
} 
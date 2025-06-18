<?php

namespace App\Tests\Unit\Domain\Visibility\ValueObject;

use App\Domain\Visibility\ValueObject\VisibilityState;
use PHPUnit\Framework\TestCase;

class VisibilityStateTest extends TestCase
{
    public function testActiveState(): void
    {
        $state = VisibilityState::ACTIVE;
        
        $this->assertEquals('active', $state->value);
        $this->assertEquals('Active', $state->getDisplayName());
        $this->assertTrue($state->isActive());
        $this->assertFalse($state->isDiscovered());
    }

    public function testDiscoveredState(): void
    {
        $state = VisibilityState::DISCOVERED;
        
        $this->assertEquals('discovered', $state->value);
        $this->assertEquals('Discovered', $state->getDisplayName());
        $this->assertFalse($state->isActive());
        $this->assertTrue($state->isDiscovered());
    }

    public function testFromString(): void
    {
        $activeState = VisibilityState::from('active');
        $this->assertEquals(VisibilityState::ACTIVE, $activeState);
        
        $discoveredState = VisibilityState::from('discovered');
        $this->assertEquals(VisibilityState::DISCOVERED, $discoveredState);
    }
} 
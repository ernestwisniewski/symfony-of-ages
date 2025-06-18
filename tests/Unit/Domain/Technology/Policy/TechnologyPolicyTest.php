<?php

namespace Tests\Unit\Domain\Technology\Policy;

use App\Domain\Technology\Policy\TechnologyPolicy;
use App\Domain\Technology\ValueObject\TechnologyId;
use App\Domain\Technology\ValueObject\TechnologyType;
use PHPUnit\Framework\TestCase;

class TechnologyPolicyTest extends TestCase
{
    private TechnologyPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new TechnologyPolicy();
    }

    public function testArePrerequisitesMetReturnsTrueWhenNoPrerequisites(): void
    {
        $technology = [
            'id' => 'agriculture',
            'name' => 'Agriculture',
            'description' => 'A technology without prerequisites',
            'cost' => 10,
            'prerequisites' => []
        ];
        $unlockedTechnologies = [];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertTrue($result);
    }

    public function testArePrerequisitesMetReturnsTrueWhenAllPrerequisitesMet(): void
    {
        $technology = [
            'id' => 'writing',
            'name' => 'Writing',
            'description' => 'A technology with prerequisites',
            'cost' => 20,
            'prerequisites' => [TechnologyType::AGRICULTURE]
        ];
        $unlockedTechnologies = [new TechnologyId('agriculture')];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertTrue($result);
    }

    public function testArePrerequisitesMetReturnsFalseWhenPrerequisitesNotMet(): void
    {
        $technology = [
            'id' => 'writing',
            'name' => 'Writing',
            'description' => 'A technology with prerequisites',
            'cost' => 20,
            'prerequisites' => [TechnologyType::AGRICULTURE]
        ];
        $unlockedTechnologies = [];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertFalse($result);
    }

    public function testGetMissingPrerequisitesReturnsEmptyArrayWhenNoPrerequisites(): void
    {
        $technology = [
            'id' => 'agriculture',
            'name' => 'Agriculture',
            'description' => 'A technology without prerequisites',
            'cost' => 10,
            'prerequisites' => []
        ];
        $unlockedTechnologies = [];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertEmpty($result);
    }

    public function testGetMissingPrerequisitesReturnsEmptyArrayWhenAllPrerequisitesMet(): void
    {
        $technology = [
            'id' => 'writing',
            'name' => 'Writing',
            'description' => 'A technology with prerequisites',
            'cost' => 20,
            'prerequisites' => [TechnologyType::AGRICULTURE]
        ];
        $unlockedTechnologies = [new TechnologyId('agriculture')];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertEmpty($result);
    }

    public function testGetMissingPrerequisitesReturnsMissingPrerequisites(): void
    {
        $technology = [
            'id' => 'philosophy',
            'name' => 'Philosophy',
            'description' => 'A technology with multiple prerequisites',
            'cost' => 45,
            'prerequisites' => [TechnologyType::WRITING, TechnologyType::MATHEMATICS]
        ];
        $unlockedTechnologies = [new TechnologyId('writing')];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertCount(1, $result);
        $this->assertEquals(TechnologyType::MATHEMATICS, $result[0]);
    }

    public function testArePrerequisitesMetWithMultiplePrerequisites(): void
    {
        $technology = [
            'id' => 'engineering',
            'name' => 'Engineering',
            'description' => 'A technology with multiple prerequisites',
            'cost' => 50,
            'prerequisites' => [TechnologyType::MATHEMATICS, TechnologyType::IRON_WORKING]
        ];
        $unlockedTechnologies = [
            new TechnologyId('mathematics'),
            new TechnologyId('iron_working')
        ];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertTrue($result);
    }

    public function testGetMissingPrerequisitesWithMultipleMissingPrerequisites(): void
    {
        $technology = [
            'id' => 'engineering',
            'name' => 'Engineering',
            'description' => 'A technology with multiple prerequisites',
            'cost' => 50,
            'prerequisites' => [TechnologyType::MATHEMATICS, TechnologyType::IRON_WORKING]
        ];
        $unlockedTechnologies = [];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertCount(2, $result);
        $this->assertContains(TechnologyType::MATHEMATICS, $result);
        $this->assertContains(TechnologyType::IRON_WORKING, $result);
    }
} 
<?php

namespace Tests\Unit\Domain\Technology\Policy;

use App\Domain\Technology\Policy\TechnologyPrerequisitesPolicy;
use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TechnologyPrerequisitesPolicyTest extends TestCase
{
    private TechnologyPrerequisitesPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new TechnologyPrerequisitesPolicy();
    }

    public function testArePrerequisitesMetReturnsTrueWhenNoPrerequisites(): void
    {
        $technology = Technology::create(
            new TechnologyId(Uuid::v4()->toRfc4122()),
            'Simple Technology',
            'A technology without prerequisites',
            10
        );
        $unlockedTechnologies = [];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertTrue($result);
    }

    public function testArePrerequisitesMetReturnsTrueWhenAllPrerequisitesMet(): void
    {
        $prerequisiteId = new TechnologyId(Uuid::v4()->toRfc4122());
        $technology = Technology::create(
            new TechnologyId(Uuid::v4()->toRfc4122()),
            'Advanced Technology',
            'A technology with prerequisites',
            10,
            [$prerequisiteId]
        );
        $unlockedTechnologies = [$prerequisiteId];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertTrue($result);
    }

    public function testArePrerequisitesMetReturnsFalseWhenPrerequisitesNotMet(): void
    {
        $prerequisiteId = new TechnologyId(Uuid::v4()->toRfc4122());
        $technology = Technology::create(
            new TechnologyId(Uuid::v4()->toRfc4122()),
            'Advanced Technology',
            'A technology with prerequisites',
            10,
            [$prerequisiteId]
        );
        $unlockedTechnologies = [];
        $result = $this->policy->arePrerequisitesMet($technology, $unlockedTechnologies);
        $this->assertFalse($result);
    }

    public function testGetMissingPrerequisitesReturnsEmptyArrayWhenNoPrerequisites(): void
    {
        $technology = Technology::create(
            new TechnologyId(Uuid::v4()->toRfc4122()),
            'Simple Technology',
            'A technology without prerequisites',
            10
        );
        $unlockedTechnologies = [];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertEmpty($result);
    }

    public function testGetMissingPrerequisitesReturnsEmptyArrayWhenAllPrerequisitesMet(): void
    {
        $prerequisiteId = new TechnologyId(Uuid::v4()->toRfc4122());
        $technology = Technology::create(
            new TechnologyId(Uuid::v4()->toRfc4122()),
            'Advanced Technology',
            'A technology with prerequisites',
            10,
            [$prerequisiteId]
        );
        $unlockedTechnologies = [$prerequisiteId];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertEmpty($result);
    }

    public function testGetMissingPrerequisitesReturnsMissingPrerequisites(): void
    {
        $prerequisiteId1 = new TechnologyId(Uuid::v4()->toRfc4122());
        $prerequisiteId2 = new TechnologyId(Uuid::v4()->toRfc4122());
        $technology = Technology::create(
            new TechnologyId(Uuid::v4()->toRfc4122()),
            'Advanced Technology',
            'A technology with multiple prerequisites',
            10,
            [$prerequisiteId1, $prerequisiteId2]
        );
        $unlockedTechnologies = [$prerequisiteId1];
        $result = $this->policy->getMissingPrerequisites($technology, $unlockedTechnologies);
        $this->assertCount(1, $result);
        $this->assertEquals($prerequisiteId2, $result[0]);
    }
} 
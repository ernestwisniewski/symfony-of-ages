<?php

namespace Tests\Unit\Domain\Unit\ValueObject;

use App\Domain\Unit\ValueObject\UnitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UnitIdTest extends TestCase
{
    public function testCanCreateUnitIdWithValidUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $unitId = new UnitId($uuid);

        $this->assertEquals($uuid, (string)$unitId);
        $this->assertEquals($uuid, $unitId->id);
    }

    public function testThrowsExceptionForInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new UnitId('invalid-uuid');
    }

    public function testCanCompareUnitIds(): void
    {
        $uuid1 = Uuid::v4()->toRfc4122();
        $uuid2 = Uuid::v4()->toRfc4122();

        $unitId1 = new UnitId($uuid1);
        $unitId2 = new UnitId($uuid1);
        $unitId3 = new UnitId($uuid2);

        $this->assertTrue($unitId1->isEqual($unitId2));
        $this->assertFalse($unitId1->isEqual($unitId3));
    }

    public function testUnitIdCanBeConvertedToString(): void
    {
        $uuid = Uuid::v4()->toRfc4122();
        $unitId = new UnitId($uuid);

        $this->assertIsString((string)$unitId);
        $this->assertEquals($uuid, (string)$unitId);
    }
}

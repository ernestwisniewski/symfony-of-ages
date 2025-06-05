<?php

namespace App\Tests\Unit\Domain\City;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\City;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Map\ValueObject\Position;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CityTest extends TestCase
{
    public function testFoundCity(): void
    {
        // Given
        $cityId = Uuid::v4()->toRfc4122();
        $playerId = Uuid::v4()->toRfc4122();
        $cityName = new CityName('Warsaw');
        $position = new Position(10, 5);

        $command = new FoundCityCommand(
            new CityId($cityId),
            new PlayerId($playerId),
            $cityName,
            $position
        );

        // When
        $testSupport = EcotoneLite::bootstrapFlowTesting([City::class]);

        $recordedEvents = $testSupport
            ->sendCommand($command)
            ->getRecordedEvents();

        // Then
        $this->assertEquals([
            new CityWasFounded(
                cityId: $cityId,
                ownerId: $playerId,
                name: (string)$cityName,
                x: $position->x,
                y: $position->y
            )
        ], $recordedEvents);
    }
}

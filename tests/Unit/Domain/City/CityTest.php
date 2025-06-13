<?php

namespace App\Tests\Unit\Domain\City;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\City;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\City\Policy\CityFoundingPolicy;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
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
        $gameId = Uuid::v4()->toRfc4122();
        $cityName = new CityName('Warsaw');
        $position = new Position(10, 5);
        $terrain = TerrainType::PLAINS;
        $foundedAt = Timestamp::now();
        $existingCityPositions = [];

        $command = new FoundCityCommand(
            new CityId($cityId),
            new PlayerId($playerId),
            new GameId($gameId),
            $cityName,
            $position,
            $foundedAt,
            $existingCityPositions
        );

        // When
        $testSupport = EcotoneLite::bootstrapFlowTesting([City::class], [
            new CityFoundingPolicy(),
        ]);

        $recordedEvents = $testSupport
            ->sendCommand($command)
            ->getRecordedEvents();

        // Then
        $this->assertEquals([
            new CityWasFounded(
                cityId: $cityId,
                ownerId: $playerId,
                gameId: $gameId,
                name: (string)$cityName,
                x: $position->x,
                y: $position->y,
                foundedAt: $foundedAt->format()
            )
        ], $recordedEvents);
    }
}

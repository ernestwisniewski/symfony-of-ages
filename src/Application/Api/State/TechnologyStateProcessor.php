<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Exception\InvalidOperationException;
use App\Application\Technology\Command\DiscoverTechnologyCommand;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Technology\ValueObject\TechnologyId;
use App\UI\Api\Resource\TechnologyResource;
use Ecotone\Modelling\CommandBus;

final readonly class TechnologyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $uriTemplate = $operation->getUriTemplate();
        match (true) {
            str_contains($uriTemplate, '/discover') => $this->discoverTechnology($uriVariables, $data),
            default => throw InvalidOperationException::unsupportedOperation($uriTemplate),
        };
    }

    private function discoverTechnology(array $uriVariables, TechnologyResource $data): void
    {
        $playerId = new PlayerId($uriVariables['playerId']);
        $technologyId = new TechnologyId($uriVariables['technologyId']);
        $command = new DiscoverTechnologyCommand(
            $playerId,
            $technologyId,
            Timestamp::now()
        );
        $this->commandBus->send($command);
    }
}

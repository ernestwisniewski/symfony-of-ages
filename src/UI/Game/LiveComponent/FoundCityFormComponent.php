<?php
declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use App\UI\Game\DTO\FoundCityFormDTO;
use App\UI\Game\Form\FoundCityType;
use Ecotone\Modelling\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class FoundCityFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public ?FoundCityFormDTO $initialFormData = null;
    #[LiveProp]
    public array $unitData = [];

    public function __construct(
        private readonly CommandBus $commandBus
    )
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(FoundCityType::class, new FoundCityFormDTO());
    }

    #[LiveAction]
    public function foundCity(): void
    {
        $this->submitForm();
        $formData = $this->getForm()->getData();
        $command = new FoundCityCommand(
            cityId: new CityId(Uuid::v4()->toRfc4122()),
            ownerId: new PlayerId($this->unitData['ownerId']),
            gameId: new GameId($this->unitData['gameId']),
            unitId: new UnitId($this->unitData['unitId']),
            name: new CityName($formData->cityName),
            position: new Position($this->unitData['position']['x'], $this->unitData['position']['y']),
            foundedAt: Timestamp::now(),
            existingCityPositions: []
        );
        $this->commandBus->send($command);
        $this->dispatchBrowserEvent('flash:success', [
            'message' => "City '{$formData->cityName}' founded successfully!"
        ]);
        $this->dispatchBrowserEvent('found-city', [
            'cityName' => $formData->cityName,
            'position' => $this->unitData['position']
        ]);
    }
}

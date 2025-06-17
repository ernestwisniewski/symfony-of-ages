<?php
declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use App\Application\Game\Command\CreateGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use App\UI\Game\DTO\GameCreateFormDTO;
use App\UI\Game\Form\GameCreateType;
use Ecotone\Modelling\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GameCreateFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public ?GameCreateFormDTO $initialFormData = null;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Security   $security
    )
    {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(GameCreateType::class, new GameCreateFormDTO());
    }

    #[LiveAction]
    public function createGame(): Response
    {
        $this->submitForm();
        $gameData = $this->getForm()->getData();
        $user = $this->security->getUser();
        $gameId = new GameId(Uuid::v4()->toRfc4122());
        $playerId = new PlayerId(Uuid::v4()->toRfc4122());
        $this->commandBus->send(new CreateGameCommand(
            gameId: $gameId,
            playerId: $playerId,
            name: new GameName($gameData->name),
            userId: new UserId($user->getId()),
            createdAt: Timestamp::now()
        ));
        $this->addFlash('success', 'Game "' . $gameData->name . '" created successfully!');
        $this->dispatchBrowserEvent('flash:success', [
            'message' => "Game '{$gameData->name}' founded successfully!"
        ]);
        $this->dispatchBrowserEvent('create-game', [
            'cityName' => $gameData->name,
        ]);
        return $this->redirectToRoute('app_games');
    }

    #[LiveAction]
    public function cancel(): Response
    {
        return $this->redirectToRoute('app_games');
    }
}

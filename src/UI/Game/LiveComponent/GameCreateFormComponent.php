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
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('GameCreateFormComponent')]
final class GameCreateFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Security $security
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(GameCreateType::class, new GameCreateFormDTO());
    }

    #[LiveAction]
    public function createGame(): Response
    {
        $this->submitForm();

        $form = $this->getForm();

        if (!$form->isValid()) {
            return $this->render('GameCreateFormComponent.html.twig');
        }

        /** @var GameCreateFormDTO $gameData */
        $gameData = $form->getData();

        try {
            $user = $this->security->getUser();

            if (!$user) {
                throw new \RuntimeException('User not authenticated');
            }

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

            return $this->redirectToRoute('app_games');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create game: ' . $e->getMessage());

            return $this->render('GameCreateFormComponent.html.twig');
        }
    }

    #[LiveAction]
    public function cancel(): Response
    {
        return $this->redirectToRoute('app_games');
    }
}

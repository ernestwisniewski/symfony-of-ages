<?php

namespace App\UI\Game\LiveComponent;

use App\Application\Game\Command\CreateGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use App\UI\Game\DTO\GameCreateFormDTO;
use Ecotone\Modelling\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GameCreateForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Security $security,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createFormBuilder(new GameCreateFormDTO())
            ->add('name', TextType::class, [
                'label' => 'Game Name',
                'attr' => [
                    'placeholder' => 'Enter game name...',
                    'data-live-debounce' => '300'
                ],
                'help' => 'Choose a unique name for your game (3-50 characters)',
            ])
            ->getForm();
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();

        /** @var GameCreateFormDTO $gameData */
        $gameData = $this->getForm()->getData();

        if (!$this->getForm()->isValid()) {
            return $this->render('components/GameCreateForm.html.twig');
        }

        try {
            $gameId = new GameId(Uuid::v4()->toRfc4122());
            $playerId = new PlayerId(Uuid::v4()->toRfc4122());
            
            $this->commandBus->send(new CreateGameCommand(
                gameId: $gameId,
                playerId: $playerId,
                name: new GameName($gameData->name),
                userId: new UserId($this->security->getUser()->getId()),
                createdAt: Timestamp::now()
            ));

            $this->addFlash('success', 'Game "' . $gameData->name . '" created successfully!');
            
            // Redirect using Turbo
            return $this->redirectToRoute('app_games');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create game: ' . $e->getMessage());
            return $this->render('components/GameCreateForm.html.twig');
        }
    }

    #[LiveAction]
    public function cancel()
    {
        return $this->redirectToRoute('app_games');
    }
} 
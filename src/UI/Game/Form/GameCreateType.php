<?php

namespace App\UI\Game\Form;

use App\UI\Game\DTO\GameCreateFormDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Game Name',
                'attr' => [
                    'placeholder' => 'Enter game name...',
                    'data-live-debounce' => '300'
                ],
                'help' => 'Choose a unique name for your game (3-50 characters)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameCreateFormDTO::class,
        ]);
    }
} 
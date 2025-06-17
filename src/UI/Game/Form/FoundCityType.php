<?php

namespace App\UI\Game\Form;

use App\Domain\Shared\ValueObject\ValidationConstants;
use App\UI\Game\DTO\FoundCityFormDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoundCityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cityName', TextType::class, [
                'label' => 'City Name',
                'attr' => [
                    'placeholder' => 'Enter city name...',
                    'data-live-debounce' => '300'
                ],
                'help' => 'Choose a name for your new city (' . ValidationConstants::MIN_CITY_NAME_LENGTH . '-' . ValidationConstants::MAX_CITY_NAME_LENGTH . ' characters)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoundCityFormDTO::class,
        ]);
    }
}

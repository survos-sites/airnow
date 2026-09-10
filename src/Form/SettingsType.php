<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('zipCode', TextType::class, [
                'label' => 'ZIP code',
                'attr' => ['inputmode' => 'numeric', 'maxlength' => 5, 'autocomplete' => 'postal-code'],
                'constraints' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{5}$/', message: 'Enter a five-digit ZIP code.')],
            ])
            ->add('apiKey', PasswordType::class, [
                'label' => 'AirNow API key', 'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'help' => 'Leave blank to keep your current key. Keys are never displayed.',
                'constraints' => [new Assert\Length(max: 200)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_token_id' => 'settings']);
    }
}

<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Exercise;
use App\Entity\WorkoutSet;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<WorkoutSet> */
final class WorkoutSetType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('exercise', EntityType::class, [
                'class' => Exercise::class,
                'choice_label' => 'name',
                'label' => 'Cvik',
                'placeholder' => '— vyberte —',
            ])
            ->add('reps', IntegerType::class, [
                'label' => 'Opak.',
                'attr' => ['min' => 0],
            ])
            ->add('weightKg', NumberType::class, [
                'label' => 'Váha (kg)',
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.25'],
            ])
            ->add('rpe', NumberType::class, [
                'label' => 'RPE',
                'required' => false,
                'scale' => 1,
                'html5' => true,
                'attr' => ['min' => 1, 'max' => 10, 'step' => '0.5'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => WorkoutSet::class,
        ]);
    }
}

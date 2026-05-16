<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Workout;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Workout> */
final class WorkoutType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Název',
            ])
            ->add('performedAt', DateType::class, [
                'label' => 'Datum',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('durationMinutes', IntegerType::class, [
                'label' => 'Doba (min)',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Poznámka',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('isTemplate', HiddenType::class, [
                'data' => $options['as_template'] ? '1' : '0',
                'mapped' => false,
            ])
            ->add('sets', CollectionType::class, [
                'entry_type' => WorkoutSetType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
                'label' => false,
                'entry_options' => ['label' => false],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => Workout::class,
            'as_template' => false,
        ]);
        $resolver->setAllowedTypes('as_template', 'bool');
    }
}

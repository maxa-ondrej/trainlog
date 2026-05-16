<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Exercise;
use App\Entity\MuscleGroup;
use App\Repository\MuscleGroupRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Exercise> */
final class ExerciseType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Název cviku',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Popis',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('muscleGroups', EntityType::class, [
                'class' => MuscleGroup::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'label' => 'Svalové partie',
                'query_builder' => static fn (MuscleGroupRepository $repo) => $repo->createQueryBuilder('mg')->orderBy('mg.name', 'ASC'),
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'Veřejný (sdílet ostatním uživatelům)',
                'required' => false,
                'disabled' => !($options['admin_mode'] ?? false),
                'help' => ($options['admin_mode'] ?? false) ? null : 'Veřejnost spravuje administrátor.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
            'admin_mode' => false,
        ]);
        $resolver->setAllowedTypes('admin_mode', 'bool');
    }
}

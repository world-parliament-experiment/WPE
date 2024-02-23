<?php

namespace AppBundle\Form;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Enum\InitiativeEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use AppBundle\Entity\Category;

class InitiativeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'initiative.edit.type',
                'required' => true,
                'choices' => InitiativeEnum::getAvailableTypes(),
                'choices_as_values' => true,
                'choice_label' => function($choice) {
                    return InitiativeEnum::getTypeName($choice);
                }
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'initiative.edit.state',
                'required' => true,
                'choices' => InitiativeEnum::getAvailableStates(),
                'choices_as_values' => true,
                'choice_label' => function($choice) {
                    return InitiativeEnum::getStateName($choice);
                }
            ])
            ->add('title', TextType::class, array(
                'label' => 'initiative.edit.title'))
            ->add('description',   CKEditorType::class, array(
                'label' => 'initiative.edit.description',
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))

            ->add('category', EntityType::class, array(
                'class'        => Category::class,
                'choice_label' => 'name',
                'label' => 'initiative.edit.category'
            ))
            // ->add('duration', TextType::class, [
            //     'label' => 'initiative.edit.duration',
            //     'disabled' => true,
            // ])
            ->add('slug', TextType::class, [
                'label' => 'initiative.edit.slug',
                'disabled' => true,
            ])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'initiative.edit.created_at',
                'widget' => 'single_text',
                'disabled' => true,
            ])
            ->add('updatedAt', DateTimeType::class, [
                'label' => 'initiative.edit.updated_at',
                'widget' => 'single_text',
                'disabled' => true,
            ])
            ->add('createdBy', EntityType::class, [
                'label' => 'initiative.edit.created_by',
                'disabled' => true,
                'class' => User::class,
                "choice_label" => 'fullname',
            ])
            ->add('updatedBy', EntityType::class, [
                'label' => 'initiative.edit.updated_by',
                'disabled' => true,
                'class' => User::class,
                "choice_label" => 'fullname',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Initiative::class
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_initiative_form';
    }

}

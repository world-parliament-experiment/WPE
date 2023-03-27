<?php

namespace AppBundle\Form;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Enum\CommentEnum;
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

class CommentAdminForm extends CommentForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('state', ChoiceType::class, [
                'label' => 'initiative.edit.state',
                'required' => true,
                'choices' => CommentEnum::getAvailableStates(),
                // 'choices_as_values' => true,
                'choice_label' => function($choice) {
                    return CommentEnum::getStateName($choice);
                }
            ])
            ->add('note',   CKEditorType::class, array(
                'label' => 'comment.edit.note',
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))

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
            'data_class' => Comment::class
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_comment_form';
    }

}

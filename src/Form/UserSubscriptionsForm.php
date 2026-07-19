<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSubscriptionsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subscribedCategories', EntityType::class, [
                'class' => Category::class,
                'multiple' => true,
                'expanded' => true,
                'label' => 'user.subscriptions.categories',
                'choice_label' => 'name',
                'by_reference' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'user.subscriptions.submit',
                'attr' => ['class' => 'btn-danger btn-block btn-lg']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}

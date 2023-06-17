<?php

namespace AppBundle\Form;
use AppBundle\Entity\Comment;
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

class CommentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('message',   CKEditorType::class, array(
                'label' => 'comment.edit.message',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))


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

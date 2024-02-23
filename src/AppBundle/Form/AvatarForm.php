<?php

namespace AppBundle\Form;
use AppBundle\Entity\User;
use AppBundle\Form\Type\AvatarType;
use Symfony\Component\Asset\Package;
use AppBundle\Entity\UserImage;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvatarForm extends AbstractType
{





    public function buildForm(FormBuilderInterface $builder,array $options)
    {






        $builder
            ->add('avatar',ChoiceType::class,
                array('choices' => array(
                    'avatar1' => '1' ,
                    'avatar2' => '2',
                    'avatar3' => '3',
                    'avatar4' => '4'),
                    'choices_as_values' => true,
                    'multiple'=>false,
                    'expanded'=>true ,
                    'mapped' => false,
                    'label'=>false,

                )

            );

    }




    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserImage::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_avatar_form';
    }

}

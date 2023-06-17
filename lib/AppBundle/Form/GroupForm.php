<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', TextareaType::class)
            ->add('roles', TextareaType::class)
            ;
        $builder->get('roles')
            ->addModelTransformermb(new CallbackTransformer(
                function ($rolesAsArray) {
                    // transform the array to a string
                    return implode("\n", $rolesAsArray);
                },
                function ($rolesAsString) {
                    // transform the string back to an array
                    $result = array();
                    foreach(explode("\n", $rolesAsString) as $role) {
                        if(trim($role) != ""){
                            $result[] = trim($role);
                        }
                    }
                    return $result;
                }
            ))
        ;

    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\GroupFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_group';
    }

}

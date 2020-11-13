<?php

namespace AppBundle\Form;


use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class CategoryForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder


            ->add('name', TextType::class, array('label' => 'category.edit.name'))
            ->add('description',
                CKEditorType::class, array(
                    'config' => array(
                        'uiColor' => '#ffffff',
                        //...
                    )))

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Category'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_category_form';
    }
}

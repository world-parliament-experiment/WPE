<?php

namespace AppBundle\Form;


use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use AppBundle\Enum\CategoryEnum;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('type', ChoiceType::class, [
                'label' => 'category.edit.type',
                'required' => true,
                'choices' => CategoryEnum::getAvailableTypes(),
                // 'choices_as_values' => true,
                'choice_label' => function($choice) {
                    return CategoryEnum::getTypeName($choice);
                }
            ])

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

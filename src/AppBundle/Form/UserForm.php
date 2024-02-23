<?php

namespace AppBundle\Form;

use AppBundle\Entity\Group;
use AppBundle\Entity\User;
use AppBundle\Repository\GroupRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        if(isset($options['isEdit']) && $options['isEdit']) {
            $builder->add('enabled', CheckboxType::class, [
                'label' => 'user.label.enabled',
                'label_attr' => array('class' => 'checkbox-inline'),
                'required' => false,
            ]);
        }else{
            $builder->remove('plainPassword');
        }
        $builder->add('firstname',
            TextType::class,
            array('label' => 'form.firstname', 'translation_domain' => 'FOSUserBundle',
            ))
            ->add('lastname', TextType::class, array('label' => 'form.lastname', 'translation_domain' => 'FOSUserBundle'))
            ->add('gender', ChoiceType::class, array('label' => 'form.gender', 'translation_domain' => 'FOSUserBundle',
                'choices' => [
                    'not stated' => 'not stated',
                    'female' => 'female',
                    'male' => 'male',
                    'divers' => 'divers',
                ],
            ))
            ->add('birthday', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.birthday', 'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'js-datepicker'],

                'html5' => false,
                'format' => 'MM/dd/yyyy'
            ])
            ->add(
                'country',
                CountryType::class,
                [

                    'label' => 'form.country',
                    'translation_domain' => 'FOSUserBundle',

                    'attr' => [
                        'data-field' => 'country',
                    ],
                    'choice_loader' => new CallbackChoiceLoader(function () {
                        return array_flip(Intl::getRegionBundle()->getCountryNames());
                    }),
                    'preferred_choices' => array('DE'),
                ]
            )
            ->add('description', CKEditorType::class, array(
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))
            ->add('city', TextType::class, array('label' => 'form.city', 'translation_domain' => 'FOSUserBundle'))
            ->add('userroles', TextareaType::class, ['label' => 'user.label.userroles'])

            /*->add('userroles', TextareaType::class, ['label' => 'user.label.userroles'])
            ->add('groups',  EntityType::class, [
                'label' => 'user.label.groups',
                /*'placeholder' => 'user.label.groups_placeholder',
                'class' => Group::class,
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'required' => true,
                'attr' => ['class' => 'js-select2-single'],
                'choice_label' => 'name',
                'query_builder' => function(GroupRepository $repo) {
                    return $repo->createAlphabeticalQueryBuilder();
                }
            ])
            */
        ;
        $builder->get('userroles')
            ->addModelTransformer(new CallbackTransformer(
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
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user';
    }

    // For Symfony 2.x
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
            'validation_groups' => array('Profile','NewUser'),
        ));
        $resolver->setRequired("isEdit");
    }


}

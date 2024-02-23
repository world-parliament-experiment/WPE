<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Entity\UserImage;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;


class ProfileForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('email', EmailType::class, array('label' => 'form.email', 'help' => 'form.email_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('username', null, array('label' => 'form.username', 'help' => 'form.username_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('firstname', TextType::class, array('label' => 'form.firstname', 'help' => 'form.firstname_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('lastname', TextType::class, array('label' => 'form.lastname', 'help' => 'form.lastname_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('gender', ChoiceType::class, array('label' => 'form.gender', 'help' => 'form.gender_help', 'translation_domain' => 'FOSUserBundle',
                'choices' => [
                    'not stated' => 'not stated',
                    'female' => 'female',
                    'male' => 'male',
                    'divers' => 'divers',
                ],
            ))
            ->add(
                'country',
                CountryType::class,
                [

                    'label' => 'form.country',
                    'help' => 'form.country_help',
                    'translation_domain' => 'FOSUserBundle',
                    'placeholder' => '',
                    'attr' => [
                        'data-field' => 'country',
                    ],
                    'choice_loader' => new CallbackChoiceLoader(function () {
                        return array_flip(Intl::getRegionBundle()->getCountryNames());
                    }),
                    'preferred_choices' => array('DE'),
                    'required' => true
                ]
            )
            ->add('city', TextType::class, array('label' => 'form.city', 'help' => 'form.city_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('description', CKEditorType::class, array(
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))
            ->add('birthday', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.birthday', 'translation_domain' => 'FOSUserBundle',
                'help' => 'form.birthday_help',
                'attr' => ['class' => 'js-datepicker'],

                'html5' => false,
                'format' => 'MM/dd/yyyy'
            ])
            ->add('consents', CheckboxType::class, array(
                'label' => "form.consents",
                'help' => 'form.consents_help',
                'translation_domain' => 'FOSUserBundle',
            ));

        $builder->get('consents')
            ->addModelTransformer(new CallbackTransformer(
                function ($type) {
                    return ($type > 0 ? true : false);
                },
                function ($type) {
                    return ($type ? 1 : 0);
                }
            ));

    }


    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ProfileFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_profile';
    }

    // For Symfony 2.x
    public function getName()
    {
        return $this->getBlockPrefix();
    }

}

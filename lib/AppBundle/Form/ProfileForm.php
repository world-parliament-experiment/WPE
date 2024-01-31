<?php

namespace AppBundle\Form;

use AppBundle\Entity\User;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use AppBundle\Entity\UserImage;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ProfileForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('email', EmailType::class, array('constraints' => [
                new NotBlank([
                    'message' => 'Please enter your email',
                ]),
                new Email([
                    'message' => 'Please enter a valid email address',
                ]),
            ],'label' => 'form.email', 
            'attr' => ['class' => 'form-control'],
            'help' => 'form.email_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('username', null, array('constraints' => [
                new NotBlank([
                    'message' => 'Please enter your username',
                ]),
            ],'label' => 'form.username', 
            'attr' => ['class' => 'form-control'],
            'help' => 'form.username_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('firstname', TextType::class, array('constraints' => [
                new NotBlank([
                    'message' => 'Please enter your firstname',
                ]),
            ],'label' => 'form.firstname', 
            'attr' => ['class' => 'form-control'],
            'help' => 'form.firstname_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('lastname', TextType::class, array('constraints' => [
                new NotBlank([
                    'message' => 'Please enter your lastname',
                ]),
            ],'label' => 'form.lastname', 
            'attr' => ['class' => 'form-control'],
            'help' => 'form.lastname_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('mobileNumber', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your mobile number',
                    ]) ],
                'label' => 'form.mobileNumber', 
                'help' => 'form.mobileNumber_help', 'translation_domain' => 'FOSUserBundle',    'attr' => [
                    'pattern' => '[0-9+\s]*',
                    'title' => 'Please enter phone number in digits.',
                ],
            ])
            ->add('gender', ChoiceType::class, array('label' => 'form.gender', 
            'attr' => ['class' => 'form-control'],
            'help' => 'form.gender_help', 'translation_domain' => 'FOSUserBundle',
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
                        'class' => 'form-control'
                        
                    ],
                    'preferred_choices' => array('DE'),
                    'required' => true
                ]
            )
            ->add('city', TextType::class, array('label' => 'form.city', 
            'attr' => ['class' => 'form-control'],
            'help' => 'form.city_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('description', CKEditorType::class, array(
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))
            ->add('birthday', DateType::class, [
                'widget' => 'single_text',
                'label' => 'form.birthday', 'translation_domain' => 'FOSUserBundle',
                'help' => 'form.birthday_help',
                'attr' => ['class' => 'js-datepicker form-control'],
                'html5' => false,
                'format' => 'MM/dd/yyyy'
            ])
            ->add('current_password', PasswordType::class, [
                'label' => 'form.current_password',
                'translation_domain' => 'FOSUserBundle',
                'mapped' => false,
                'constraints' => array(
                    new NotBlank(),
                    
                ),
                'attr' => array(
                    'autocomplete' => 'current-password',
                    'class' => 'form-control'
                ),
            ])
            ->add('consents', CheckboxType::class, array(
                'label' => "form.consents",
                'help' => 'form.consents_help',
                'translation_domain' => 'FOSUserBundle',
                'attr' => ['class' => 'form-control'],

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


    // public function getParent()
    // {
    //     return 'FOS\UserBundle\Form\Type\ProfileFormType';
    // }

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

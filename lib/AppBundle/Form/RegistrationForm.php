<?php

namespace AppBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\User;
use Symfony\Component\Validator\Constraints\IsTrue;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use NumberFormatter;
use Symfony\Component\Form\Extension\Core\Type\NumberType;


class RegistrationForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your email',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],'label' => 'form.email', 'help' => 'form.email_help', 'translation_domain' => 'FOSUserBundle'])
            ->add('username', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your username',
                    ]) ],
                'label' => 'form.username', 
                'help' => 'form.username_help', 
                'translation_domain' => 'FOSUserBundle'])
            ->add('firstname', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your first name',
                    ]) ],
                'label' => 'form.firstname', 
                'help' => 'form.firstname_help', 'translation_domain' => 'FOSUserBundle'])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your last name',
                    ]) ],
                'label' => 'form.lastname', 
                'help' => 'form.lastname_help', 'translation_domain' => 'FOSUserBundle'])
            ->add('mobileNumber', NumberType::class, [
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
            ->add('gender', HiddenType::class, ['label' => 'form.gender', 'empty_data' => 'not stated','translation_domain' => 'FOSUserBundle'])
            ->add(
                'country',
                CountryType::class,
                [
                    'label' => 'form.country',
                    'help' => 'form.country_help',
                    'required'=> 'true',
                    'translation_domain' => 'FOSUserBundle',
                    'placeholder' => false,
                    'attr' => [
                        'data-field' => 'country',
                    ],
                    'choice_loader' => new CallbackChoiceLoader(function () {
                        $allCountries = Countries::getNames();
                        return array_flip($allCountries);
                    }),
                ]
            )
            ->add('plainPassword', RepeatedType::class, [
                'required'=> true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your password',
                    ]) ],
                'type' => PasswordType::class,
                'options' => [
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                    ],
                ],
                'error_bubbling'  => true,
                'first_options' => ['error_bubbling' => true, 'label' => 'form.password', 'help' => 'form.password_help'],
                'second_options' => ['label' => 'form.password_confirmation'],
                'invalid_message' => 'fos_user.password.mismatch',
            ])
            ->add('recaptcha', EWZRecaptchaType::class, [
                'label' => "Please tick to continue",
                'language' => 'en',
                'attr' => [
                    'options' => [
                        'theme' => 'light',
                        'type'  => 'image',
                        'size'  => 'normal',
                        'defer' => true,
                        'async' => true,
                    ]
                ],
                'mapped'      => false,
                'constraints' => [
                    new RecaptchaTrue()
                ]
            ])
            ->add('consents', CheckboxType::class, [
                'label' => "form.consents",
                'translation_domain' => 'FOSUserBundle',
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'label' => "form.terms",
                'required'=> 'true',
                'data' => false,
                'mapped' => false,
                'constraints' => new IsTrue(),
                'translation_domain' => 'FOSUserBundle',
            ]);
        ;
        
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
    //     return 'FOS\UserBundle\Form\Type\RegistrationFormType';

    // }

    // public function getBlockPrefix()
    // {
    //     return 'app_user_registration';
    // }

    // public function configureOptions(OptionsResolver $resolver)
    // {
    //     $resolver->setDefaults([
    //         'data_class' => User::class,
    //     ]);
    // }

}
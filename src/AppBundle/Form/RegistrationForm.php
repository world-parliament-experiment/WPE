<?php

namespace AppBundle\Form;

use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
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
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;

class RegistrationForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('email', EmailType::class, array('label' => 'form.email', 'help' => 'form.email_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('username', null, array('label' => 'form.username', 'help' => 'form.username_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('firstname', TextType::class, array('label' => 'form.firstname', 'help' => 'form.firstname_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('lastname', TextType::class, array('label' => 'form.lastname', 'help' => 'form.lastname_help', 'translation_domain' => 'FOSUserBundle'))
            ->add('gender', HiddenType::class, array('label' => 'form.gender', 'empty_data' => 'not stated','translation_domain' => 'FOSUserBundle'))
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
                        return array_flip(Intl::getRegionBundle()->getCountryNames());
                    }),
                ]
            )
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'options' => array(
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => array(
                        'autocomplete' => 'new-password',
                    ),
                ),
                'error_bubbling'  => true,
                'first_options' => array('error_bubbling' => true, 'label' => 'form.password', 'help' => 'form.password_help',),
                'second_options' => array('label' => 'form.password_confirmation'),
                'invalid_message' => 'fos_user.password.mismatch',
            ))

            ->add('recaptcha', EWZRecaptchaType::class, [
                'label' => "Please tick to continue",
                'language' => 'en',
                'attr' => array(
                    'options' => array(
                        'theme' => 'light',
                        'type'  => 'image',
                        'size'  => 'normal',
                        'defer' => true,
                        'async' => true,
                    )
                ),
                'mapped'      => false,
                'constraints' => array(
                    new RecaptchaTrue()
                )
            ])
            ->add('consents', CheckboxType::class, array(
                'label' => "form.consents",
                'translation_domain' => 'FOSUserBundle',
            ))
            ->add('termsAccepted', CheckboxType::class, array(
                'label' => "form.terms",
                'required'=> 'true',
                'data' => false,
                'mapped' => false,
                'constraints' => new IsTrue(),
                'translation_domain' => 'FOSUserBundle',
            ));
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

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';

    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

}
<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VerifyForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder     
        ->add('otp', TextType::class, [
            'label' => 'form.otp', 
            'help' => 'form.otp_help', 'translation_domain' => 'FOSUserBundle'
        ]);
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

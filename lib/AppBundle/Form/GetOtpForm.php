<?php
namespace AppBundle\Form;

use AppBundle\CountriesCodes;
use AppBundle\CountryCodes;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GetOtpForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mobileNumber', NumberType::class, [
                'label' => 'form.mobileNumber',
                'help' => 'form.mobileNumber_help', 'translation_domain' => 'FOSUserBundle',    'attr' => [
                    'pattern' => '[0-9]*',
                    'title' => 'Please enter phone number in digits.',
                    'readonly' => 'true'
                ]
            ])
            ->add('countryCode', ChoiceType::class, [
                'label' => 'form.country',
                'help' => 'form.country_help', 'translation_domain' => 'FOSUserBundle', 'attr' => [
                    'title' => 'Please choose a country.'
                ],
                'choices' => array_flip($options['countries']),
                'data' => !empty($options['data']['countryCode']) ? (string)$options['data']['countryCode'] : 0
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'countries' => CountriesCodes::COUNTRY_CODE,
        ]);
    }
}

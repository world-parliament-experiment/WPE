<?php

namespace AppBundle\Form;

use AppBundle\Form\Model\Quicksearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class QuicksearchForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('query', TextType::class, [
            'label' => 'quicksearch.query',
            'required' => true ])
        ;


    }

    public function getBlockPrefix()
    {
        return 'search';
    }

    // For Symfony 2.x
    public function getName()
    {
        return $this->getBlockPrefix();
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Quicksearch::class
        ]);
    }



}

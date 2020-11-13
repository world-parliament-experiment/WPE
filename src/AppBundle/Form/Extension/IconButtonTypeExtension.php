<?php

namespace AppBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IconButtonTypeExtension extends AbstractTypeExtension
{

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['icon_before'] = isset($options['icon_before']) ? $options['icon_before'] : '';
        $view->vars['icon_after'] = isset($options['icon_after']) ? $options['icon_after'] : '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'icon_before' => null,
            'icon_after' => null
        ]);
    }

    public function getExtendedType()
    {
        return ButtonType::class;
    }
}

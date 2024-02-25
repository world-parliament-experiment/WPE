<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FutureVoteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('vote', SubmitType::class, [
                'attr' => ['class' => 'voteSubmit btn-light'],
                'label' => 'vote.future.button.vote',
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Vote'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_future_vote_form';
    }
}

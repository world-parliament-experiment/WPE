<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrentVoteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('voteYes', SubmitType::class, [
            'attr' => ['class' => 'voteSubmit'],
            'label' => 'vote.current.button.vote_yes',
        ]);

        $builder->add('voteAbstention', SubmitType::class, [
            'attr' => ['class' => 'voteSubmit'],
            'label' => 'vote.current.button.vote_abstention',
        ]);

        $builder->add('voteNo', SubmitType::class, [
            'attr' => ['class' => 'voteSubmit'],
            'label' => 'vote.current.button.vote_no',
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
        return 'app_bundle_current_vote_form';
    }
}

<?php

namespace AppBundle\Form;

use AppBundle\Entity\Delegation;
use AppBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\User;
use AppBundle\Repository\DelegationRepository;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Form\AbstractType;


use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class DelegationForm extends AbstractType
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * DelegationForm constructor.
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var Delegation $delegation */
        $delegation = isset($options['data']) ? $options['data'] : null;

        /** @var User $truster */
        $truster = $delegation ? $delegation->getTruster() : null;

        $builder->add('truster', EntityType::class, [
            'class' => User::class,
            "choice_label" => 'username',
            'label' => 'Delegate to:',
            'required'   => false,
            'query_builder' => function(EntityRepository $er) use ($user, $truster) {
                return $er->getDelegationChoiceQuery($user, $truster);
            }
        ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Delegation'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_delegation_form';
    }


}

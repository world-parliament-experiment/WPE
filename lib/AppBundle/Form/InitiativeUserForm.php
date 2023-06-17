<?php

namespace AppBundle\Form;
use AppBundle\Entity\Initiative;

use DateTime;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\Voting;
use AppBundle\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class InitiativeUserForm extends AbstractType
{
    private $em;
    private $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $enddate = new DateTime();
        $repoCategory = $this->em->getRepository(Category::class);
        $user= $this->security->getUser();
        $country = $user->getCountry();
            
        $categories = $repoCategory->createQueryBuilder("q")
            ->where("length(q.description) > 2 OR q.description = :country")
            ->setParameter("country", $country)
            ->getQuery()
            ->getResult();

        $builder

            ->add('title', TextType::class, array(
                'label' => 'initiative.edit.title'))
            ->add('description',   CKEditorType::class, array(
                'label' => 'initiative.edit.description',
                'config' => array(
                    'uiColor' => '#ffffff',
                    //...
                )))

            ->add('category', EntityType::class, array(
                'class'        => Category::class,
                'choice_label' => 'name',
                'placeholder' => "Select a category",
                'label' => 'initiative.edit.category',
                'choices' => $categories
            ))
            ->add('publish', SubmitType::class, [
                'label' => 'initiative.button.publish',
                'attr' => ['class' => 'btn-danger btn-block btn-lg'],
                // 'icon_before' => 'fas fa-share-square'
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Initiative::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'app_bundle_initiative_form';
    }
    

}

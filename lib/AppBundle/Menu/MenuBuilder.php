<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class MenuBuilder
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    private $translator;

    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authorizationChecker, TranslatorInterface $translator)
    {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
    }

    public function createMainMenu(array $options)
    {

        $menu = $this->factory->createItem('root');

        $menu->addChild('Home', ['route' => 'homepage'])
            ->setLabel($this->translator->trans('menu.home.label', [], 'messages'))
            ->setExtra("icon", "fas fa-home fa-fw");

        $menu->addChild('Future', ['route' => 'category_index', 'routeParameters' => [ 'type' => 'future'] ])
            ->setLabel($this->translator->trans('menu.future.label', [], 'messages'))
            ->setExtra("icon", "fas fa-vote-yea fa-fw");

        $menu->addChild('Ongoing Votes', ['route' => 'category_index', 'routeParameters' => [ 'type' => 'current'] ])
            ->setLabel($this->translator->trans('menu.current.label', [], 'messages'))
            ->setExtra("icon", "fas fa-vote-yea fa-fw");

        $menu->addChild('Program', ['route' => 'category_index', 'routeParameters' => [ 'type' => 'program'] ])
            ->setLabel($this->translator->trans('menu.program.label', [], 'messages'))
            ->setExtra("icon", "fas fa-book-open fa-fw");

        $menu->addChild('Past', ['route' => 'category_index', 'routeParameters' => [ 'type' => 'past'] ])
            ->setLabel($this->translator->trans('menu.past.label', [], 'messages'))
            ->setExtra("icon", "fas fa-book-open fa-fw");
   
        $menu->addChild('General Assembly', ['route' => 'general_assembly' ])
            ->setLabel($this->translator->trans('menu.assembly.label', [], 'messages'))
            ->setExtra("icon", "fas fa-users fa-fw");

        $menu->addChild('Parliament', ['route' => 'parliament' ])
            ->setLabel($this->translator->trans('menu.parliament.label', [], 'messages'))
            ->setExtra("icon", "fas fa-users fa-fw");

        $menu->addChild('FAQ', ['route' => 'faq' ])
            ->setLabel($this->translator->trans('menu.faq.label', [], 'messages'))
            ->setExtra("icon", "fas fa-question fa-fw");
        
        // IS_AUTHENTICATED_ANONYMOUSLY

        // IF- ROLE_SUPERADMIN
        // ELSE - ROLE_MODERATOR
        if ($this->authorizationChecker->isGranted('ROLE_SUPERADMIN')) {

            $menu->addChild('Admin')
                ->setChildrenAttribute("class", "dropdown")
                ->setLabel($this->translator->trans('menu.admin.label', [], 'messages'))

                ->setExtra("icon", "fas fa-user-cog fa-fw")
                ->setUri("#")
                ->addChild('Initiative', ["route" => "admin_initiative_index"])
                ->setLabel($this->translator->trans('menu.admin.initiative', [], 'messages'))

                ->getParent()
                ->addChild('Category', ["route" => "admin_category_index"])
                ->setLabel($this->translator->trans('menu.admin.category', [], 'messages'))

                ->getParent()
                ->addChild('Comment', ["route" => "admin_comment_index"])
                ->setLabel($this->translator->trans('menu.admin.comment', [], 'messages'))

                ->getParent()
                ->addChild('User', ["route" => "admin_user_index"])
                ->setLabel($this->translator->trans('menu.admin.user', [], 'messages'))

                ->getParent();
        }elseif ($this->authorizationChecker->isGranted('ROLE_MODERATOR')){
            $menu->addChild('Admin')
                ->setChildrenAttribute("class", "dropdown")
                ->setLabel("menu.admin.label")
                ->setExtra("icon", "fas fa-user-cog fa-fw")
                ->setUri("#")
                ->addChild('Comment', ["route" => "admin_comment_index"])
                ->setLabel($this->translator->trans('menu.admin.comment', [], 'messages'))

                ->getParent();
        }

        // IS_AUTHENTICATED_ANONYMOUSLY
        // IS_AUTHENTICATED_REMEMBERED
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $menu->addChild('User')
                ->setChildrenAttribute("class", "dropdown")
                ->setExtra("icon", "fas fa-user fa-fw")
                ->setLabel("")
                ->setUri("#")
                ->addChild('Profile', ["route" => "user_profile_edit" ])
                ->setLabel($this->translator->trans('menu.user.profile', [], 'messages'))
                ->getParent()
                ->addChild('Edit Avatar', ["route" => "user_avatar_edit"])
                ->setLabel($this->translator->trans('menu.user.avatar', [], 'messages'))
                ->getParent()
                ->addChild('Create Initiative', ["route" => "user_initiative_new"])
                ->setLabel($this->translator->trans('menu.user.create', [], 'messages'))
                ->getParent()
                ->addChild('Own Initiatives', ["route" => "user_initiative_index"])
                ->setLabel($this->translator->trans('menu.user.initiatives', [], 'messages'))

                ->getParent()
                ->addChild('Change Password', ["route" => "app_change_password"])
                ->setLabel($this->translator->trans('menu.user.password', [], 'messages'))

                ->getParent()
                ->addChild('Change Delegation',  ["route" => "user_delegate"])
                ->setLabel($this->translator->trans('menu.user.delegation', [], 'messages'))

                ->getParent()
                ->addChild('Friends',  ["route" => "user_friends"])
                ->setLabel($this->translator->trans('menu.user.friends', [], 'messages'))

                ->getParent()
                ->addChild('Favourites',  ["route" => "user_favourites"])
                ->setLabel($this->translator->trans('menu.user.favourites', [], 'messages'))

                ->getParent()
                ->addChild('Logout', ["route" => "logout"])
                ->setLabel($this->translator->trans('menu.user.logout', [], 'messages'))

                ->getParent()
            ;

        } else {

            $menu->addChild('User')
                ->setChildrenAttribute("class", "dropdown")
                ->setExtra("icon", "fa fa-user")
                ->setLabel("")
                ->setUri("#")
                ->addChild('Register', ["route" => "app_register"])
                ->setLabel($this->translator->trans('menu.user.register', [], 'messages'))

                ->getParent()
                ->addChild('Login', ["route" => "login"])
                ->setLabel($this->translator->trans('menu.user.login', [], 'messages'))

                ->getParent()
            ;

        }


        return $menu;
    }
}
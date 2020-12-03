<?php

namespace AppBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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

    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->factory = $factory;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function createMainMenu(array $options)
    {

        $menu = $this->factory->createItem('root');

        $menu->addChild('Home', array("route" => "homepage", "label" => "menu.home.label" ))
            ->setExtra("icon", "fas fa-home fa-fw");
        $menu->addChild('General Assembly', array("route" => "general_assembly", "label" => "menu.assembly.label" ))
            ->setExtra("icon", "fas fa-users fa-fw");
        $menu->addChild('Parliament', array("route" => "parliament", "label" => "menu.parliament.label" ))
            ->setExtra("icon", "fas fa-users fa-fw");
        $menu->addChild('Future', array("route" => "category_index", 'routeParameters' => array('type' => 'future'),"label" => "menu.current.label" ))
            ->setExtra("icon", "fas fa-vote-yea fa-fw");
        $menu->addChild('Ongoing Votes', array("route" => "category_index", 'routeParameters' => array('type' => 'current'),"label" => "menu.current.label" ))
            ->setExtra("icon", "fas fa-vote-yea fa-fw");
        $menu->addChild('Past', array("route" => "category_index", 'routeParameters' => array('type' => 'past'),"label" => "menu.past.label" ))
            ->setExtra("icon", "fas fa-book-open fa-fw");
        $menu->addChild('Program', array("route" => "category_index", 'routeParameters' => array('type' => 'program'),"label" => "menu.program.label" ))
            ->setExtra("icon", "fas fa-book-open fa-fw");

        if ($this->authorizationChecker->isGranted('ROLE_SUPERADMIN')) {

            $menu->addChild('Admin')
                ->setChildrenAttribute("class", "dropdown")
                ->setLabel("menu.admin.label")
                ->setExtra("icon", "fas fa-user-cog fa-fw")
                ->setUri("#")
                ->addChild('Initiative', array("route" => "admin_initiative_index", "label" => "menu.admin.initiative"))
                ->getParent()
                ->addChild('Category', array("route" => "admin_category_index", "label" => "menu.admin.category"))
                ->getParent()
                ->addChild('Comment', array("route" => "admin_comment_index", "label" => "menu.admin.comment"))
                ->getParent()
                ->addChild('User', array("route" => "admin_user_index", "label" => "menu.admin.user"))
                ->getParent();
        }elseif ($this->authorizationChecker->isGranted('ROLE_MODERATOR')){
            $menu->addChild('Admin')
                ->setChildrenAttribute("class", "dropdown")
                ->setLabel("menu.admin.label")
                ->setExtra("icon", "fas fa-user-cog fa-fw")
                ->setUri("#")
                ->addChild('Comment', array("route" => "admin_comment_index", "label" => "menu.admin.comment"))
                ->getParent();
        }

        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {

            $menu->addChild('User')
                ->setChildrenAttribute("class", "dropdown")
                ->setExtra("icon", "fas fa-user fa-fw")
                ->setLabel("")
                ->setUri("#")
                ->addChild('Profile', array("route" => "fos_user_profile_edit", "label" => "menu.user.profile"))
                ->getParent()
                ->addChild('Edit Avatar', array("route" => "user_avatar_edit", "label" => "menu.user.avatar"))
                ->getParent()
                ->addChild('Create Initiative', array("route" => "user_initiative_new", "label" => "menu.user.create"))
                ->getParent()
                ->addChild('Own Initiatives', array("route" => "user_initiative_index", "label" => "menu.user.initiatives"))
                ->getParent()
                ->addChild('Change Password', array("route" => "fos_user_change_password", "label" => "menu.user.password"))
                ->getParent()
                ->addChild('Change Delegation',  array("route" => "user_delegate", "label" => "menu.user.delegation"))
                ->getParent()
                ->addChild('Friends',  array("route" => "user_friends", "label" => "menu.user.friends"))
                ->getParent()
                ->addChild('Favourites',  array("route" => "user_favourites", "label" => "menu.user.favourites"))
                ->getParent()
                ->addChild('Logout', array("route" => "fos_user_security_logout", "label" => "menu.user.logout"))
                ->getParent()
            ;

        } else {

            $menu->addChild('User')
                ->setChildrenAttribute("class", "dropdown")
                ->setExtra("icon", "fa fa-user")
                ->setLabel("")
                ->setUri("#")
                ->addChild('Register', array("route" => "fos_user_registration_register", "label" => "menu.user.register"))
                ->getParent()
                ->addChild('Login', array("route" => "fos_user_security_login", "label" => "menu.user.login"))
                ->getParent()
            ;

        }

        /*

                $menu->setChildrenAttribute('class', 'nav');

                // Navigation
                $menu->addChild('menu.navigation')->setAttribute("class", 'nav-header');

                // Dashboard
                $menu->addChild('menu.dashboard', array("route" => "homepage"))
                    ->setExtra("icon", "fa fa-laptop");

                // Cart
                $menu->addChild('menu.cart', array("route" => "cart_index"))
                    ->setExtra("icon", "fa fa-shopping-cart")
                ;

                // Search
                $menu->addChild('menu.search', array("route" => "search_index"))
                    ->setExtra("icon", "fa fa-search")
                ;

                // Administration
                if ($this->authorizationChecker->isGranted('ROLE_SUPERADMIN')) {
                    $menu->addChild('menu.administration')
                        ->setChildrenAttribute("class", "sub-menu")
                        ->setAttribute("class", 'has-sub')
                        ->setExtra("caret", true)
                        ->setExtra("icon", "fa fa-database")
                        ->addChild('menu.audioformats', array("route" => "admin_audio_index"))
                        ->getParent()
                        ->addChild('menu.genres', array("route" => "admin_genre_index"))
                        ->getParent()
                        ->addChild('menu.groups', array("route" => "admin_group_index"))
                        ->getParent()
                        ->addChild('menu.languages', array("route" => "admin_language_index"))
                        ->getParent()
                        ->addChild('menu.movies', array("route" => "admin_movie_index"))
                        ->getParent()
                        ->addChild('menu.movieratings', array("route" => "admin_movierating_index"))
                        ->getParent()
                        ->addChild('menu.pricecodes', array("route" => "admin_pricecode_index"))
                        ->getParent()
                        ->addChild('menu.products', array("route" => "admin_product_index"))
                        ->getParent()
                        ->addChild('menu.screenformats', array("route" => "admin_screen_index"))
                        ->getParent()
                        ->addChild('menu.trailers', array("route" => "admin_trailer_index"))
                        ->getParent()
                        ->addChild('menu.users', array("route" => "admin_user_index"))
                        ->getParent()
                        ->addChild('menu.versions', array("route" => "admin_version_index"))
                        ->getParent()
                    ;
                }

                $menu->addChild('menu.products')
                    ->setChildrenAttribute("class", "sub-menu")
                    ->setAttribute("class", 'has-sub')
                    ->setExtra("caret", true)
                    ->setExtra("icon", "fa fa-folder")
                    ->addChild('menu.catalog', array("route" => "search_catalog"))
                    ->getParent()
                    ->addChild('menu.export', array("route" => "search_export"))
                    ->getParent()
                    ->addChild('menu.newrelease', array("route" => "search_newrelease"))
                    ->getParent()
                    ->addChild('menu.releaseschedule', array("route" => "search_release"))
                    ->getParent()
                ;

                $menu->addChild('menu.promotions')
                    ->setChildrenAttribute("class", "sub-menu")
                    ->setAttribute("class", 'has-sub')
                    ->setExtra("caret", true)
                    ->setExtra("icon", "fa fa-table")
                    ->addChild('menu.search', array("route" => "promotion_index"))
                    ->getParent()
                    ->addChild('menu.promotion.create', array("route" => "promotion_new"))
                    ->getParent();*/


        return $menu;
    }
}
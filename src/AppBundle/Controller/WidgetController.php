<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Intl;

class WidgetController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->_serializeGroups = ["simple"];
    }

    /**
     * @return Response
     */
    public function footerAction()
    {
        $em = $this->getDoctrine()->getManager();

        $categories = $em->getRepository('AppBundle:Category')
            ->getCategoryOverview('program');

        return $this->render('footer.html.twig', [
            'categories' => $categories
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function programCategoriesAction()
    {

        $em = $this->getDoctrine()->getManager();
        $cache = $this->get('cache.app')->getItem('widget_program_catgories');
        $countries = Intl::getRegionBundle()->getCountryNames();

        if (!$cache->isHit()) {

            $categories = $em->getRepository('AppBundle:Category')
                ->getCategoryOverview('program');

            $cache->set($categories);
            $cache->expiresAfter(600);

        } else {
            $categories = $cache->get();
        }

        return $this->render('Widget/programcategories.html.twig', [
            'categories' => $categories,
            'countries' => $countries
        ]);

    }

    /**
     * @return Response
     */
    public function socialFollowersAction()
    {

        return $this->render('Widget/socialfollowers.html.twig', [
        ]);

    }

    /**
     * @return Response
     */
    public function newsletterAction()
    {

        return $this->render('Widget/newsletter.html.twig', [
        ]);

    }


    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function mostCitizensByCountryAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_citizens_by_country');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $countries = $em->getRepository(User::class)
                ->getMostCitizensByCountry();

            $cache->set($countries);
            $cache->expiresAfter(600);

        } else {
            $countries = $cache->get();
        }

        return $this->render('Widget/mostcitizens.html.twig', [
            'countries' => $countries
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public
    function mostDelegationsAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_delegations');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $delegations = $em->getRepository(User::class)
                ->getMostDelegationsByUser(10);

            $cache->set($delegations);
            $cache->expiresAfter(600);

        } else {
            $delegations = $cache->get();
        }

        return $this->render('Widget/mostdelegations.html.twig', [
            'users' => $delegations
        ]);

    }


    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function lastLoginsAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_last_logins');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $logins = $em->getRepository(User::class)
                ->getLastLogins();

            $cache->set($logins);
            $cache->expiresAfter(600);

        } else {
            $logins = $cache->get();
        }

        return $this->render('Widget/lastlogins.html.twig', [
            'users' => $logins
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function lastRegistrationsAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_last_registrations');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $registrations = $em->getRepository(User::class)
                ->getLastRegistrations();

            $cache->set($registrations);
            $cache->expiresAfter(600);

        } else {
            $registrations = $cache->get();
        }

        return $this->render('Widget/lastregistrations.html.twig', [
            'users' => $registrations
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function MostCommentsAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_comments');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $comments = $em->getRepository(User::class)
                ->getMostCommentsByUser();

            $cache->set($comments);
            $cache->expiresAfter(600);

        } else {
            $comments = $cache->get();
        }

        return $this->render('Widget/mostcomments.html.twig', [
            'users' => $comments
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function MostInitiativesAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_initiatives');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $initiatives = $em->getRepository(User::class)
                ->getMostInitiativesByUser();

            $cache->set($initiatives);
            $cache->expiresAfter(600);

        } else {
            $initiatives = $cache->get();
        }

        return $this->render('Widget/mostinitiatives.html.twig', [
            'users' => $initiatives
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function MostViewsAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_views');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $initiatives = $em->getRepository(Initiative::class)
                ->getMostViewedInitiatives();

            $cache->set($initiatives);
            $cache->expiresAfter(600);

        } else {
            $initiatives = $cache->get();
        }

        return $this->render('Widget/mostviews.html.twig', [
            'initiatives' => $initiatives
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function MostPopularAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_popular');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $initiatives = $em->getRepository(Initiative::class)
                ->getMostPopularInitiatives();

            $cache->set($initiatives);
            $cache->expiresAfter(600);

        } else {
            $initiatives = $cache->get();
        }

        return $this->render('Widget/mostpopular.html.twig', [
            'initiatives' => $initiatives
        ]);

    }
    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function MostCommentedInitiativesAction()
    {

        $cache = $this->get('cache.app')->getItem('widget_most_commented_initiatives');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $initiatives = $em->getRepository(Initiative::class)
                ->getMostCommentedInitiatives();

            $cache->set($initiatives);
            $cache->expiresAfter(600);

        } else {
            $initiatives = $cache->get();
        }

        return $this->render('Widget/mostcommentedinitiatives.html.twig', [
            'initiatives' => $initiatives
        ]);

    }
}

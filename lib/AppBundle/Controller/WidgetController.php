<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Entity\Category;
use AppBundle\Enum\InitiativeEnum;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Intl\Countries;

class WidgetController extends BaseController
{
    private $cache;

    public function __construct(CacheInterface $cache, SerializerInterface $serializer)
    {
        $this->cache = $cache;
        // parent::__construct($serializer);
        // $this->_serializeGroups = ["simple"];
    }

    /**
     * @return Response
     */
    public function footerAction()
    {
        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository(Category::class)
            ->getCategoryOverview('program');

        return $this->render('footer.html.twig', [
            'categories' => $categories
        ]);

    }

    /**
     * @return Response
     * @throws InvalidArgumentException
     */
    public function CategoriesOverviewAction(string $type)
    {

        $em = $this->getDoctrine()->getManager();
        $cacheKey = 'widget_program_catgories';
        $cacheItem = $this->cache->getItem($cacheKey);

        // $cache = $this->get('cache.app')->getItem('widget_program_catgories');
        // $countries = Intl::getRegionBundle()->getCountryNames();
        $countries = Countries::getNames();

        if (!$cacheItem->isHit()) {

            $categories = $em->getRepository(Category::class)
                ->getCategoryOverview($type);

            $cacheItem->set($categories);
            $cacheItem->expiresAfter(600);

        } else {
            $categories = $cacheItem->get();
        }

        return $this->render('Widget/categoriesoverview.html.twig', [
            'categories' => $categories,
            'countries' => $countries,
            'type' => $type,
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
        $cacheKey = 'widget_most_citizens_by_country';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_citizens_by_country');

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
        $cacheKey = 'widget_most_delegations';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_delegations');

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

        $cacheKey = 'widget_last_logins';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_last_logins');

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
        $cacheKey = 'widget_last_registrations';
        $cache = $this->cache->getItem($cacheKey);

        // $cache = $this->get('cache.app')->getItem('widget_last_registrations');

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
        $cacheKey = 'widget_most_comments';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_comments');

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

        $cacheKey = 'widget_most_initiatives';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_initiatives');

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
        $cacheKey = 'widget_most_views';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_views');

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
        $cacheKey = 'widget_program_catgories';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_popular');
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

        $cacheKey = 'widget_most_commented_initiatives';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('widget_most_commented_initiatives');

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

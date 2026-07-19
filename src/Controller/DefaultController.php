<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Initiative;
use App\Enum\InitiativeEnum;
use JMS\Serializer\SerializerInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
// use Symfony\Component\Cache\Adapter\FilesystemAdapter;
// use Symfony\Component\Cache\Adapter\AdapterInterface;
use Psr\Cache\CacheItemPoolInterface;
use Doctrine\Persistence\ManagerRegistry;

class DefaultController extends BaseController
{
    private $cache;
    public $serializer;

    public function __construct(SerializerInterface $serializer,CacheItemPoolInterface $cache,ManagerRegistry $managerRegistry)
    {
        parent::__construct($serializer,$managerRegistry);
        $this->cache = $cache;
        $this->_serializeGroups = ["simple"];

    }

    /**
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function indexAction(Request $request)
    {

        $em = $this->managerRegistry->getManager();

        $countries = ['UN'];
        $categoryIds = null;
        if ($user = $this->getUser()) {
            if ($userCountry = $user->getCountry()) {
                $countries[] = $userCountry;
            }
            
            $subscribed = $user->getSubscribedCategories();
            if (!$subscribed->isEmpty()) {
                $categoryIds = $subscribed->map(fn($c) => $c->getId())->toArray();
            }
        }

        $filter = $request->query->get('type');
        $types = [InitiativeEnum::TYPE_FUTURE, InitiativeEnum::TYPE_CURRENT, InitiativeEnum::TYPE_ARTICLE];
        
        if ($filter === 'article') {
            $types = [InitiativeEnum::TYPE_ARTICLE];
        } elseif ($filter === 'proposal') {
            $types = [InitiativeEnum::TYPE_FUTURE];
        } elseif ($filter === 'vote') {
            $types = [InitiativeEnum::TYPE_CURRENT];
        }

        $feedItems = $em->getRepository(Initiative::class)->getFeedItems(20, $countries, $types, $categoryIds);

        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'feed_items' => $feedItems,
            'current_filter' => $filter
        ]);

    }

    /**
     * @Breadcrumb("breadcrumb.legal.label", attributes={"translate": true})
     * @Route("/legal", name="legal_notice")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function legalAction(Request $request)
    {

        return $this->render('default/legal.html.twig', [
        ]);

    }

    /**
     * @Breadcrumb("breadcrumb.privacy.label", attributes={"translate": true})
     * @Route("/privacy", name="privacy")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function privacyAction(Request $request)
    {

        return $this->render('default/privacy.html.twig', [
        ]);

    }

    /**
     * @Breadcrumb("breadcrumb.rules.label", attributes={"translate": true})
     * @Route("/rules", name="rules")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function rulesAction(Request $request)
    {

        return $this->render('default/rules.html.twig', [
        ]);

    }

    /**
     * @Breadcrumb("breadcrumb.faq.label", attributes={"translate": true})
     * @Route("/faq", name="faq")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function faqAction(Request $request)
    {

        return $this->render('default/faq.html.twig', [
        ]);

    }

    /**
     * @Breadcrumb("breadcrumb.disclaimer.label", attributes={"translate": true})
     * @Route("/disclaimer", name="disclaimer")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function disclaimerAction(Request $request)
    {

        return $this->render('default/disclaimer.html.twig', [
        ]);

    }

    /**
     * @Breadcrumb("breadcrumb.parliament.label", attributes={"translate": true})
     * @Route("/parliament", name="parliament")
     * @param Request $request
     * @return Response
     * @throws Exception
     */

    public function parliamentAction(Request $request)
    {

        return $this->render('default/parliament.html.twig', [
        ]);

    }

    /**
     * @Route("/parliament/members", name="parliament_members", methods={"POST", "GET"}, options={"expose"=true})
     * @param Request $request
     * @return Response
     * @throws Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */

    public function parliamentMembersAction()
    {
        $cacheKey = 'parliament_members';
        $cache = $this->cache->getItem($cacheKey);
        // $cache = $this->get('cache.app')->getItem('parliament_members');

        if (!$cache->isHit()) {

            $em = $this->managerRegistry->getManager();

            $delegations = $em->getRepository(User::class)
                ->getMostDelegationsByUser(600);

            $cache->set($delegations);
            $cache->expiresAfter(600);

        } else {
            $delegations = $cache->get();
        }

        return $this->createApiResponse([
            'success' => true,
            'data' => $delegations
        ]);

    }

    /**
     *
     * @Breadcrumb("breadcrumb.assembly.label", attributes={"translate": true})
     * @Route("/assembly", name="general_assembly")
     * @param Request $request
     * @return Response
     */
    public function assemblyAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/assembly.html.twig', [

        ]);
    }

    /**
     * Lists all users.
     *
     * @Route("/assembly/search", name="assembly_search", methods={"POST"},defaults={"id"=1},options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function assemblySearchAction(Request $request)
    {

        $em = $this->managerRegistry->getManager();
        $draw = $request->request->getInt('draw', 1);
        $start = $request->request->getInt('start', 0);
        $length = $request->request->getInt('length', 10);
        $search = $request->request->get('search');
        $columns = $request->request->get('columns');
        $order = $request->request->get('order');
        $orderBy = $columns[$order[0]['column']]['data'];
        $orderDir = $order[0]['dir'];
        $orders = array(
            array($orderBy, $orderDir)
        );
        $users = $em->getRepository(User::class)->assemblySearch($search['value'], $orders);

        $output = array();
        $output['draw'] = $draw;
        $output['recordsTotal'] = $em->getRepository(User::class)->countAllUsers(true);
        $output["recordsFiltered"] = count($users);

        $output['items'] = array_splice($users, $start, $length);
        $response = $this->createApiResponse($output, 200);

        return $response;
    }


    /**
     * @Route("/test", name="test")
     * @param Request $request
     * @throws Exception
     */

    public function testAction(Request $request)
    {


        $em = $this->managerRegistry->getManager();
        $users = $em->getRepository(User::class)->getMostDelegationsByUser();

        exit();
    }

}

<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;

class DefaultController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
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

        $em = $this->getDoctrine()->getManager();

        $initiatives = $em->getRepository(Initiative::class)->slider(25);

        $votesf = $em->getRepository(Initiative::class)
            ->future();
        $votesc = $em->getRepository(Initiative::class)
            ->current();
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'votesf' => $votesf,
            'votesc' => $votesc,
            'top' => $initiatives,
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

        $cache = $this->get('cache.app')->getItem('parliament_members');

        if (!$cache->isHit()) {

            $em = $this->getDoctrine()->getManager();

            $delegations = $em->getRepository(User::class)
                ->getMostDelegationsByUser(20);

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

        $em = $this->getDoctrine()->getManager();
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


        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository(User::class)->getMostDelegationsByUser();

        exit();
    }

}

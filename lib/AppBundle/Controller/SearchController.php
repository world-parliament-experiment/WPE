<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\User;
use AppBundle\Entity\UserImage;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;

class SearchController extends BaseController
{

    public function __construct(SerializerInterface $serializer,ManagerRegistry $managerRegistry)
    {
        parent::__construct($serializer,$managerRegistry);
        $this->_serializeGroups = ["simple"];
    }

    /**
     * Lists all initiatives matching query in title
     *
     * @Breadcrumb("breadcrumb.search.label", attributes={"translate": true})
     * @Route("/quicksearch", name="quicksearch", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function quicksearchAction(Request $request)
    {
        $em = $this->managerRegistry->getManager();
        $submittedToken = $request->request->get('search_token');

        $searchForm = $this->createForm('AppBundle\Form\QuicksearchForm',null,array('csrf_protection' => false));
        $searchForm->handleRequest($request);

        $initiatives = array();

        if ($this->isCsrfTokenValid('top-search', $submittedToken) && $searchForm->isSubmitted() && $searchForm->isValid()) {
            $initiatives = $em->getRepository(Initiative::class)->searchPublic($searchForm->getData('query'),'title','asc');
        }

        return $this->render('default/quicksearch.html.twig', array(
            'form' => $searchForm->createView(),
            'initiatives' => $initiatives,
            'valid' => $this->isCsrfTokenValid('top-search', $submittedToken) && $searchForm->isSubmitted() && $searchForm->isValid(),
            'token_error' => !$this->isCsrfTokenValid('top-search', $submittedToken),
        ));

    }



}

<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Category;

use AppBundle\Entity\Initiative;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Response;

/**
 * Category controller.
 *
 * @Route("/category")
 */
class CategoryController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->_serializeGroups = ["simple"];
    }

    /**
     * @Breadcrumb("breadcrumb.{type}.label", attributes={"translate": true})
     * @Route("/{type}", requirements={"type" = "(future|current|past|program)"}, name="category_index")
     */

    public function listCategoryOverviewAction($type)
    {
        $em = $this->getDoctrine()->getManager();

        if ($type === 'future') {

            $initiatives = $em->getRepository(Category::class)
                ->getFutureInitiatives();

            return $this->render('Category/future.html.twig', [
                'initiatives' => $initiatives
            ]);

        } elseif ($type === 'current') {
            $initiatives = $em->getRepository(Category::class)
                ->getCurrentInitiatives();

            return $this->render('Category/current.html.twig', [
                'initiatives' => $initiatives
            ]);

        } elseif ($type === 'program') {
            $categories = $em->getRepository(Category::class)
                ->getCategoryOverview($type);

            return $this->render('Category/index.html.twig', [
                'categories' => $categories,
                'type' => 'decisions',
            ]);
        } else {
            $categories = $em->getRepository(Category::class)
                ->getCategoryOverview($type);
    
            return $this->render('Category/index.html.twig', [
                'categories' => $categories,
                'type' => 'archive',
            ]);
        }
    }

    /**
     * @Breadcrumb("breadcrumb.{type}.label", route={"name"="category_index", "parameters"={"type"="{type}"}}, attributes={"translate": true})
     * @Breadcrumb("{category.name}")
     * @Route("/{type}/{id}/{slug}", requirements={"id" = "\d+","type" = "(future|current|past|program)"}, name="category_type")
     * @param Category $category {type}
     * @param $type
     * @return Response
     */
    public function listCategoryAction(Category $category, $type)
    {
        return $this->render('Category/category.html.twig', [
            "category" => $category,
            "type" => $type,
        ]);

    }

    /**
     * Lists all initiative entities of certain type.
     *
     * @Route("/{type}/{id}/{slug}/ajax", name="category_type_search", requirements={"type" = "(future|current|past|program)"},defaults={"id" = 0}, methods={"POST","GET"}, options={"expose"=true})
     * @param Request $request
     * @param Category $category
     * @param $type
     * @return JsonResponse
     */
    public function listCategorySearchAction(Request $request, Category $category, $type)
    {

        $em = $this->getDoctrine()->getManager();

        $draw = $request->request->getInt('draw', 1);
        $start = $request->request->getInt('start', 0);
        $search = $request->request->get('search');
        $length = $request->request->getInt('length', 10);
        $columns = $request->request->get('columns');
        $order = $request->request->get('order');
        $idx = $order[0]['column'];
        $orderBy = preg_replace('/^title/', 'i.title', $columns[$order[0]['column']]['data']);
        $orderBy = preg_replace('/^createdBy.username/', 'c.username', $orderBy);
        $orderBy = preg_replace('/^createdAt/', 'i.createdAt', $orderBy);
        $orderDir = $order[0]['dir'];

        $initiatives = $em->getRepository(Initiative::class)->searchCategoryType($search['value'], $orderBy, $orderDir, $category,$type);

        $output = array();
        $output['draw'] = $draw;
        try {
            $output['recordsTotal'] = $em->getRepository(Initiative::class)->countAllTypeInitiatives($category,$type);
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }
        $output["recordsFiltered"] = count($initiatives);

        $output['items'] = array_splice($initiatives, $start, $length);
        $response = $this->createApiResponse($output, 200);

        return $response;

    }


    /**
     * Lists all category entities.
     *
     * @Route("/delegatec", name="category_delegate", methods={"GET"})
     * @return Response
     */
    public function indexAction()
    {

        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('Delegation/category.html.twig', array(
            'categories' => $categories,
        ));
    }

}



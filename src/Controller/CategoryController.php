<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Initiative;
use JMS\Serializer\SerializerInterface;
use App\Enum\InitiativeEnum;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Category controller.
 *
 * @Route("/category")
 */
class CategoryController extends BaseController
{

    public function __construct(SerializerInterface $serializer,ManagerRegistry $managerRegistry)
    {
        parent::__construct($serializer,$managerRegistry);
        $this->_serializeGroups = ["simple"];
    }

    /**
     * @Breadcrumb("breadcrumb.{type}.label", attributes={"translate": true})
     * @Route("/{type}", requirements={"type" = "(future|current|past|program|article)"}, name="category_index")
     */

    public function listCategoryOverviewAction(Request $request, $type)
    {
        // dd($type);
        $em = $this->managerRegistry->getManager();
        $q = $request->query->get('q');

        if ($type === 'future') {
            $user = $this->getUser();
            $countryCode = ($user && $user->getCountry()) ? $user->getCountry() : null;

            $initiatives = $em->getRepository(Category::class)
                ->getFutureInitiativesByUser($countryCode, $q);

            return $this->render('Category/future.html.twig', [
                'initiatives' => $initiatives,
                'type' => $type,
                'alias' => 'proposals',
            ]);

        } elseif ($type === 'current') {
            $user = $this->getUser();
            $countryCode = ($user && $user->getCountry()) ? $user->getCountry() : null;

            $initiatives = $em->getRepository(Category::class)
                ->getCurrentInitiativesByUser($countryCode, $q);

            return $this->render('Category/current.html.twig', [
                'initiatives' => $initiatives,
                'type' => $type,
                'alias' => 'votes',
            ]);

        } elseif ($type === 'program') {
            $initiatives = $em->getRepository(Category::class)
                ->getProgramInitiatives($q);

            return $this->render('Category/program.html.twig', [
                'initiatives' => $initiatives,
                'type' => $type,
                'alias' => 'decisions',
            ]);
        } elseif ($type === 'article') {
            if ($q) {
                $initiatives = $em->getRepository(Initiative::class)->createQueryBuilder('i')
                    ->leftJoin('i.createdBy', 'creator')
                    ->where('i.type = :type')
                    ->andWhere('i.state = :state')
                    ->andWhere('LOWER(i.title) LIKE :query OR LOWER(creator.username) LIKE :query')
                    ->setParameters([
                        'type' => InitiativeEnum::TYPE_ARTICLE,
                        'state' => InitiativeEnum::STATE_ACTIVE,
                        'query' => '%' . strtolower($q) . '%'
                    ])
                    ->orderBy('i.publishedAt', 'DESC')
                    ->setMaxResults(500)
                    ->getQuery()
                    ->getResult();
            } else {
                $initiatives = $em->getRepository(Initiative::class)->findBy(
                    ['type' => InitiativeEnum::TYPE_ARTICLE, 'state' => InitiativeEnum::STATE_ACTIVE],
                    ['publishedAt' => 'DESC'],
                    500
                );
            }

            return $this->render('Category/article.html.twig', [
                'initiatives' => $initiatives,
                'type' => $type,
                'alias' => 'articles',
            ]);
        } else {
            $initiatives = $em->getRepository(Category::class)
                ->getPastInitiatives($q);
    
            return $this->render('Category/past.html.twig', [
                'initiatives' => $initiatives,
                'type' => $type,
                'alias' => 'archive',
            ]);
        }
    }

    /**
     * @Breadcrumb("breadcrumb.{type}.label", route={"name"="category_index", "parameters"={"type"="{type}"}}, attributes={"translate": true})
     * @Breadcrumb("{category.name}")
     * @Route("/{type}/{id}/{slug}", requirements={"id" = "\d+", "type" = "(future|current|past|program|article)"}, name="category_type")
     * @param Category $category {type}
     * @param $type
     * @return Response
     */
    public function listCategoryAction(int $id, $type, Category $category)
    {
        $em = $this->managerRegistry->getManager();
        $category = $em->getRepository(Category::class)->find($id);

        if ($type === 'program') {
            $initiatives = $em->getRepository(Category::class)
                ->getInitiatives($category, InitiativeEnum::TYPE_PROGRAM);

            return $this->render('Category/program.html.twig', [
                'category' => $category,
                'type' => $type,
                'alias' => 'adopted votes',
                'initiatives' => $initiatives,
            ]);
        } elseif ($type === 'past') {
            $initiatives = $em->getRepository(Category::class)
                ->getInitiatives($category, InitiativeEnum::TYPE_PAST);

            return $this->render('Category/past.html.twig', [
                'category' => $category,
                'type' => $type,
                'alias' => 'unsuccessful votes',
                'initiatives' => $initiatives,
            ]);
        } elseif ($type === 'current') {
            
            $initiatives = $em->getRepository(Category::class)
            ->getInitiatives($category, InitiativeEnum::TYPE_CURRENT);
            
            return $this->render('Category/current.html.twig', [
                'category' => $category,
                'type' => $type,
                'alias' => 'votes',
                'initiatives' => $initiatives,
            ]);
        } elseif ($type === 'future') {

            $initiatives = $em->getRepository(Category::class)
            ->getInitiatives($category, InitiativeEnum::TYPE_FUTURE);

            return $this->render('Category/future.html.twig', [
                'category' => $category,
                'type' => $type,
                'alias' => 'proposals',
                'initiatives' => $initiatives,
            ]);
        } elseif ($type === 'article') {

            $initiatives = $em->getRepository(Category::class)
                ->getInitiatives($category, InitiativeEnum::TYPE_ARTICLE);

            return $this->render('Category/article.html.twig', [
                'category' => $category,
                'type' => $type,
                'alias' => 'articles',
                'initiatives' => $initiatives,
            ]);
        }
    }

    /**
     * Lists all initiative entities of certain type.
     *
     * @Route("/{type}/{id}/{slug}/ajax", name="category_type_search", requirements={"type" = "(future|current|past|program|article)"},defaults={"id" = 0}, methods={"POST","GET"}, options={"expose"=true})
     * @param Request $request
     * @param Category $category
     * @param $type
     * @return JsonResponse
     */
    public function listCategorySearchAction(Request $request, int $id, $type)
    {

        $em = $this->managerRegistry->getManager();
        $category = $em->getRepository(Category::class)->find($id);

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

        $em = $this->managerRegistry->getManager();
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('Delegation/category.html.twig', array(
            'categories' => $categories,
        ));
    }

}



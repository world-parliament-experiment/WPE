<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Initiative;
use AppBundle\Controller\BaseController;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// @Security("is_granted('ROLE_SUPERADMIN')")
/**
 * Initiative controller.
 *
 * @Route("/admin/initiative")
 */

class InitiativeAdminController extends BaseController
{

    

    /**
     * Lists all initiative entities.
     *
     * @Route("/", name="admin_initiative_index", methods={"GET"})
     */
    public function indexAction()
    {

        $em = $this->managerRegistry->getManager();
        $initiatives = $em->getRepository(Initiative::class)->findAll();

        return $this->render('Admin/Initiative/index.html.twig', array(
            'initiatives' => [], #$initiatives,
        ));
    }

    /**
     * Lists all initiative entities.
     *
     * @Route("/search", name="admin_initiative_search", methods={"POST","GET"}, options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function searchAction(Request $request)
    {

        $em = $this->managerRegistry->getManager();
        $draw = $request->request->getInt('draw', 1);
        $start = $request->request->getInt('start', 0);
        $length = $request->request->getInt('length', 10);
        $search = $request->request->get('search');
        $columns = $request->request->get('columns');
        $order = $request->request->get('order');
        $idx = $order[0]['column'];
        $orderBy = preg_replace('/^short/','',$columns[$order[0]['column']]['data']);
        $orderBy = preg_replace('/^typeName/','type',$orderBy);
        $orderDir = $order[0]['dir'];
        $initiatives = $em->getRepository(Initiative::class)->search($search['value'],$orderBy,$orderDir);

        $output = array();
        $output['draw'] = $draw;
        try {
            $output['recordsTotal'] = $em->getRepository(Initiative::class)->countAllInitiatives();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }
        $output["recordsFiltered"] = count($initiatives);

        $output['items'] = array_splice($initiatives,$start,$length);
        $response = $this->createApiResponse($output, 200);

        return $response;
    }

    /**
     * Creates a new initiative entity.
     *
     * @Route("/new", name="admin_initiative_new", methods={"GET","POST"})
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function newAction(Request $request, SluggerInterface $slugger)
    {
        $initiative = new Initiative();
        $form = $this->createForm('AppBundle\Form\InitiativeForm', $initiative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $initiative = $form->getData();
            $initiative->setDuration("7");
            $user = $this->getUser();
            $initiative->setCreatedBy($user);
            $initiative->setCreatedAt(new \DateTime());
            $initiative->setSlug($slugger);

            // $initiative->setCreatedAt(new \DateTime());

            $em = $this->managerRegistry->getManager();
            $em->persist($initiative);
            $em->flush();

            $this->addFlash(
                'success',
                'initiative.flash.new'
            );
            return $this->redirectToRoute('admin_initiative_edit', array('id' => $initiative->getId()));
        }

        return $this->render('Admin/Initiative/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing initiative entity.
     *
     * @Route("/{id}/edit", name="admin_initiative_edit", methods={"GET","POST"}, options={"expose"=true})
     * @param Request $request
     * @param Initiative $initiative
     * @return RedirectResponse|Response
     * @internal param MovieRating $movieRating
     */
    public function editAction(Request $request, int $id)
    {
        $em = $this->managerRegistry->getManager();
        $initiative = $em->getRepository(Initiative::class)->find($id);

        $deleteForm = $this->createDeleteForm($initiative);
        $editForm = $this->createForm('AppBundle\Form\InitiativeForm', $initiative);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $initiative = $editForm->getData();

            $em->persist($initiative);
            $em->flush();

            $this->addFlash(
                'success',
                'initiative.flash.edit'
            );

            return $this->redirectToRoute('admin_initiative_edit', array('id' => $initiative->getId()));
        }

        return $this->render('Admin/Initiative/edit.html.twig', array(
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a initiative entity.
     *
     * @Route("/{id}/delete", name="admin_initiative_delete", methods={"DELETE"})
     * @param Request $request
     * @param Initiative $initiative
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, int $id)
    {
        $em = $this->managerRegistry->getManager();
        $initiative = $em->getRepository(Initiative::class)->find($id);
        $form = $this->createDeleteForm($initiative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($initiative);
            $em->flush();

            $this->addFlash(
                'success',
                'initiative.flash.delete'
            );

        }

        return $this->redirectToRoute('admin_initiative_index');
    }

    /**
     * @param Initiative $initiative
     * @return RedirectResponse|Response
     * @Route("/{id}/show", name="admin_initiative_show", methods={"GET","POST"})
     *
     */
    public function showAction(Initiative $initiative)
    {
        $showForm = $this->createForm('AppBundle\Form\InitiativeForm', $initiative);
        $id = $showForm->getData();
        $em = $this->managerRegistry->getManager();

        $id = $em->getRepository(Initiative::class)
            ->findOneBy(['id' => $id]);
        return $this->render('Admin/Initiative/show.html.twig', array(
            'id' => $id,
        ));
    }

    /**
     * Creates a form to delete a initiative entity.
     *
     * @param Initiative $initiative The initiative entity
     *
     * @return FormInterface The form
     */
    private function createDeleteForm(Initiative $initiative)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_initiative_delete', array('id' => $initiative->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}

<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Controller\BaseController;
use JMS\Serializer\SerializerInterface;
use AppBundle\Entity\User;
use AppBundle\Entity\Category;
use Doctrine\ORM\Mapping\Id;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use AppBundle\Service\UserManagerInterface;

/**
 * User controller.
 *
 * @Route("/admin/user")
 */
class UserAdminController extends BaseController
{
    /**
     * @Route("/", name="admin_user_index")
     */
    public function indexAction()
    {
        return $this->render('Admin/User/index.html.twig', array());
    }

    /**
     * Lists all initiative entities.
     *
     * @Route("/search", name="admin_user_search", methods={"POST","GET"}, options={"expose"=true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function searchAction(Request $request)
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
        if ($orderBy == 'fullname') {
            $orders = array(
                array('lastname', $orderDir),
                array('firstname', $orderDir)
            );
        } else {
            $orders = array(
                array($orderBy, $orderDir)
            );
        }
        $users = $em->getRepository(User::class)->search($search['value'], $orders);
        
        $output = array();
        $output['draw'] = $draw;
        $output['recordsTotal'] = $em->getRepository(User::class)->countAllUsers();
        $output["recordsFiltered"] = count($users);

        $output['items'] = array_splice($users,$start,$length);
        $response = $this->createApiResponse($output, 200);

        return $response;
    }


    /**
     * Deletes a product entity.
     *
     * @Route("/{id}/delete", name="admin_user_delete")
     * #[HttpMethod("DELETE")]
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, int $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);

        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($user);
            $em->flush();

            $this->addFlash(
                'success',
                'user.flash.delete'
            );
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Creates a form to delete a product entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }


    /**
     * Displays a form to edit an existing user entity.
     *
     * @Route("/{id}/edit", name="admin_user_edit", options={"expose"=true})
     * #[HttpMethod("GET", "POST")]
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     */
    public function editAction(Request $request, int $id, UserManagerInterface $userManager)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->find($id);

        $deleteForm = $this->createDeleteForm($user);

        $editForm = $this->createForm('AppBundle\Form\UserForm', $user, array('isEdit' => true));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // $userManager = $this->container->get('fos_user.userManager');
            $userManager->updateUser($user, true);
            

            $this->addFlash(
                'success',
                'user.flash.update'
            );

            return $this->redirectToRoute('admin_user_edit', array('id' => $user->getId()));
        }
        $hierarchy = new RoleHierarchy($this->getParameter('security.role_hierarchy.roles'));
        $primaryRoles = array();
        foreach($user->getRoles() as $role) {
            $primaryRoles[] = $role;
        }
        $roles = array();
        foreach($hierarchy->getReachableRoleNames($primaryRoles) as $role){
            $roles[] = $role;
        }
        $roles = array_unique($roles);
        sort($roles);
        return $this->render('Admin/User/edit.html.twig', array(
            'movieRating' => $user,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            "roles" => $roles,
        ));
    }


}

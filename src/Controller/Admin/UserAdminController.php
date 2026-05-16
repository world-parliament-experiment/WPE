<?php

namespace App\Controller\Admin;

use App\Controller\BaseController;
use App\Entity\UserImage;
use JMS\Serializer\SerializerInterface;
use App\Entity\User;
use App\Entity\Category;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use App\Service\UserManager;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use Doctrine\Persistence\ManagerRegistry;

// @Security("is_granted('ROLE_SUPERADMIN')")
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

        $em = $this->managerRegistry->getManager();
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
        $em = $this->managerRegistry->getManager();
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
    public function editAction(Request $request, int $id, UserManager $userManager)
    {

        $em = $this->managerRegistry->getManager();
        $user = $em->getRepository(User::class)->find($id);

        $deleteForm = $this->createDeleteForm($user);

        $editForm = $this->createForm('App\Form\UserForm', $user, array('isEdit' => true));
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

    /**
     * Admin: display the avatar edit page for any user.
     *
     * @Route("/{id}/avatar", name="admin_user_avatar", methods={"GET"})
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function avatarAction(int $id)
    {
        $em = $this->managerRegistry->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $image = $em->getRepository(User::class)->getUserAvatarImage($user);

        return $this->render('User/avatar.html.twig', [
            'image'      => $image,
            'saveUrl'    => $this->generateUrl('admin_user_avatar_save', ['id' => $id]),
            'targetUser' => $user,
        ]);
    }

    /**
     * Admin: save a new avatar (base64 PNG) for any user.
     *
     * @Route("/{id}/avatar/save", name="admin_user_avatar_save", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param int $id
     */
    public function avatarSaveAction(Request $request, int $id)
    {
        $em = $this->managerRegistry->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            die('error_user_not_found');
        }

        $file = \App\Service\AvatarManager::validateFilename($request->get('filename'));
        if ($file['type'] === 'invalid') {
            die('error_file_type');
        }

        $data = \App\Service\AvatarManager::validateImagedata($_POST['imgdata'], $file['type']);
        if ($data === false) {
            die('error_file_data');
        }

        $data = base64_decode($data);
        $dir = $this->getParameter('avatar_image_path');

        if (!is_dir($dir) || !is_writable($dir)) {
            die('error_uploads_dir');
        }

        $image = $em->getRepository(User::class)->getUserAvatarImage($user);

        if (is_null($image)) {
            $image = new UserImage();
            $image->setUser($user);
        } elseif ($image->getPath() !== 'default.png') {
            @unlink($dir . $image->getPath());
        }

        $image->setImageType(UserImage::USER_IMAGE_TYPE_AVATAR);
        $image->setContentType('image/png');
        $fn = tempnam($dir, $user->getId() . '_');
        if ($fn === false) {
            die('error_file_data');
        }
        if (rename($fn, $fn . '.png') === false) {
            die('error_file_data');
        }
        $fn .= '.png';
        $image->setPath(basename($fn));
        file_put_contents($fn, $data);

        $em->persist($image);
        $em->flush();

        die('saved');
    }


}

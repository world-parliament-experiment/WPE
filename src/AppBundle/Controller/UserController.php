<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Category;
use AppBundle\Entity\Delegation;
use AppBundle\Entity\Favourite;
use AppBundle\Entity\Initiative;

use AppBundle\Entity\User;
use AppBundle\Entity\UserImage;
use AppBundle\Entity\Voting;
use AppBundle\Enum\DelegationEnum;
use AppBundle\Enum\FavouriteEnum;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use AppBundle\Service\AvatarManager;
use DateTime;

use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;


/**
 * User controller.
 *
 * @Route("/user")
 */
class UserController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->_serializeGroups = ["simple"];
    }

    /**
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.initiative.label", attributes={"translate": true})
     * @Route("/initiatives", name="user_initiative_index")
     */
    public function indexAction()
    {

        $this->denyAccessUnlessGranted('ROLE_USER');
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $initiativesDraft = $em->getRepository(Initiative::class)
            ->getDraftInitiativesByUser($user);

        $initiativesActive = $em->getRepository(Initiative::class)
            ->getActiveInitiativesByUser($user);

        $initiativesFinished = $em->getRepository(Initiative::class)
            ->getFinishedInitiativesByUser($user);

//        dump($initiativesDraft);
//        dump($initiativesActive);
//        dump($initiativesFinished);

        return $this->render('User/ownInitiatives.html.twig', array(
            "initiativesDraft" => $initiativesDraft,
            "initiativesActive" => $initiativesActive,
            "initiativesFinished" => $initiativesFinished,
        ));
    }

    /**
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("{user.username}")
     * @Route("/{id}/profile", name="user_profile_show", methods={"GET","POST"}, options={"expose"=true})
     * @param User $user
     * @return RedirectResponse|Response
     */
    public function showAction(User $user)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();
        $initiativesDraft = $em->getRepository(Initiative::class)
            ->getDraftInitiativesByUser($user);

        $initiativesActive = $em->getRepository(Initiative::class)
            ->getActiveInitiativesByUser($user);

        $initiativesFinished = $em->getRepository(Initiative::class)
            ->getFinishedInitiativesByUser($user);

//        $image = $em->getRepository(User::class)->getUserAvatarImage($user);
//
//        $response = new StreamedResponse(function () use ($image) {
//            echo stream_get_contents($image->getImageData());
//        });
//
//        $response->headers->set('Content-Type', $image->getContentType());

        return $this->render('User/profile.html.twig', array(

            'user' => $user,
            "initiativesDraft" => $initiativesDraft,
            "initiativesActive" => $initiativesActive,
            "initiativesFinished" => $initiativesFinished,

        ));

    }

    /**
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.delegation.label", attributes={"translate": true})
     *
     * @Route("/delegate", methods={"GET"},  name="user_delegate")
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */

    public function delegateAction(Request $request)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        // get global delegations for user
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $delegation = $em->getRepository(Delegation::class)->getDelegationGlobalByUser($user);

        if (is_null($delegation)) {
            $delegation = new Delegation();
            $delegation->setScope(DelegationEnum::SCOPE_PLATFORM);
        }

        $editForm = $this->createForm('AppBundle\Form\DelegationForm', $delegation, [
            'action' => $this->generateUrl('user_delegate_set', ['id' => 0])
        ]);

        // get category delegations for an user

        // get all categories order alphanumeric
        $categories = $em->getRepository(Category::class)->findBy([], ["name" => "ASC"]);
        $delegations = array();

        // get all delegations by user
        foreach ($em->getRepository(Delegation::class)->getDelegationsByUser($user) as $delegation) {
            $delegations[$delegation->getCategory()->getId()] = $delegation;
        }

        $categoryForms = array();

        foreach ($categories as $category) {
            /** @var Category $category */

            if (isset($delegations[$category->getId()])) {
                $delegation = $delegations[$category->getId()];
            } else {
                $delegation = new Delegation();
                $delegation->setCategory($category);
            }
            $categoryForm = $this->createForm('AppBundle\Form\DelegationForm', $delegation, [
                'action' => $this->generateUrl('user_delegate_set', ['id' => $category->getId()])
            ]);
            $categoryForms[] = $categoryForm->createView();

        }

        return $this->render('Delegation/edit.html.twig', array(
                'form' => $editForm->createView(),
                'categoryForms' => $categoryForms,
            )
        );
    }

    /**
     * @Route("/delegate/set/{id}", methods={"POST"}, name="user_delegate_set")
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */

    public function setDelegateAction(Request $request, $id)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if ($id == 0) {
            $delegation = $em->getRepository(Delegation::class)->getDelegationGlobalByUser($user);
        } else {
            $category = $em->getRepository(Category::class)->find($id);
            if (is_null($category)) {
                throw $this->createNotFoundException('category not found');
            }
            $delegation = $em->getRepository(Delegation::class)->getDelegationByCategoryAndUser($category, $user);
        }

        if (is_null($delegation)) {
            $delegation = new Delegation();
            $delegation->setScope($id == 0 ? DelegationEnum::SCOPE_PLATFORM : DelegationEnum::SCOPE_CATEGORY);
            $delegation->setCategory($id == 0 ? null : $category);
        }

        $editForm = $this->createForm('AppBundle\Form\DelegationForm', $delegation);
        $editForm->handleRequest($request);

        $output = array();

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $delegation = $editForm->getData();
            $delegation->setUser($user);
            $validDays = $this->getParameter('delegation_valid_days');
            $delegation->setValidUntil(new DateTime("+$validDays days"));
            if ($delegation->getTruster() == null) {
                $em->remove($delegation);
            } else {
                $em->persist($delegation);
            }
            $em->flush();
            $output['status'] = true;
        } else {
            $output['status'] = false;
            $output['status'] = $editForm->getErrors();
        }
        $response = $this->createApiResponse($output, 200);
        return $response;
    }

    /**
     *
     *
     * @Route("/delegate/unset/{id}", methods={"GET"}, name="user_delegate_unset")
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */

    public function unsetDelegateAction(Request $request, $id)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if ($id != 0) {
            $category = $em->getRepository(Category::class)->find($id);
            if (is_null($category)) {
                throw $this->createNotFoundException('category not found');
            }
            $delegation = $em->getRepository(Delegation::class)->getDelegationByCategoryAndUser($category, $user);
        } else {
            $delegation = $em->getRepository(Delegation::class)->getDelegationGlobalByUser($user);
        }
        if (!is_null($delegation)) {
            $em->remove($delegation);
            $em->flush();
        }

        $output = array();
        $output['status'] = true;

        $response = $this->createApiResponse($output, 200);
        return $response;
    }

    /**
     * Creates a new initiative entity.
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.initiative.label", route={"name"="user_initiative_index"}, attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.initiative.create.label", attributes={"translate": true})
     *
     * @Route("/create", name="user_initiative_new", methods={"GET","POST"})
     * @param Request $request
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function newAction(Request $request)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $initiative = new Initiative();

        $initiative->setState(InitiativeEnum::STATE_DRAFT);
        $initiative->setType(InitiativeEnum::TYPE_FUTURE);

        $form = $this->createForm('AppBundle\Form\InitiativeUserForm', $initiative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $initiative = $form->getData();

            if ($form->get('publish')->isClicked()) {

                // $this->denyAccessUnlessGranted("publish", $initiative);

                $voting = New Voting();

                $startdate = new DateTime();

                $initiative->setPublishedAt($startdate);
                $initiative->setState(InitiativeEnum::STATE_ACTIVE);

                $startdate->modify("+30 seconds");

                // if ($startdate < new DateTime()) {
                //     $startdate->modify("tomorrow 20:00");
                // }

                $voting->setStartdate($startdate);

                $voting->setState(VotingEnum::STATE_WAITING);
                $voting->setType(VotingEnum::TYPE_FUTURE);
                $voting->setInitiative($initiative);

                $em->persist($voting);
                $em->persist($initiative);

                $em->flush();

                $this->addFlash(
                    'success',
                    'initiative.flash.publish'
                );

                return $this->redirectToRoute('user_initiative_index');

            } else {

                $this->addFlash(
                    'success',
                    'initiative.flash.draft'
                );

            }


            $em->persist($initiative);
            $em->flush();

            return $this->redirectToRoute('user_initiative_edit', array("id" => $initiative->getId(), "slug" => $initiative->getSlug()));

        }

        return $this->render('User/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing initiative entity.
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.initiative.label", route={"name"="user_initiative_index"}, attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.initiative.edit.label", attributes={"translate": true})
     * @Route("/initiative/{id}/{slug}/edit", requirements={"id" = "\d+"}, name="user_initiative_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Initiative $initiative
     * @return RedirectResponse|Response
     *
     * @throws Exception
     */
    public function editAction(Request $request, Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("edit", $initiative);

        $deleteForm = $this->createDeleteForm($initiative);
        $editForm = $this->createForm('AppBundle\Form\InitiativeUserForm', $initiative);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $em = $this->getDoctrine()->getManager();

            $initiative = $editForm->getData();

            if ($editForm->get('publish')->isClicked()) {
                $voting = New Voting();

                $this->denyAccessUnlessGranted("publish", $initiative);

                $startdate = new DateTime();

                $initiative->setPublishedAt($startdate);
                $initiative->setState(InitiativeEnum::STATE_ACTIVE);

                $startdate->modify("+30 seconds");

                // if ($startdate < new DateTime()) {
                //     $startdate->modify("tomorrow 20:00");
                // }

                $voting->setStartdate($startdate);

                $voting->setState(VotingEnum::STATE_WAITING);
                $voting->setType(VotingEnum::TYPE_FUTURE);
                $voting->setInitiative($initiative);

                $em->persist($voting);
                $em->persist($initiative);

                $em->flush();

                $this->addFlash(
                    'success',
                    'initiative.flash.publish'
                );

                return $this->redirectToRoute('user_initiative_index');

            } else {

                $this->addFlash(
                    'success',
                    'initiative.flash.edit'
                );

            }

            $em->persist($initiative);

            $em->flush();

        }

        return $this->render('User/edit.html.twig', array(
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a form to delete a initiative entity.
     *
     * @param Initiative $initiative The initiative entity
     *
     * @return FormInterface
     */
    private function createDeleteForm(Initiative $initiative)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_initiative_delete', array("id" => $initiative->getId(), "slug" => $initiative->getSlug())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * Deletes a initiative entity.
     *
     * @Route("/initiative/{id}/{slug}/delete", requirements={"id" = "\d+"}, name="user_initiative_delete", methods={"DELETE"})
     * @param Request $request
     * @param Initiative $initiative
     * @return RedirectResponse
     */
    public function deleteAction(Request $request, Initiative $initiative)
    {

        $this->denyAccessUnlessGranted("delete", $initiative);

        $form = $this->createDeleteForm($initiative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($initiative);
            $em->flush();

            $this->addFlash(
                'success',
                'initiative.flash.delete'
            );

        }

        return $this->redirectToRoute('user_initiative_index');
    }


    /**
     * Displays a form to edit an avatar.
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.avatar.label", attributes={"translate": true})
     *
     * @Route("/avatar/edit", name="user_avatar_edit", methods={"GET","POST"})
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function avatarEditAction(Request $request)
    {
        return $this->render('User/avatar.html.twig', array(
        ));
    }


    /**
     * @Route("/avatar/save", name="user_avatar_save",  methods={"POST"}, options={"expose"=true} )
     * @Security("is_granted('ROLE_USER')")
     * @param Request $request
     * @return void
     * @throws NonUniqueResultException
     */

    public function avatarSaveAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $file = AvatarManager::validateFilename( $request->get('filename') );
        if ( $file['type'] !== 'invalid' ) {
            $data =  AvatarManager::validateImagedata( $_POST['imgdata'], $file['type'] );
        } else {
            die( 'error_file_type' );
        }
        // if filename is correct
        if ( $file['name'] !== 'invalid' ) {
            if ( $file['type'] === 'png' ) {
                // checking that validated image data is not empty
                if ( $data !== false ) {
                    $data = base64_decode( $data );
                    $dir =  $this->getParameter('avatar_image_path');
                    if ( is_dir( $dir ) && is_writable( $dir ) ) {
                        $em = $this->getDoctrine()->getManager();
                        $image = $em->getRepository(User::class)->getUserAvatarImage($user);

                        if (is_null($image)) {
                            $image = new UserImage();

                            $image->setUser($user);
                        }elseif ($image->getPath() != 'default.png'){
                            @unlink($dir.$image->getPath() );
                        }
                        $image->setImageType(UserImage::USER_IMAGE_TYPE_AVATAR);
                        $image->setContentType('image/png');
                        $fn = tempnam($dir,$user->getId().'_');
                        if($fn === false)
                            die( 'error_file_data');
                        if(rename($fn,$fn.'.png') === false)
                            die( 'error_file_data');
                        $fn .= '.png';
                        $image->setPath(basename($fn));
                        file_put_contents( $fn, $data );

                        $em->persist($image);
                        $em->flush();

                        die( 'saved' );
                    } else {
                        die( 'error_uploads_dir' );
                    }
                } else {
                    die( 'error_file_data' );
                }
            } else {
                die( 'error_file_type' );
            }
        } else {
            die('error_file_type');
        }
    }

    /**
     * @Route("/{id}/avatar", name="user_profile_avatar", options={"expose"=true}, requirements={"id" = "\d+"})
     * @param User $user
     * @return Response
     * @throws NonUniqueResultException
     */

    public function avatarAction(User $user)
    {

        $em = $this->getDoctrine()->getManager();

        $image = $em->getRepository(User::class)->getUserAvatarImage($user);

        if (is_null($image)) {
            $image = new UserImage();

            $image->setUser($user);
            $image->setImageType(UserImage::USER_IMAGE_TYPE_AVATAR);
            $image->setContentType('image/png');

            $filename = 'default.png';
            $image->setPath($filename);

            $em->persist($image);
            $em->flush();
        }

        $path = $this->get('kernel')->getRootDir() . '/../web/assets/img/avatar/' . $image->getPath();

        $response = new BinaryFileResponse($path);

        $response->headers->set('Content-Type', $image->getContentType());

        return $response;

    }


    /**
     * handle favourites
     *
     * @Route("/favourite/{id}", options={"expose"=true}, methods={"GET"}, requirements={"id" = "\d+"}, name="user_favourite")
     * @param Request $request
     * @param Initiative $initiative
     * @return Response
     */
    public function favouriteInitiativeAction(Request $request, Initiative $initiative)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        try {

            $favourite = $em->getRepository(Favourite::class)->getFavouriteByUserAndInitiative($this->getUser(), $initiative);

            if (!is_null($favourite)) {

                $em->remove($favourite);
                $em->flush();

                return $this->createApiResponse([
                    'success' => true,
                    'next' => 'add',
                    'message' => 'Initistive dropped from favourites',
                ]);

            } else {

                $favourite = new Favourite();
                $favourite->setUser($this->getUser());
                $favourite->setType(FavouriteEnum::TYPE_INITIATIVE);
                $favourite->setInitiative($initiative);
                $em->persist($favourite);
                $em->flush();

                return $this->createApiResponse([
                    'success' => true,
                    'next' => 'drop',
                    'message' => 'Initistive added to favourites',
                ]);

            }

        } catch (NonUniqueResultException $e) {

            return $this->createApiResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

        }

    }

    /**
     * handle friends
     *
     * @Route("/friend/{id}", options={"expose"=true}, methods={"GET"}, requirements={"id" = "\d+"}, name="user_friend")
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function favouriteFriendAction(Request $request, User $user)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        try {

            $friend = $em->getRepository(Favourite::class)->getFriendByUser($this->getUser(), $user);

            if (!is_null($friend)) {

                $em->remove($friend);
                $em->flush();

                return $this->createApiResponse([
                    'success' => true,
                    'next' => 'add',
                    'message' => 'User dropped from friends list',
                ]);

            } else {

                $friend = new Favourite();
                $friend->setUser($this->getUser());
                $friend->setType(FavouriteEnum::TYPE_USER);
                $friend->setFriend($user);
                $em->persist($friend);
                $em->flush();

                return $this->createApiResponse([
                    'success' => true,
                    'next' => 'drop',
                    'message' => 'user added to friends list',
                ]);

            }

        } catch (NonUniqueResultException $e) {

            return $this->createApiResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

        }

    }

    /**
     * handle friends
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.friend.label", attributes={"translate": true})
     *
     * @Route("/friends", methods={"GET"}, name="user_friends")
     * @param Request $request
     * @return Response
     */
    public function friendsAction(Request $request)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        $friends = $em->getRepository(User::class)->getFriendsByUser($this->getUser());
        return $this->render('User/friends.html.twig', array(

            'user' => $this->getUser(),
            "friends" => $friends,
        ));
    }

    /**
     * handle favourites
     *
     * @Breadcrumb("breadcrumb.profile.label", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.profile.favourite.label", attributes={"translate": true})
     *
     * @Route("/favourites", methods={"GET"}, name="user_favourites")
     * @param Request $request
     * @return Response
     */
    public function favouritesAction(Request $request)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $em = $this->getDoctrine()->getManager();

        $rep = $em->getRepository(Initiative::class);
        return $this->render('User/favourites.html.twig', array(

            'user' => $this->getUser(),
            "initiativesActive" => $rep->getFavouritesByUser($this->getUser(), InitiativeEnum::STATE_ACTIVE),
            "initiativesFinished" => $rep->getFavouritesByUser($this->getUser(), InitiativeEnum::STATE_FINISHED),
        ));
    }

}
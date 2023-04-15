<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Annotation\PageAnnotation as Page;
use AppBundle\Controller\BaseController;
use JMS\Serializer\SerializerInterface;
use AppBundle\Entity\Category;
use AppBundle\Form\CommentAdminForm;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Initiative;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;

// @Security("is_granted('ROLE_MODERATOR')")
/**
 * Category controller.
 *
 * @Breadcrumb("breadcrumb.admin.label", attributes={"translate": true})
 * @Breadcrumb("breadcrumb.admin.comment.label", route={"name"="admin_comment_index"}, attributes={"translate": true})
 * @Route("/admin/comment")
 */
class CommentAdminController extends BaseController
{

    
    /**
     * Lists all comment entities.
     * @Page("page.admin.comments", attributes={"translate": true})
     * @Route("/", name="admin_comment_index")
     * #[HttpMethod("GET")]
     */
    public function indexAction(Request $request)
    {

        $em = $this->managerRegistry->getManager();
        return $this->render('Admin/Comment/index.html.twig', array(
        ));
    }
    /**
     * Search all comment entities.
     * @Route("/search", name="admin_comment_search",methods={"POST"}, options={"expose"=true})
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
        $idx = $order[0]['column'];
        if($columns[$idx]['data'] == "createdBy.username"){
            $orderBy = "u.username";
        }elseif($columns[$idx]['data'] == "initiative.title"){
            $orderBy = "i.title";
        }elseif($columns[$idx]['data'] == "initiative.category.name"){
            $orderBy = "ca.name";
        }else{
            $orderBy = "c.".$columns[$idx]['data'];
        }
        $orderDir = $order[0]['dir'];
        $comments = $em->getRepository(Comment::class)->search($search['value'],$orderBy,$orderDir);

        $output = array();
        $output['draw'] = $draw;
        $output['recordsTotal'] =  $em->getRepository(Comment::class)->countAllComments();
        $output["recordsFiltered"] = count($comments);

        $output['items'] = array_splice($comments,$start,$length);
        $response = $this->createApiResponse($output, 200);

        return $response;

    }
    /**
     * Displays a form to edit an existing comment entity
     *
     * @Page("page.admin.comment.edit", attributes={"translate": true})     *
     * @Breadcrumb("breadcrumb.admin.comment.edit.label", attributes={"translate": true})
     * @Route("/{id}/edit", name="admin_comment_edit", options={"expose"=true})
     * #[HttpMethod("GET","POST")]
     * @param Request $request
     * @param Comment $comment
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     */
    public function editAction (Request $request, Comment $comment)
    {
        $deleteForm = $this->createDeleteForm($comment);
        $editForm = $this->createForm(CommentAdminForm::class, $comment);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $comment = $editForm->getData();

            $em = $this->managerRegistry->getManager();
            $em->persist($comment);
            $em->flush();

            $this->addFlash(
                'success',
                'comment.flash.edit'
            );

            return $this->redirectToRoute('admin_comment_edit', array('id' => $comment->getId()));
        }

        $em = $this->managerRegistry->getManager();

        return $this->render('Admin/Comment/edit.html.twig', array(
            'comment' => $comment,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
            'changes' => $comment->getChanges($em->getRepository('Gedmo\Loggable\Entity\LogEntry')),

        ));
    }


    /**
     * Deletes a comment entity.
     *
     * @Route("/{id}/delete", name="admin_comment_delete")
     * #[HttpMethod("DELETE")]
     * @param Request $request
     * @param Comment $comment
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, Comment $comment)
    {
        $form = $this->createDeleteForm($comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->managerRegistry->getManager();
            $em->remove($comment);
            $em->flush();

            $this->addFlash(
                'success',
                'comment.flash.delete'
            );

        }

        return $this->redirectToRoute('admin_comment_index');
    }

    /**
     * Creates a form to delete a comment entity.
     *
     * @param Comment $comment
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(Comment $comment)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_comment_delete', array('id' => $comment->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}


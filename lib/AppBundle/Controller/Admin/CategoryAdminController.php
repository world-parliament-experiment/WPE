<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Annotation\PageAnnotation as Page;
use AppBundle\Entity\Category;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use APY\BreadcrumbTrailBundle\Annotation\Breadcrumb;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

// @Security("is_granted('ROLE_SUPERADMIN')")
/**
 * Category controller.
 *
 * @Breadcrumb("breadcrumb.admin.label", attributes={"translate": true})
 * @Breadcrumb("breadcrumb.admin.category.label", route={"name"="admin_category_index"}, attributes={"translate": true})
 * @Route("/admin/category")
 */
class CategoryAdminController extends AbstractController
{
    /**
     * Lists all category entities.
     * @Page("page.admin.categories", attributes={"translate": true})
     * @Route("/", name="admin_category_index")
     * #[HttpMethod("GET")]
     */
    public function indexAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('Admin/Category/index.html.twig', array(
            'categories' => $categories,
        ));
    }

    /**
     * Creates a new category entity.
     *
     * @Page("page.admin.category.new", attributes={"translate": true})
     * @Breadcrumb("breadcrumb.admin.category.new.label", attributes={"translate": true})
     * @Route("/new", name="admin_category_new")
     * #[HttpMethod("GET","POST")]
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, SluggerInterface $slugger)
    {

        $category = new Category();
        $form = $this->createForm('AppBundle\Form\CategoryForm' ,$category);
        $form->handleRequest($request);

        // dd($form);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setSlug($slugger);
            $category = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            $this->addFlash(
                'success',
                'vote.flash.new'
            );
            return $this->redirectToRoute('admin_category_edit', array('id' => $category->getId()));
        }

        return $this->render('Admin/Category/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }
    /**
     * Displays a form to edit an existing category entity
     *
     * @Page("page.admin.category.edit", attributes={"translate": true})     *
     * @Breadcrumb("breadcrumb.admin.category.edit.label", attributes={"translate": true})
     * @Route("/{id}/edit", name="admin_category_edit")
     * #[HttpMethod("GET","POST")]
     * @param Request $request
     * @param Category $category
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     */
    public function editAction (Request $request, int $id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository(Category::class)->find($id);
        $deleteForm = $this->createDeleteForm($category);
        $editForm = $this->createForm('AppBundle\Form\CategoryForm', $category);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $category = $editForm->getData();

            
            $em->persist($category);
            $em->flush();

            $this->addFlash(
                'success',
                'category.flash.edit'
            );

            return $this->redirectToRoute('admin_category_edit', array('id' => $category->getId()));
        }

        return $this->render('Admin/Category/edit.html.twig', array(
            'category' => $category,
            'form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a category entity.
     *
     * @Route("/{id}/delete", name="admin_category_delete")
     * #[HttpMethod("DELETE")]
     * @param Request $request
     * @param Category $category
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, int $id)
    {
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository(Category::class)->find($id);
        $form = $this->createDeleteForm($category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $em->remove($category);
            $em->flush();

            $this->addFlash(
                'success',
                'category.flash.delete'
            );

        }

        return $this->redirectToRoute('admin_category_index');
    }

    /**
     * Creates a form to delete a category entity.
     *
     * @param Category $category
     *
     * @return \Symfony\Component\Form\FormInterface The form
     */
    private function createDeleteForm(Category $category)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_category_delete', ['id' => $category->getId()]))
            ->setMethod('DELETE')
            ->getForm();
    }
}


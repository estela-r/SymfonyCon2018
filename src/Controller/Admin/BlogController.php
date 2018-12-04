<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes. However, in your real Symfony application you should use any of the
 * existing bundles that let you generate ready-to-use backends without effort.
 *
 * See http://knpbundles.com/keyword/admin
 *
 * @Route("/admin/post")
 * @IsGranted("ROLE_ADMIN")
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BlogController extends AbstractController
{
    /**
     * Lists all Post entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_post_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     *
     * @Route("/", methods={"GET"}, name="admin_index")
     * @Route("/", methods={"GET"}, name="admin_post_index")
     */
    public function index(PostRepository $posts): Response
    {
        $authorPosts = $posts->findBy(['author' => $this->getUser()], ['publishedAt' => 'DESC']);

        return $this->render('admin/blog/index.html.twig', ['posts' => $authorPosts]);
    }

    /**
     * Creates a new Post entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="admin_post_new")
     *
     * NOTE: the Method annotation is optional, but it's a recommended practice
     * to constraint the HTTP methods each controller responds to (by default
     * it responds to all methods).
     */
    public function new(Request $request): Response
    {
        $post = $this->constructPost();

        $form = $this->createForm(PostType::class, $post)
                     ->add('saveAndCreateNew', SubmitType::class);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->returnInvalidFormResponse($post, $form);
        }

        return $this->submitValidNewPost($post, $form);
    }

    /**
     * Finds and displays a Post entity.
     *
     * @Route("/{id<\d+>}", methods={"GET"}, name="admin_post_show")
     */
    public function show(Post $post): Response
    {
        $this->denyAccessUnlessGranted('show', $post, 'Posts can only be shown to their authors.');

        return $this->render('admin/blog/show.html.twig', [
            'post' => $post,
        ]);
    }

    /**
     * Displays a form to edit an existing Post entity.
     *
     * @Route("/{id<\d+>}/edit",methods={"GET", "POST"}, name="admin_post_edit")
     * @IsGranted("edit", subject="post", message="Posts can only be edited by their authors.")
     */
    public function edit(Request $request, Post $post): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->modifyPost($post);
        }

        return $this->returnUnsuccessfulEditView($post, $form);
    }

    /**
     * Deletes a Post entity.
     *
     * @Route("/{id}/delete", methods={"POST"}, name="admin_post_delete")
     * @IsGranted("delete", subject="post")
     */
    public function delete(Request $request, Post $post): Response
    {
        if (!$this->isCsrfTokenValid('delete', $request->request->get('token'))) {
            return $this->redirectToRoute('admin_post_index');
        }

        $post->getTags()->clear();

        $this->persistPostDeletion($post);

        $this->addFlash('success', 'post.deleted_successfully');

        return $this->redirectToRoute('admin_post_index');
    }

    /**
     * @return Post
     * @throws \LogicException
     */
    protected function constructPost(): Post
    {
        $post = new Post();
        $post->setAuthor($this->getUser());

        return $post;
    }

    /**
     * @param $post
     * @throws \LogicException
     */
    protected function persistPost($post): void
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($post);
        $em->flush();
    }

    /**
     * @param $form
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectUser($form): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if ($form->get('saveAndCreateNew')
                 ->isClicked()) {
            return $this->redirectToRoute('admin_post_new');
        }

        return $this->redirectToRoute('admin_post_index');
    }

    /**
     * @param $post
     * @param $form
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \LogicException
     */
    protected function submitValidNewPost($post, $form): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $post->setSlug(Slugger::slugify($post->getTitle()));

        $this->persistPost($post);

        $this->addFlash('success', 'post.created_successfully');

        return $this->redirectUser($form);
    }

    /**
     * @param Post $post
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \LogicException
     */
    protected function modifyPost(Post $post): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $post->setSlug(Slugger::slugify($post->getTitle()));
        $this->getDoctrine()->getManager()->flush();

        $this->addFlash('success', 'post.updated_successfully');

        return $this->redirectToRoute('admin_post_edit', ['id' => $post->getId()]);
    }

    /**
     * @param Post $post
     * @throws \LogicException
     */
    protected function persistPostDeletion(Post $post): void
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($post);
        $em->flush();
    }

    /**
     * @param $post
     * @param $form
     * @return Response
     * @throws \LogicException
     */
    protected function returnInvalidFormResponse($post, $form): Response
    {
        return $this->render('admin/blog/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Post $post
     * @param      $form
     * @return Response
     * @throws \LogicException
     */
    protected function returnUnsuccessfulEditView(Post $post, $form): Response
    {
        return $this->render('admin/blog/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }
}

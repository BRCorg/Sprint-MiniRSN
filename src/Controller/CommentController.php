<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/', name: 'app_comment_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();
        $comments = $commentRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('comment/index.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/new/{postId}', name: 'app_comment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, int $postId): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($postId);
        
        if (!$post) {
            throw $this->createNotFoundException('Post non trouvé');
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setUser($this->getUser());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès !');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/quick-add/{postId}', name: 'app_comment_quick_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function quickAdd(Request $request, EntityManagerInterface $entityManager, int $postId): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($postId);
        
        if (!$post) {
            throw $this->createNotFoundException('Post non trouvé');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('comment_quick_add', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        $commentText = $request->request->get('comment_text');
        
        if (empty($commentText) || strlen(trim($commentText)) < 3) {
            $this->addFlash('error', 'Le commentaire doit contenir au moins 3 caractères.');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        if (strlen($commentText) > 1000) {
            $this->addFlash('error', 'Le commentaire ne peut pas dépasser 1000 caractères.');
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setUser($this->getUser());
        $comment->setText($commentText);

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès !');
        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
    }

    #[Route('/{id}', name: 'app_comment_show', methods: ['GET'])]
    public function show(Comment $comment): Response
    {
        return $this->render('comment/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut modifier ce commentaire
        if ($comment->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres commentaires.');
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire modifié avec succès !');
            return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut supprimer ce commentaire
        if ($comment->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->get('_token'))) {
            $postId = $comment->getPost()->getId();
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire supprimé avec succès !');
            return $this->redirectToRoute('post_show', ['id' => $postId]);
        }

        $this->addFlash('error', 'Erreur lors de la suppression du commentaire.');
        return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
    }
}

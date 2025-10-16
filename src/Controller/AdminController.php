<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Post;
use App\Entity\Comment;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    // CRUD Users
    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/users/{id}/edit', name: 'app_admin_user_edit')]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        // Add form handling logic here
        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete')]
    public function deleteUser(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();
        
        $this->addFlash('success', 'User deleted successfully');
        return $this->redirectToRoute('app_admin_users');
    }

    // Moderation
    #[Route('/posts', name: 'app_admin_posts')]
    public function posts(PostRepository $postRepository): Response
    {
        return $this->render('admin/posts.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_admin_post_delete')]
    public function deletePost(Post $post, EntityManagerInterface $em): Response
    {
        $em->remove($post);
        $em->flush();
        
        $this->addFlash('success', 'Post deleted successfully');
        return $this->redirectToRoute('app_admin_posts');
    }

    #[Route('/comments', name: 'app_admin_comments')]
    public function comments(CommentRepository $commentRepository): Response
    {
        return $this->render('admin/comments.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_comment_delete')]
    public function deleteComment(Comment $comment, EntityManagerInterface $em): Response
    {
        $em->remove($comment);
        $em->flush();
        
        $this->addFlash('success', 'Comment deleted successfully');
        return $this->redirectToRoute('app_admin_comments');
    }
}

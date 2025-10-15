<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PostRepository $postRepository): Response
    {
        // Rediriger vers login si non connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer tous les posts
        $posts = $postRepository->findAll();

        return $this->render('home/index.html.twig', [
            'posts' => $posts,
        ]);
    }
}

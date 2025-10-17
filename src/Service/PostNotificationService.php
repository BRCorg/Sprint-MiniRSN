<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PostNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UserRepository $userRepository,
        private string $adminEmail = 'admin@minirsn.local'
    ) {
    }

    /**
     * Envoie une notification par email à tous les utilisateurs lorsqu'un nouveau post est créé
     */
    public function notifyNewPost(Post $post): void
    {
        // Récupérer TOUS les utilisateurs (ROLE_USER et ROLE_ADMIN)
        $allUsers = $this->userRepository->findAll();

        $recipients = [];
        foreach ($allUsers as $user) {
            // Ne pas envoyer d'email à l'auteur du post
            if ($user->getEmail() && $user->getId() !== $post->getUser()->getId()) {
                $recipients[] = $user->getEmail();
            }
        }

        // Si aucun destinataire trouvé, ne rien faire
        if (empty($recipients)) {
            return;
        }

        // Créer le contenu HTML de l'email
        $htmlContent = $this->twig->render('emails/new_post_notification.html.twig', [
            'post' => $post,
            'author' => $post->getUser(),
        ]);

        // Créer et envoyer l'email pour chaque destinataire
        foreach ($recipients as $recipient) {
            $email = (new Email())
                ->from('noreply@minirsn.local')
                ->to($recipient)
                ->subject('Nouveau post créé sur MiniRSN')
                ->html($htmlContent);

            $this->mailer->send($email);
        }
    }
}

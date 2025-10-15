# GUIDE D'IMPLÉMENTATION COMPLET - Mini RSN

Ce guide vous explique **étape par étape** comment implémenter toutes les fonctionnalités du réseau social.

---

## TABLE DES MATIÈRES

1. [Inscription et Connexion](#1-inscription-et-connexion)
2. [Publier des messages avec image](#2-publier-des-messages-avec-image)
3. [Voir les publications des autres](#3-voir-les-publications-des-autres)
4. [Commenter les publications](#4-commenter-les-publications)
5. [Modifier ses publications](#5-modifier-ses-publications)
6. [Supprimer ses publications](#6-supprimer-ses-publications)
7. [Notifications email (Mailhog)](#7-notifications-email-mailhog)
8. [Administration - CRUD Utilisateurs](#8-administration-crud-utilisateurs)
9. [Modération des messages et commentaires](#9-modération-des-messages-et-commentaires)

---

## 1. INSCRIPTION ET CONNEXION

### ✅ Déjà fait !

Les commandes `make:user`, `make:auth` et `make:registration-form` ont déjà créé :
- Entity `User`
- Système de connexion (`/login`)
- Système d'inscription (`/register`)

### Vérification rapide

**Routes disponibles** :
```bash
docker exec symfony_php php bin/console debug:router | grep -E "(login|register)"
```

Vous devriez voir :
- `app_login` → `/login`
- `app_register` → `/register`
- `app_logout` → `/logout`

---

## 2. PUBLIER DES MESSAGES AVEC IMAGE

### Étape 1 : Créer le formulaire de publication

```bash
docker exec symfony_php php bin/console make:form PostType
```

**Fichier : `src/Form/PostType.php`**
```php
<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Quoi de neuf ?'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le message ne peut pas être vide',
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optionnel)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
```

---

### Étape 2 : Créer le contrôleur PostController

```bash
docker exec symfony_php php bin/console make:controller PostController
```

**Fichier : `src/Controller/PostController.php`**
```php
<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/post')]
class PostController extends AbstractController
{
    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload d'image
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('posts_images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }

                $post->setImage($newFilename);
            }

            // Associer l'utilisateur connecté
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été publié !');

            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        // Récupérer tous les posts, triés du plus récent au plus ancien
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }
}
```

---

### Étape 3 : Configurer le répertoire d'upload

**Fichier : `config/services.yaml`**

Ajouter après `parameters:` :
```yaml
parameters:
    posts_images_directory: '%kernel.project_dir%/public/uploads/posts'
```

---

### Étape 4 : Créer le répertoire d'upload

```bash
docker exec symfony_php mkdir -p public/uploads/posts
docker exec symfony_php chmod 777 public/uploads/posts
```

---

### Étape 5 : Créer les templates

**Fichier : `templates/post/new.html.twig`**
```twig
{% extends 'base.html.twig' %}

{% block title %}Nouvelle publication{% endblock %}

{% block body %}
<div class="container mt-5">
    <h1>Nouvelle publication</h1>

    {{ form_start(form) }}
        {{ form_row(form.content) }}
        {{ form_row(form.imageFile) }}

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Publier
        </button>
        <a href="{{ path('app_post_index') }}" class="btn btn-secondary">Annuler</a>
    {{ form_end(form) }}
</div>
{% endblock %}
```

**Fichier : `templates/post/index.html.twig`**
```twig
{% extends 'base.html.twig' %}

{% block title %}Fil d'actualité{% endblock %}

{% block body %}
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Fil d'actualité</h1>
        {% if is_granted('ROLE_USER') %}
            <a href="{{ path('app_post_new') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle publication
            </a>
        {% endif %}
    </div>

    {% for message in app.flashes('success') %}
        <div class="alert alert-success">{{ message }}</div>
    {% endfor %}

    {% if posts is empty %}
        <p class="text-muted">Aucune publication pour le moment.</p>
    {% else %}
        {% for post in posts %}
            <div class="card mb-3">
                <div class="card-header">
                    <strong>{{ post.user.email }}</strong>
                    <small class="text-muted">• {{ post.createdAt|date('d/m/Y à H:i') }}</small>
                </div>
                <div class="card-body">
                    <p class="card-text">{{ post.content }}</p>

                    {% if post.image %}
                        <img src="{{ asset('uploads/posts/' ~ post.image) }}"
                             alt="Image du post"
                             class="img-fluid rounded">
                    {% endif %}
                </div>
                <div class="card-footer text-muted">
                    <small>{{ post.comments|length }} commentaire(s)</small>

                    {% if is_granted('ROLE_USER') and app.user == post.user %}
                        <div class="float-end">
                            <a href="{{ path('app_post_edit', {'id': post.id}) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <form method="post" action="{{ path('app_post_delete', {'id': post.id}) }}" style="display:inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette publication ?');">
                                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ post.id) }}">
                                <button class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </div>
                    {% endif %}
                </div>
            </div>
        {% endfor %}
    {% endif %}
</div>
{% endblock %}
```

---

## 3. VOIR LES PUBLICATIONS DES AUTRES

### ✅ Déjà fait dans l'étape 2 !

La méthode `index()` du `PostController` récupère tous les posts et les affiche dans `post/index.html.twig`.

### Ajouter une page de détail (optionnel)

**Dans `PostController.php`**, ajouter :
```php
#[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
public function show(Post $post): Response
{
    return $this->render('post/show.html.twig', [
        'post' => $post,
    ]);
}
```

**Fichier : `templates/post/show.html.twig`**
```twig
{% extends 'base.html.twig' %}

{% block title %}Publication{% endblock %}

{% block body %}
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <strong>{{ post.user.email }}</strong>
            <small class="text-muted">• {{ post.createdAt|date('d/m/Y à H:i') }}</small>
        </div>
        <div class="card-body">
            <p class="card-text">{{ post.content }}</p>

            {% if post.image %}
                <img src="{{ asset('uploads/posts/' ~ post.image) }}"
                     alt="Image du post"
                     class="img-fluid rounded">
            {% endif %}
        </div>
    </div>

    <a href="{{ path('app_post_index') }}" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>
{% endblock %}
```

---

## 4. COMMENTER LES PUBLICATIONS

### Étape 1 : Créer le formulaire de commentaire

```bash
docker exec symfony_php php bin/console make:form CommentType
```

**Fichier : `src/Form/CommentType.php`**
```php
<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Votre commentaire...'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le commentaire ne peut pas être vide',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
```

---

### Étape 2 : Créer le contrôleur CommentController

```bash
docker exec symfony_php php bin/console make:controller CommentController
```

**Fichier : `src/Controller/CommentController.php`**
```php
<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
#[IsGranted('ROLE_USER')]
class CommentController extends AbstractController
{
    #[Route('/post/{id}/new', name: 'app_comment_new', methods: ['POST'])]
    public function new(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPost($post);
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté !');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire du commentaire ou admin
        if ($this->getUser() !== $comment->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $postId = $comment->getPost()->getId();
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire supprimé');
            return $this->redirectToRoute('app_post_show', ['id' => $postId]);
        }

        return $this->redirectToRoute('app_post_index');
    }
}
```

---

### Étape 3 : Modifier le template de détail pour afficher les commentaires

**Fichier : `templates/post/show.html.twig`** (version complète)
```twig
{% extends 'base.html.twig' %}

{% block title %}Publication{% endblock %}

{% block body %}
<div class="container mt-5">
    {% for message in app.flashes('success') %}
        <div class="alert alert-success">{{ message }}</div>
    {% endfor %}

    {# Publication #}
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ post.user.email }}</strong>
            <small class="text-muted">• {{ post.createdAt|date('d/m/Y à H:i') }}</small>
        </div>
        <div class="card-body">
            <p class="card-text">{{ post.content }}</p>

            {% if post.image %}
                <img src="{{ asset('uploads/posts/' ~ post.image) }}"
                     alt="Image du post"
                     class="img-fluid rounded">
            {% endif %}
        </div>
        <div class="card-footer">
            <small class="text-muted">{{ post.comments|length }} commentaire(s)</small>
        </div>
    </div>

    {# Commentaires #}
    <h3>Commentaires</h3>

    {% if is_granted('ROLE_USER') %}
        <div class="card mb-3">
            <div class="card-body">
                {{ form_start(commentForm, {'action': path('app_comment_new', {'id': post.id})}) }}
                    {{ form_row(commentForm.text) }}
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-comment"></i> Commenter
                    </button>
                {{ form_end(commentForm) }}
            </div>
        </div>
    {% endif %}

    {% if post.comments is empty %}
        <p class="text-muted">Aucun commentaire pour le moment.</p>
    {% else %}
        {% for comment in post.comments %}
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>{{ comment.user.email }}</strong>
                            <small class="text-muted">• {{ comment.createdAt|date('d/m/Y à H:i') }}</small>
                        </div>
                        {% if is_granted('ROLE_USER') and (app.user == comment.user or is_granted('ROLE_ADMIN')) %}
                            <form method="post" action="{{ path('app_comment_delete', {'id': comment.id}) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce commentaire ?');">
                                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ comment.id) }}">
                                <button class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        {% endif %}
                    </div>
                    <p class="mt-2 mb-0">{{ comment.text }}</p>
                </div>
            </div>
        {% endfor %}
    {% endif %}

    <a href="{{ path('app_post_index') }}" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>
{% endblock %}
```

---

### Étape 4 : Modifier le contrôleur PostController pour passer le formulaire

**Dans `src/Controller/PostController.php`**, modifier la méthode `show()` :
```php
use App\Entity\Comment;
use App\Form\CommentType;

#[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
public function show(Post $post): Response
{
    $comment = new Comment();
    $commentForm = $this->createForm(CommentType::class, $comment);

    return $this->render('post/show.html.twig', [
        'post' => $post,
        'commentForm' => $commentForm,
    ]);
}
```

---

## 5. MODIFIER SES PUBLICATIONS

### Dans `PostController.php`, ajouter :

```php
#[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_USER')]
public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    // Vérifier que l'utilisateur est propriétaire du post
    if ($this->getUser() !== $post->getUser()) {
        throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres publications');
    }

    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Gestion de l'upload d'image
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            // Supprimer l'ancienne image si elle existe
            if ($post->getImage()) {
                $oldImagePath = $this->getParameter('posts_images_directory').'/'.$post->getImage();
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('posts_images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
            }

            $post->setImage($newFilename);
        }

        $post->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'Publication modifiée avec succès !');

        return $this->redirectToRoute('app_post_index');
    }

    return $this->render('post/edit.html.twig', [
        'post' => $post,
        'form' => $form,
    ]);
}
```

---

### Template `templates/post/edit.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Modifier la publication{% endblock %}

{% block body %}
<div class="container mt-5">
    <h1>Modifier la publication</h1>

    {% for message in app.flashes('error') %}
        <div class="alert alert-danger">{{ message }}</div>
    {% endfor %}

    {{ form_start(form) }}
        {{ form_row(form.content) }}

        {% if post.image %}
            <div class="mb-3">
                <label>Image actuelle :</label><br>
                <img src="{{ asset('uploads/posts/' ~ post.image) }}"
                     alt="Image actuelle"
                     class="img-thumbnail"
                     style="max-width: 300px;">
            </div>
        {% endif %}

        {{ form_row(form.imageFile, {'label': 'Nouvelle image (optionnel)'}) }}

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <a href="{{ path('app_post_index') }}" class="btn btn-secondary">Annuler</a>
    {{ form_end(form) }}
</div>
{% endblock %}
```

---

## 6. SUPPRIMER SES PUBLICATIONS

### Dans `PostController.php`, ajouter :

```php
#[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
{
    // Vérifier que l'utilisateur est propriétaire du post ou admin
    if ($this->getUser() !== $post->getUser() && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException();
    }

    if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
        // Supprimer l'image si elle existe
        if ($post->getImage()) {
            $imagePath = $this->getParameter('posts_images_directory').'/'.$post->getImage();
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Publication supprimée avec succès');
    }

    return $this->redirectToRoute('app_post_index');
}
```

**Note** : Le bouton de suppression est déjà dans le template `post/index.html.twig` créé précédemment.

---

## 7. NOTIFICATIONS EMAIL (MAILHOG)

### Étape 1 : Créer un service de notification

**Fichier : `src/Service/NotificationService.php`**
```php
<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Post;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function notifyNewPost(Post $post): void
    {
        // Récupérer tous les utilisateurs (sauf l'auteur)
        // Pour simplifier, on envoie juste à l'auteur pour démonstration

        $email = (new Email())
            ->from('noreply@minirsn.com')
            ->to($post->getUser()->getEmail())
            ->subject('Votre publication a été créée')
            ->html(sprintf(
                '<h1>Publication créée</h1><p>Votre message a été publié avec succès !</p><p>%s</p>',
                nl2br($post->getContent())
            ));

        $this->mailer->send($email);
    }

    public function notifyNewComment(Comment $comment): void
    {
        $post = $comment->getPost();
        $postAuthor = $post->getUser();

        // Ne pas notifier si l'auteur du commentaire est le même que l'auteur du post
        if ($comment->getUser() === $postAuthor) {
            return;
        }

        $email = (new Email())
            ->from('noreply@minirsn.com')
            ->to($postAuthor->getEmail())
            ->subject('Nouveau commentaire sur votre publication')
            ->html(sprintf(
                '<h1>Nouveau commentaire</h1>
                <p><strong>%s</strong> a commenté votre publication :</p>
                <blockquote>%s</blockquote>
                <p>Votre publication :</p>
                <blockquote>%s</blockquote>',
                $comment->getUser()->getEmail(),
                nl2br($comment->getText()),
                nl2br($post->getContent())
            ));

        $this->mailer->send($email);
    }
}
```

---

### Étape 2 : Utiliser le service dans les contrôleurs

**Dans `PostController.php`**, modifier la méthode `new()` :
```php
use App\Service\NotificationService;

#[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_USER')]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    SluggerInterface $slugger,
    NotificationService $notificationService  // ⬅️ Ajouter
): Response
{
    $post = new Post();
    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // ... code existant ...

        $entityManager->persist($post);
        $entityManager->flush();

        // Envoyer la notification ⬅️ Ajouter
        $notificationService->notifyNewPost($post);

        $this->addFlash('success', 'Votre message a été publié !');

        return $this->redirectToRoute('app_post_index');
    }

    // ... reste du code ...
}
```

---

**Dans `CommentController.php`**, modifier la méthode `new()` :
```php
use App\Service\NotificationService;

#[Route('/post/{id}/new', name: 'app_comment_new', methods: ['POST'])]
public function new(
    Request $request,
    Post $post,
    EntityManagerInterface $entityManager,
    NotificationService $notificationService  // ⬅️ Ajouter
): Response
{
    $comment = new Comment();
    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $comment->setPost($post);
        $comment->setUser($this->getUser());
        $comment->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($comment);
        $entityManager->flush();

        // Envoyer la notification ⬅️ Ajouter
        $notificationService->notifyNewComment($comment);

        $this->addFlash('success', 'Commentaire ajouté !');
    }

    return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
}
```

---

### Étape 3 : Tester les emails

1. Publier un message
2. Aller sur **http://localhost:8025** (MailHog)
3. Vous verrez l'email intercepté !

---

## 8. ADMINISTRATION - CRUD UTILISATEURS

### Étape 1 : Ajouter le rôle ROLE_ADMIN

**Modifier `src/Entity/User.php`** pour ajouter une méthode helper :
```php
public function isAdmin(): bool
{
    return in_array('ROLE_ADMIN', $this->roles);
}
```

---

### Étape 2 : Créer manuellement un admin

**Via console** :
```bash
docker exec symfony_php php bin/console doctrine:query:sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@minirsn.com'"
```

**Ou créer un utilisateur admin lors de l'inscription** puis modifier en BDD via phpMyAdmin :
```json
["ROLE_ADMIN"]
```

---

### Étape 3 : Créer le contrôleur Admin

```bash
docker exec symfony_php php bin/console make:controller Admin/UserController
```

**Fichier : `src/Controller/Admin/UserController.php`**
```php
<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    #[Route('/{id}/toggle-admin', name: 'app_admin_user_toggle_admin', methods: ['POST'])]
    public function toggleAdmin(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-admin'.$user->getId(), $request->request->get('_token'))) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles)) {
                // Retirer ROLE_ADMIN
                $user->setRoles(array_diff($roles, ['ROLE_ADMIN']));
                $this->addFlash('success', 'Droits admin retirés');
            } else {
                // Ajouter ROLE_ADMIN
                $roles[] = 'ROLE_ADMIN';
                $user->setRoles($roles);
                $this->addFlash('success', 'Droits admin ajoutés');
            }

            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
```

---

### Étape 4 : Créer le template admin

**Fichier : `templates/admin/user/index.html.twig`**
```twig
{% extends 'base.html.twig' %}

{% block title %}Administration - Utilisateurs{% endblock %}

{% block body %}
<div class="container mt-5">
    <h1>Gestion des utilisateurs</h1>

    {% for message in app.flashes('success') %}
        <div class="alert alert-success">{{ message }}</div>
    {% endfor %}

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Rôles</th>
                <th>Inscrit le</th>
                <th>Posts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        {% for user in users %}
            <tr>
                <td>{{ user.id }}</td>
                <td>{{ user.email }}</td>
                <td>
                    {% if 'ROLE_ADMIN' in user.roles %}
                        <span class="badge bg-danger">Admin</span>
                    {% else %}
                        <span class="badge bg-secondary">User</span>
                    {% endif %}
                </td>
                <td>{{ user.createdAt|date('d/m/Y') }}</td>
                <td>{{ user.posts|length }}</td>
                <td>
                    <form method="post" action="{{ path('app_admin_user_toggle_admin', {'id': user.id}) }}" style="display:inline;">
                        <input type="hidden" name="_token" value="{{ csrf_token('toggle-admin' ~ user.id) }}">
                        <button class="btn btn-sm btn-warning">
                            {% if 'ROLE_ADMIN' in user.roles %}
                                Retirer admin
                            {% else %}
                                Promouvoir admin
                            {% endif %}
                        </button>
                    </form>

                    {% if app.user != user %}
                        <form method="post" action="{{ path('app_admin_user_delete', {'id': user.id}) }}" style="display:inline;" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                            <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ user.id) }}">
                            <button class="btn btn-sm btn-danger">Supprimer</button>
                        </form>
                    {% endif %}
                </td>
            </tr>
        {% endfor %}
        </tbody>
    </table>

    <a href="{{ path('app_post_index') }}" class="btn btn-secondary">Retour</a>
</div>
{% endblock %}
```

---

## 9. MODÉRATION DES MESSAGES ET COMMENTAIRES

### Étape 1 : Créer le contrôleur de modération

```bash
docker exec symfony_php php bin/console make:controller Admin/ModerationController
```

**Fichier : `src/Controller/Admin/ModerationController.php`**
```php
<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/moderation')]
#[IsGranted('ROLE_ADMIN')]
class ModerationController extends AbstractController
{
    #[Route('/', name: 'app_admin_moderation_index', methods: ['GET'])]
    public function index(PostRepository $postRepository, CommentRepository $commentRepository): Response
    {
        return $this->render('admin/moderation/index.html.twig', [
            'posts' => $postRepository->findBy([], ['createdAt' => 'DESC'], 20),
            'comments' => $commentRepository->findBy([], ['createdAt' => 'DESC'], 20),
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_admin_moderation_delete_post', methods: ['POST'])]
    public function deletePost(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            // Supprimer l'image si elle existe
            if ($post->getImage()) {
                $imagePath = $this->getParameter('posts_images_directory').'/'.$post->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash('success', 'Publication supprimée');
        }

        return $this->redirectToRoute('app_admin_moderation_index');
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_moderation_delete_comment', methods: ['POST'])]
    public function deleteComment(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire supprimé');
        }

        return $this->redirectToRoute('app_admin_moderation_index');
    }
}
```

---

### Étape 2 : Créer le template de modération

**Fichier : `templates/admin/moderation/index.html.twig`**
```twig
{% extends 'base.html.twig' %}

{% block title %}Modération{% endblock %}

{% block body %}
<div class="container mt-5">
    <h1>Modération des contenus</h1>

    {% for message in app.flashes('success') %}
        <div class="alert alert-success">{{ message }}</div>
    {% endfor %}

    <ul class="nav nav-tabs mb-4" id="moderationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button">
                Publications ({{ posts|length }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button">
                Commentaires ({{ comments|length }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="moderationTabsContent">
        {# Tab Publications #}
        <div class="tab-pane fade show active" id="posts" role="tabpanel">
            <h2>Publications récentes</h2>

            {% if posts is empty %}
                <p class="text-muted">Aucune publication</p>
            {% else %}
                {% for post in posts %}
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>{{ post.user.email }}</strong>
                            <small class="text-muted">• {{ post.createdAt|date('d/m/Y à H:i') }}</small>
                        </div>
                        <div class="card-body">
                            <p>{{ post.content|slice(0, 200) }}{% if post.content|length > 200 %}...{% endif %}</p>

                            {% if post.image %}
                                <img src="{{ asset('uploads/posts/' ~ post.image) }}"
                                     alt="Image"
                                     class="img-thumbnail"
                                     style="max-width: 200px;">
                            {% endif %}
                        </div>
                        <div class="card-footer">
                            <a href="{{ path('app_post_show', {'id': post.id}) }}" class="btn btn-sm btn-info">
                                Voir
                            </a>
                            <form method="post" action="{{ path('app_admin_moderation_delete_post', {'id': post.id}) }}" style="display:inline;" onsubmit="return confirm('Supprimer cette publication ?');">
                                <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ post.id) }}">
                                <button class="btn btn-sm btn-danger">Supprimer</button>
                            </form>
                        </div>
                    </div>
                {% endfor %}
            {% endif %}
        </div>

        {# Tab Commentaires #}
        <div class="tab-pane fade" id="comments" role="tabpanel">
            <h2>Commentaires récents</h2>

            {% if comments is empty %}
                <p class="text-muted">Aucun commentaire</p>
            {% else %}
                {% for comment in comments %}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ comment.user.email }}</strong>
                                    <small class="text-muted">• {{ comment.createdAt|date('d/m/Y à H:i') }}</small>
                                </div>
                                <div>
                                    <a href="{{ path('app_post_show', {'id': comment.post.id}) }}" class="btn btn-sm btn-info">
                                        Voir la publication
                                    </a>
                                    <form method="post" action="{{ path('app_admin_moderation_delete_comment', {'id': comment.id}) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce commentaire ?');">
                                        <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ comment.id) }}">
                                        <button class="btn btn-sm btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                            <p class="mt-2">{{ comment.text }}</p>
                            <small class="text-muted">
                                Sur la publication : "{{ comment.post.content|slice(0, 50) }}..."
                            </small>
                        </div>
                    </div>
                {% endfor %}
            {% endif %}
        </div>
    </div>

    <a href="{{ path('app_post_index') }}" class="btn btn-secondary mt-3">Retour</a>
</div>
{% endblock %}
```

---

## 10. MENU DE NAVIGATION

### Modifier `templates/base.html.twig`

```twig
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>{% block title %}Mini RSN{% endblock %}</title>
        <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 128 128%22><text y=%221.2em%22 font-size=%2296%22>⚫️</text></svg>">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        {% block stylesheets %}{% endblock %}
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ path('app_post_index') }}">
                    <i class="fas fa-share-alt"></i> Mini RSN
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ path('app_post_index') }}">
                                <i class="fas fa-home"></i> Accueil
                            </a>
                        </li>
                        {% if is_granted('ROLE_USER') %}
                            <li class="nav-item">
                                <a class="nav-link" href="{{ path('app_post_new') }}">
                                    <i class="fas fa-plus"></i> Nouvelle publication
                                </a>
                            </li>
                        {% endif %}
                        {% if is_granted('ROLE_ADMIN') %}
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-shield-alt"></i> Administration
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ path('app_admin_user_index') }}">
                                            <i class="fas fa-users"></i> Utilisateurs
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ path('app_admin_moderation_index') }}">
                                            <i class="fas fa-flag"></i> Modération
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        {% endif %}
                    </ul>
                    <ul class="navbar-nav">
                        {% if is_granted('ROLE_USER') %}
                            <li class="nav-item">
                                <span class="navbar-text me-3">
                                    <i class="fas fa-user"></i> {{ app.user.email }}
                                </span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ path('app_logout') }}">
                                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                                </a>
                            </li>
                        {% else %}
                            <li class="nav-item">
                                <a class="nav-link" href="{{ path('app_login') }}">
                                    <i class="fas fa-sign-in-alt"></i> Connexion
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ path('app_register') }}">
                                    <i class="fas fa-user-plus"></i> Inscription
                                </a>
                            </li>
                        {% endif %}
                    </ul>
                </div>
            </div>
        </nav>

        {% block body %}{% endblock %}

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        {% block javascripts %}{% endblock %}
    </body>
</html>
```

---

## 11. RÉCAPITULATIF DES COMMANDES

### Créer les formulaires
```bash
docker exec symfony_php php bin/console make:form PostType
docker exec symfony_php php bin/console make:form CommentType
```

### Créer les contrôleurs
```bash
docker exec symfony_php php bin/console make:controller PostController
docker exec symfony_php php bin/console make:controller CommentController
docker exec symfony_php php bin/console make:controller Admin/UserController
docker exec symfony_php php bin/console make:controller Admin/ModerationController
```

### Créer le service de notification
```bash
# Créer manuellement : src/Service/NotificationService.php
```

### Créer le répertoire d'upload
```bash
docker exec symfony_php mkdir -p public/uploads/posts
docker exec symfony_php chmod 777 public/uploads/posts
```

### Créer un admin
```bash
# D'abord s'inscrire avec email admin@minirsn.com
# Puis :
docker exec symfony_php php bin/console doctrine:query:sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@minirsn.com'"
```

---

## 12. CHECKLIST FINALE

- [ ] Inscription et connexion fonctionnent
- [ ] Publier un message avec image
- [ ] Voir le fil d'actualité
- [ ] Voir le détail d'une publication
- [ ] Commenter une publication
- [ ] Modifier SA publication
- [ ] Supprimer SA publication
- [ ] Recevoir un email lors d'un nouveau post (vérifier Mailhog)
- [ ] Recevoir un email lors d'un commentaire (vérifier Mailhog)
- [ ] Admin : voir la liste des utilisateurs
- [ ] Admin : promouvoir/rétrograder admin
- [ ] Admin : supprimer un utilisateur
- [ ] Admin : supprimer n'importe quel post
- [ ] Admin : supprimer n'importe quel commentaire

---

## 13. ROUTES PRINCIPALES

| Route | URL | Description |
|-------|-----|-------------|
| `app_register` | `/register` | Inscription |
| `app_login` | `/login` | Connexion |
| `app_logout` | `/logout` | Déconnexion |
| `app_post_index` | `/post/` | Fil d'actualité |
| `app_post_new` | `/post/new` | Nouvelle publication |
| `app_post_show` | `/post/{id}` | Détail publication |
| `app_post_edit` | `/post/{id}/edit` | Modifier publication |
| `app_post_delete` | `/post/{id}/delete` | Supprimer publication |
| `app_comment_new` | `/comment/post/{id}/new` | Nouveau commentaire |
| `app_comment_delete` | `/comment/{id}/delete` | Supprimer commentaire |
| `app_admin_user_index` | `/admin/user/` | Gestion utilisateurs |
| `app_admin_moderation_index` | `/admin/moderation/` | Modération |

---

## 14. AMÉLIORATIONS POSSIBLES

- Pagination des posts
- Like/Dislike sur les posts
- Recherche de posts
- Filtres par utilisateur
- Avatar utilisateur
- Messages privés
- Notifications en temps réel
- API REST
- Tests unitaires et fonctionnels

---

**🎉 Félicitations ! Votre réseau social est complet !**

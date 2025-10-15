# GUIDE DÉTAILLÉ AVEC EXPLICATIONS COMPLÈTES - Mini RSN

Ce guide explique **TOUT** en détail : pourquoi on fait comme ça, comment ça fonctionne, et ce que fait chaque ligne de code.

---

## TABLE DES MATIÈRES

1. [Comprendre l'architecture Symfony](#1-comprendre-larchitecture-symfony)
2. [Publier des messages - Explications détaillées](#2-publier-des-messages---explications-détaillées)
3. [Upload d'images - Comment ça marche](#3-upload-dimages---comment-ça-marche)
4. [Relations Doctrine - Comprendre les liens](#4-relations-doctrine---comprendre-les-liens)
5. [Sécurité - IsGranted et vérifications](#5-sécurité---isgranted-et-vérifications)
6. [Formulaires Symfony - Le cycle complet](#6-formulaires-symfony---le-cycle-complet)
7. [Notifications Email - Architecture complète](#7-notifications-email---architecture-complète)
8. [Administration - Rôles et permissions](#8-administration---rôles-et-permissions)
9. [Templates Twig - Syntaxe et logique](#9-templates-twig---syntaxe-et-logique)

---

## 1. COMPRENDRE L'ARCHITECTURE SYMFONY

### Le pattern MVC (Modèle-Vue-Contrôleur)

```
Utilisateur
    ↓
    📍 Route (/post/new)
    ↓
    🎮 Contrôleur (PostController::new)
    ↓
    📊 Modèle (Entity Post)
    ↓
    💾 Base de données
    ↓
    🎨 Vue (Twig template)
    ↓
Réponse HTML
```

### Pourquoi cette architecture ?

- **Séparation des responsabilités** : Chaque partie a un rôle précis
- **Maintenabilité** : Facile de modifier une partie sans toucher aux autres
- **Testabilité** : On peut tester chaque couche indépendamment

---

## 2. PUBLIER DES MESSAGES - EXPLICATIONS DÉTAILLÉES

### Étape 1 : Le Formulaire (PostType)

#### Pourquoi créer un FormType ?

Symfony utilise des **classes de formulaire** plutôt que du HTML brut pour :
- **Validation automatique** des données
- **Protection CSRF** (empêche les attaques)
- **Réutilisabilité** du formulaire
- **Typage fort** (on sait exactement quelles données on attend)

#### Code expliqué ligne par ligne

```php
<?php

namespace App\Form;

use App\Entity\Post;  // L'entité liée au formulaire
use Symfony\Component\Form\AbstractType;  // Classe de base pour tous les formulaires
use Symfony\Component\Form\Extension\Core\Type\FileType;  // Type pour les fichiers
use Symfony\Component\Form\Extension\Core\Type\TextareaType;  // Type pour zone de texte
use Symfony\Component\Form\FormBuilderInterface;  // Interface pour construire le formulaire
use Symfony\Component\OptionsResolver\OptionsResolver;  // Pour configurer les options
use Symfony\Component\Validator\Constraints\File;  // Contrainte de validation pour fichiers
use Symfony\Component\Validator\Constraints\NotBlank;  // Contrainte "champ obligatoire"

class PostType extends AbstractType
{
    // Méthode principale : construit le formulaire
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ========== Champ "content" ==========
            ->add('content', TextareaType::class, [
                // "label" : le texte affiché au-dessus du champ
                'label' => 'Votre message',

                // "attr" : attributs HTML du champ
                'attr' => [
                    'rows' => 4,  // Hauteur de la zone de texte (4 lignes)
                    'placeholder' => 'Quoi de neuf ?'  // Texte indicatif
                ],

                // "constraints" : règles de validation
                'constraints' => [
                    new NotBlank([
                        // Message d'erreur si le champ est vide
                        'message' => 'Le message ne peut pas être vide',
                    ]),
                ],
            ])

            // ========== Champ "imageFile" ==========
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optionnel)',

                // ⚠️ IMPORTANT : "mapped" => false
                // Signifie : ce champ ne correspond PAS à une propriété de l'entité Post
                // Pourquoi ? Car dans Post, on a "image" (string) pas "imageFile" (objet)
                // On va traiter ce fichier manuellement dans le contrôleur
                'mapped' => false,

                // Ce champ est optionnel
                'required' => false,

                'constraints' => [
                    new File([
                        // Taille maximale : 2 Mo
                        'maxSize' => '2M',

                        // Types MIME autorisés (types de fichiers)
                        'mimeTypes' => [
                            'image/jpeg',  // .jpg, .jpeg
                            'image/png',   // .png
                            'image/gif',   // .gif
                        ],

                        // Message d'erreur si mauvais type
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    ])
                ],
            ])
        ;
    }

    // Configure les options par défaut du formulaire
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // "data_class" : indique que ce formulaire est lié à l'entité Post
            // Symfony va automatiquement mapper les champs aux propriétés de Post
            'data_class' => Post::class,
        ]);
    }
}
```

#### Concepts clés à retenir

**1. Mapped vs Non-Mapped**
```php
// ✅ Mapped (par défaut)
'content' -> correspond à $post->content

// ❌ Non-mapped (mapped => false)
'imageFile' -> ne correspond à rien dans Post
              on le traite manuellement
```

**2. Types de champs Symfony**
- `TextType` : champ texte court (`<input type="text">`)
- `TextareaType` : zone de texte multi-lignes (`<textarea>`)
- `FileType` : upload de fichier (`<input type="file">`)
- `EmailType` : email avec validation
- `PasswordType` : mot de passe masqué
- etc.

**3. Contraintes de validation**
```php
NotBlank()      // Ne peut pas être vide
Length()        // Longueur min/max
Email()         // Format email valide
File()          // Validation de fichier
Choice()        // Doit être dans une liste
```

---

### Étape 2 : Le Contrôleur (PostController)

#### Code complet avec explications

```php
<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;  // Pour sauvegarder en BDD
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;  // Représente la requête HTTP
use Symfony\Component\HttpFoundation\Response;  // Représente la réponse HTTP
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;  // Protection par rôle
use Symfony\Component\String\Slugger\SluggerInterface;  // Pour nettoyer les noms de fichiers

// Route de base pour toutes les méthodes de ce contrôleur
#[Route('/post')]
class PostController extends AbstractController
{
    // ========================================
    // MÉTHODE : Créer un nouveau post
    // ========================================

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    // ⬆️ Route : /post/new
    // ⬆️ Nom de la route : app_post_new (pour générer l'URL avec path())
    // ⬆️ Méthodes HTTP acceptées : GET (afficher le formulaire) et POST (soumettre)

    #[IsGranted('ROLE_USER')]
    // ⬆️ Sécurité : seuls les utilisateurs connectés peuvent accéder
    // Si non connecté -> redirection vers /login

    public function new(
        Request $request,                    // Injection de la requête HTTP
        EntityManagerInterface $entityManager, // Injection du gestionnaire BDD
        SluggerInterface $slugger             // Injection du slugger
    ): Response
    {
        // ========== 1. Préparation ==========

        // Créer une nouvelle instance vide de Post
        $post = new Post();

        // Créer le formulaire lié à $post
        $form = $this->createForm(PostType::class, $post);
        // ⬆️ Symfony va automatiquement remplir $post avec les données du formulaire

        // ========== 2. Traitement de la requête ==========

        // Récupérer les données de la requête et les mettre dans le formulaire
        $form->handleRequest($request);
        // ⬆️ Si c'est une requête POST, Symfony remplit le formulaire
        //    Si c'est une requête GET, le formulaire reste vide

        // ========== 3. Validation et sauvegarde ==========

        // Vérifier si le formulaire a été soumis ET est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // ⬆️ isSubmitted() : true si la requête est POST
            // ⬆️ isValid() : true si toutes les contraintes sont respectées

            // ========== 3a. Gestion de l'upload d'image ==========

            // Récupérer le fichier uploadé (peut être null)
            $imageFile = $form->get('imageFile')->getData();
            // ⬆️ getData() : récupère la valeur du champ

            if ($imageFile) {
                // Le fichier existe, on va le traiter

                // --- Étape 1 : Récupérer le nom original ---
                $originalFilename = pathinfo(
                    $imageFile->getClientOriginalName(),  // Ex: "mon image.jpg"
                    PATHINFO_FILENAME  // Récupère juste "mon image" (sans extension)
                );

                // --- Étape 2 : Créer un nom "propre" (slugify) ---
                $safeFilename = $slugger->slug($originalFilename);
                // ⬆️ "mon image" devient "mon-image" (enlève espaces, accents, etc.)
                // Pourquoi ? Pour éviter les problèmes avec les URLs et le système de fichiers

                // --- Étape 3 : Créer un nom unique ---
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                // ⬆️ Résultat : "mon-image-64f5a3b2c1d40.jpg"
                // uniqid() : génère un ID unique basé sur l'heure
                // guessExtension() : détecte l'extension (.jpg, .png, etc.)

                // --- Étape 4 : Déplacer le fichier ---
                try {
                    $imageFile->move(
                        // Destination : public/uploads/posts/
                        $this->getParameter('posts_images_directory'),
                        // Nom du fichier
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Si erreur (permissions, disque plein, etc.)
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                    // ⬆️ addFlash : crée un message temporaire affiché une seule fois
                }

                // --- Étape 5 : Enregistrer le nom en BDD ---
                $post->setImage($newFilename);
                // ⬆️ On stocke juste le nom, pas le fichier complet
            }

            // ========== 3b. Compléter les données ==========

            // Associer l'utilisateur connecté au post
            $post->setUser($this->getUser());
            // ⬆️ $this->getUser() : récupère l'utilisateur connecté

            // Définir la date de création
            $post->setCreatedAt(new \DateTimeImmutable());
            // ⬆️ DateTimeImmutable : comme DateTime mais non modifiable (plus sûr)

            // ========== 3c. Sauvegarder en base de données ==========

            // Préparer l'insertion (mais ne l'exécute pas encore)
            $entityManager->persist($post);
            // ⬆️ Doctrine sait maintenant qu'il doit sauvegarder $post

            // Exécuter TOUTES les requêtes SQL en attente
            $entityManager->flush();
            // ⬆️ C'est ICI que l'INSERT SQL est vraiment exécuté

            // Pourquoi persist() puis flush() ?
            // - persist() : "prépare" l'objet
            // - flush() : exécute toutes les requêtes en une seule fois (performance)

            // ========== 3d. Message de succès et redirection ==========

            $this->addFlash('success', 'Votre message a été publié !');

            // Rediriger vers la liste des posts
            return $this->redirectToRoute('app_post_index');
            // ⬆️ Redirection GET (évite la re-soumission du formulaire si F5)
        }

        // ========== 4. Affichage du formulaire ==========

        // Si on arrive ici, c'est que :
        // - Soit la requête est GET (première visite)
        // - Soit le formulaire est invalide

        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form,  // Symfony passe automatiquement le formulaire au template
        ]);
    }

    // ========================================
    // MÉTHODE : Afficher tous les posts
    // ========================================

    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        // ⬆️ Injection du repository Post
        // Le repository = classe qui fait les requêtes SELECT sur la table Post

        // Récupérer tous les posts, triés par date décroissante
        $posts = $postRepository->findBy(
            [],                        // Critères WHERE (vide = tous)
            ['createdAt' => 'DESC']    // Tri ORDER BY created_at DESC
        );

        // Équivalent SQL :
        // SELECT * FROM post ORDER BY created_at DESC

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }
}
```

#### Concepts clés expliqués

**1. Le cycle de vie d'une requête**

```
1. L'utilisateur visite /post/new (GET)
   ↓
2. Symfony route vers PostController::new()
   ↓
3. Le formulaire est créé et affiché (vide)
   ↓
4. L'utilisateur remplit et soumet (POST)
   ↓
5. Symfony route à nouveau vers PostController::new()
   ↓
6. Le formulaire est rempli avec les données POST
   ↓
7. Validation des contraintes
   ↓
8. Si valide : traitement + sauvegarde + redirection
   Si invalide : réaffichage avec erreurs
```

**2. Pourquoi persist() ET flush() ?**

```php
// ❌ MAUVAIS (une requête SQL par objet)
$entityManager->persist($post1);
$entityManager->flush();  // INSERT 1
$entityManager->persist($post2);
$entityManager->flush();  // INSERT 2
$entityManager->persist($post3);
$entityManager->flush();  // INSERT 3

// ✅ BON (une seule transaction)
$entityManager->persist($post1);
$entityManager->persist($post2);
$entityManager->persist($post3);
$entityManager->flush();  // INSERT 1, 2, 3 en une seule fois
```

**3. Pourquoi slugger ?**

```php
// Nom original : "Mon Super Fichier Été 2024!.jpg"

// Sans slugger :
// "Mon Super Fichier Été 2024!.jpg" ❌
// Problèmes : espaces, accents, caractères spéciaux

// Avec slugger :
// "mon-super-fichier-ete-2024-64f5a3b2c1d40.jpg" ✅
// Safe pour : URLs, système de fichiers, toutes plateformes
```

---

## 3. UPLOAD D'IMAGES - COMMENT ÇA MARCHE

### Architecture complète

```
Formulaire HTML
    ↓
<input type="file" name="imageFile">
    ↓
Navigateur envoie le fichier en multipart/form-data
    ↓
Symfony récupère via $form->get('imageFile')->getData()
    ↓
Objet UploadedFile
    ↓
Validation (taille, type MIME)
    ↓
Slugification du nom
    ↓
move() vers public/uploads/posts/
    ↓
Enregistrement du nom en BDD
```

### Configuration du répertoire

**Dans `config/services.yaml` :**

```yaml
parameters:
    # Définir un paramètre global accessible partout
    posts_images_directory: '%kernel.project_dir%/public/uploads/posts'

    # Explication :
    # %kernel.project_dir% = chemin absolu du projet
    # Ex: /var/www/html
    # Résultat final : /var/www/html/public/uploads/posts
```

**Dans le contrôleur :**

```php
$this->getParameter('posts_images_directory')
// ⬆️ Récupère le paramètre défini dans services.yaml
// Avantage : si on change le chemin, un seul endroit à modifier
```

### Créer le répertoire

```bash
docker exec symfony_php mkdir -p public/uploads/posts
# ⬆️ -p : crée les dossiers parents si besoin

docker exec symfony_php chmod 777 public/uploads/posts
# ⬆️ 777 : tous les droits (lecture, écriture, exécution)
# En production, utiliser 755 ou 775 (plus sécurisé)
```

### Affichage dans Twig

```twig
{% if post.image %}
    <img src="{{ asset('uploads/posts/' ~ post.image) }}" alt="Image">
{% endif %}

{# Explication :
   - asset() : génère l'URL complète vers un fichier public
   - 'uploads/posts/' : chemin depuis public/
   - ~ : concaténation en Twig (équivalent de . en PHP)
   - post.image : nom du fichier (ex: "mon-image-64f5a3b2c1d40.jpg")
   - Résultat : "/uploads/posts/mon-image-64f5a3b2c1d40.jpg"
#}
```

### Sécurité et bonnes pratiques

**1. Ne JAMAIS faire confiance au nom du fichier**

```php
// ❌ DANGEREUX
$filename = $imageFile->getClientOriginalName();
$imageFile->move($directory, $filename);
// Un hacker pourrait uploader "../../etc/passwd"

// ✅ SÉCURISÉ
$filename = $slugger->slug($originalFilename).'-'.uniqid().'.'.$extension;
// On contrôle complètement le nom
```

**2. Valider le type MIME**

```php
'mimeTypes' => [
    'image/jpeg',
    'image/png',
    'image/gif',
],
// ⬆️ Vérifie le VRAI type de fichier (pas juste l'extension)
// Un .jpg renommé en .exe sera rejeté
```

**3. Limiter la taille**

```php
'maxSize' => '2M',
// ⬆️ Empêche les uploads de gros fichiers qui :
//    - Ralentissent le serveur
//    - Remplissent le disque
//    - Peuvent être des attaques DoS
```

---

## 4. RELATIONS DOCTRINE - COMPRENDRE LES LIENS

### Les 3 types de relations

```
1. OneToOne (1-1)
   Exemple : User ↔ Profile
   Un utilisateur a UN profil
   Un profil appartient à UN utilisateur

2. ManyToOne / OneToMany (N-1 / 1-N)
   Exemple : Post → User
   Un post appartient à UN utilisateur
   Un utilisateur a PLUSIEURS posts

3. ManyToMany (N-N)
   Exemple : Article ↔ Tag
   Un article a PLUSIEURS tags
   Un tag est sur PLUSIEURS articles
```

### Notre schéma

```
        User
       /    \
      /      \
  (1-N)    (1-N)
    /        \
  Post     Comment
    \        /
     (1-N) /
       \ /
     Comment
```

### Code détaillé : User ↔ Post

**Dans `User.php` :**

```php
/**
 * @var Collection<int, Post>
 */
#[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user', orphanRemoval: true)]
private Collection $posts;

// Explications :
// - OneToMany : un User a PLUSIEURS Posts
// - targetEntity: Post::class : la relation pointe vers l'entité Post
// - mappedBy: 'user' : dans Post, la propriété qui fait le lien est $user
// - orphanRemoval: true : si on supprime un User, ses Posts sont supprimés aussi

public function __construct()
{
    // Collection = comme un tableau, mais géré par Doctrine
    $this->posts = new ArrayCollection();
}

public function getPosts(): Collection
{
    return $this->posts;
    // ⬆️ Retourne tous les posts de cet utilisateur
}

public function addPost(Post $post): static
{
    if (!$this->posts->contains($post)) {
        // ⬆️ Évite les doublons
        $this->posts->add($post);
        $post->setUser($this);
        // ⬆️ IMPORTANT : synchronise les deux côtés de la relation
    }
    return $this;
}

public function removePost(Post $post): static
{
    if ($this->posts->removeElement($post)) {
        // ⬆️ Retire de la collection
        if ($post->getUser() === $this) {
            $post->setUser(null);
            // ⬆️ Casse le lien
        }
    }
    return $this;
}
```

**Dans `Post.php` :**

```php
#[ORM\ManyToOne(inversedBy: 'posts')]
#[ORM\JoinColumn(nullable: false)]
private ?User $user = null;

// Explications :
// - ManyToOne : plusieurs Posts appartiennent à UN User
// - inversedBy: 'posts' : dans User, la propriété inverse est $posts
// - JoinColumn(nullable: false) : un Post DOIT avoir un User
//   → Créé une contrainte NOT NULL en BDD

public function getUser(): ?User
{
    return $this->user;
}

public function setUser(?User $user): static
{
    $this->user = $user;
    return $this;
}
```

### SQL généré

```sql
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    ...
);

CREATE TABLE post (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    user_id INT NOT NULL,  -- Clé étrangère
    ...
    CONSTRAINT FK_post_user FOREIGN KEY (user_id) REFERENCES user(id)
);

-- Si on supprime un User, que se passe-t-il ?
-- orphanRemoval: true → CASCADE DELETE automatique
```

### Utilisation dans le code

```php
// Récupérer tous les posts d'un utilisateur
$user = $userRepository->find(1);
$posts = $user->getPosts();  // Collection de Post

foreach ($posts as $post) {
    echo $post->getContent();
}

// Récupérer l'auteur d'un post
$post = $postRepository->find(1);
$author = $post->getUser();  // Objet User
echo $author->getEmail();
```

### Lazy Loading vs Eager Loading

```php
// ========== Lazy Loading (par défaut) ==========
$user = $userRepository->find(1);
// SQL 1 : SELECT * FROM user WHERE id = 1

$posts = $user->getPosts();
// Pas encore de SQL

foreach ($posts as $post) {
    // SQL 2 : SELECT * FROM post WHERE user_id = 1
    echo $post->getContent();
}

// ========== Eager Loading (optimisé) ==========
$user = $userRepository->createQueryBuilder('u')
    ->leftJoin('u.posts', 'p')
    ->addSelect('p')
    ->where('u.id = :id')
    ->setParameter('id', 1)
    ->getQuery()
    ->getOneOrNullResult();

// SQL : SELECT user.*, post.* FROM user LEFT JOIN post ...
// ⬆️ Une seule requête au lieu de deux (plus performant)
```

---

## 5. SÉCURITÉ - ISGRANTED ET VÉRIFICATIONS

### Les attributs de sécurité

```php
#[IsGranted('ROLE_USER')]
public function new(): Response
{
    // Seuls les utilisateurs avec ROLE_USER peuvent accéder
}

// Équivalent à :
public function new(): Response
{
    if (!$this->isGranted('ROLE_USER')) {
        throw $this->createAccessDeniedException();
    }
}
```

### Hiérarchie des rôles

**Dans `config/packages/security.yaml` :**

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: ROLE_USER
        # ⬆️ Un ADMIN a aussi le rôle USER automatiquement

        ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_USER]
        # ⬆️ Un SUPER_ADMIN a ADMIN et USER
```

**Comment ça marche :**

```php
$user->setRoles(['ROLE_ADMIN']);

$user->getRoles();
// Retourne : ['ROLE_ADMIN', 'ROLE_USER']
// ⬆️ ROLE_USER est automatiquement ajouté grâce à la hiérarchie
```

### Vérifications dans les contrôleurs

```php
// ========== Vérifier un rôle ==========
if ($this->isGranted('ROLE_ADMIN')) {
    // L'utilisateur est admin
}

// ========== Vérifier le propriétaire ==========
if ($this->getUser() !== $post->getUser()) {
    throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres publications');
}

// ========== Vérifier propriétaire OU admin ==========
if ($this->getUser() !== $post->getUser() && !$this->isGranted('ROLE_ADMIN')) {
    throw $this->createAccessDeniedException();
}
// ⬆️ Soit vous êtes le propriétaire, soit vous êtes admin
```

### Protection CSRF (Cross-Site Request Forgery)

**Qu'est-ce que c'est ?**

Imaginez :
1. Vous êtes connecté sur minirsn.com
2. Vous visitez un site malveillant
3. Ce site contient un formulaire caché qui envoie une requête à minirsn.com
4. Votre navigateur envoie automatiquement vos cookies
5. → Le site malveillant peut supprimer vos posts !

**Solution : token CSRF**

```twig
{# Template Twig #}
<form method="post" action="{{ path('app_post_delete', {'id': post.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ post.id) }}">
    {# ⬆️ Token unique généré par Symfony #}
    <button>Supprimer</button>
</form>
```

```php
// Contrôleur
if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
    // ⬆️ Vérifie que le token est valide
    // Si valide : le formulaire vient bien de notre site
    $entityManager->remove($post);
    $entityManager->flush();
}
```

**Comment ça marche :**

```
1. Symfony génère un token aléatoire : "a3f5b2c8d1e9"
2. Le stocke en session : $_SESSION['csrf']['delete1'] = "a3f5b2c8d1e9"
3. L'insère dans le formulaire
4. À la soumission, compare le token reçu avec celui en session
5. Si différents ou absents → attaque CSRF détectée
```

---

## 6. FORMULAIRES SYMFONY - LE CYCLE COMPLET

### Anatomie d'un formulaire

```php
// 1. CRÉATION
$post = new Post();  // Objet vide
$form = $this->createForm(PostType::class, $post);
// ⬆️ Lie le formulaire à l'objet

// 2. TRAITEMENT DE LA REQUÊTE
$form->handleRequest($request);
// ⬆️ Remplit $post avec les données POST

// 3. VALIDATION
if ($form->isSubmitted() && $form->isValid()) {
    // $post contient maintenant les données validées
    $entityManager->persist($post);
    $entityManager->flush();
}

// 4. RENDU
return $this->render('template.html.twig', [
    'form' => $form,
]);
```

### Ce qui se passe dans handleRequest()

```php
$form->handleRequest($request);

// En interne, Symfony fait :
if ($request->isMethod('POST')) {
    // 1. Récupère les données POST
    $data = $request->request->all();

    // 2. Les transforme selon les types de champs
    // Ex: "2024-01-15" → DateTimeImmutable

    // 3. Les injecte dans l'objet
    $post->setContent($data['post']['content']);
    $post->setImage($data['post']['image']);

    // 4. Valide les contraintes
    $validator->validate($post);
}
```

### Transformateurs de données

```php
// Champ DateType
'birthday' => DateType::class

// HTML : <input name="birthday[year]" value="2024">
//        <input name="birthday[month]" value="01">
//        <input name="birthday[day]" value="15">

// Symfony transforme en :
new \DateTime('2024-01-15');

// Et l'inverse pour l'affichage !
```

### Validation en cascade

```php
// Dans PostType
'constraints' => [
    new NotBlank(),  // Vérifié en premier
    new Length(['min' => 10]),  // Vérifié ensuite
]

// Si NotBlank échoue, Length n'est PAS vérifié
// Pourquoi ? Éviter les messages d'erreur redondants
```

### Thèmes de formulaire

```twig
{# Rendu complet par défaut #}
{{ form_row(form.content) }}
{# Génère :
   <div>
       <label>Votre message</label>
       <textarea name="post[content]">...</textarea>
       <span class="error">Erreur...</span>
   </div>
#}

{# Rendu personnalisé #}
{{ form_label(form.content) }}
{{ form_widget(form.content, {'attr': {'class': 'custom-class'}}) }}
{{ form_errors(form.content) }}
```

---

## 7. NOTIFICATIONS EMAIL - ARCHITECTURE COMPLÈTE

### Pourquoi un Service ?

```
❌ MAUVAIS : Email dans le contrôleur
PostController::new()
    → Logique métier (créer post)
    → Logique email (envoyer notification)
    → Mélange de responsabilités

✅ BON : Email dans un service
PostController::new()
    → Logique métier
    → NotificationService::notifyNewPost()
        → Logique email isolée
```

**Avantages :**
- **Réutilisable** : peut servir ailleurs (commandes, API, etc.)
- **Testable** : on peut tester le service indépendamment
- **Maintenable** : si on change le système d'email, un seul endroit à modifier

### Code du service expliqué

```php
<?php

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Post;
use Symfony\Component\Mailer\MailerInterface;  // Interface du mailer Symfony
use Symfony\Component\Mime\Email;  // Classe pour créer un email

class NotificationService
{
    // ========== INJECTION DE DÉPENDANCES ==========

    public function __construct(
        private MailerInterface $mailer,
        // ⬆️ "private" dans le constructeur = création automatique de la propriété
        // Équivalent de :
        // private MailerInterface $mailer;
        // public function __construct(MailerInterface $mailer) {
        //     $this->mailer = $mailer;
        // }
    ) {}
    // ⬆️ Symfony injecte automatiquement l'implémentation du MailerInterface

    // ========== NOTIFICATION NOUVEAU POST ==========

    public function notifyNewPost(Post $post): void
    {
        // Créer un email
        $email = (new Email())
            ->from('noreply@minirsn.com')
            // ⬆️ Expéditeur
            // En production, doit être un email configuré dans MAILER_DSN

            ->to($post->getUser()->getEmail())
            // ⬆️ Destinataire : l'auteur du post
            // En réalité, on voudrait notifier les followers, mais pour la démo...

            ->subject('Votre publication a été créée')
            // ⬆️ Sujet de l'email

            ->html(sprintf(
                '<h1>Publication créée</h1><p>Votre message a été publié avec succès !</p><p>%s</p>',
                nl2br($post->getContent())
                // ⬆️ nl2br() : transforme les sauts de ligne en <br>
                // sprintf() : format de chaîne (comme printf)
            ));

        // Envoyer l'email
        $this->mailer->send($email);
        // ⬆️ En dev : intercepté par Mailhog
        //    En prod : envoyé par SMTP réel
    }

    // ========== NOTIFICATION NOUVEAU COMMENTAIRE ==========

    public function notifyNewComment(Comment $comment): void
    {
        $post = $comment->getPost();
        $postAuthor = $post->getUser();

        // ========== VÉRIFICATION IMPORTANTE ==========

        // Ne pas notifier si l'auteur du commentaire est le même que l'auteur du post
        if ($comment->getUser() === $postAuthor) {
            return;
            // ⬆️ Évite de s'auto-notifier
        }

        $email = (new Email())
            ->from('noreply@minirsn.com')
            ->to($postAuthor->getEmail())
            // ⬆️ On notifie l'auteur du post

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

### Configuration Mailer

**Dans `.env` :**

```env
# Développement : MailHog
MAILER_DSN=smtp://mailhog:1025

# Production : SMTP réel (ex: Gmail)
# MAILER_DSN=gmail+smtp://username:password@default

# Explication du DSN :
# gmail+smtp:// → Transport (Gmail avec SMTP)
# username:password → Identifiants
# @default → Serveur par défaut (smtp.gmail.com:587)
```

### Mailhog : Comment ça marche

```
Application Symfony
    ↓
$mailer->send($email)
    ↓
SMTP localhost:1025
    ↓
Mailhog (conteneur Docker)
    ↓
Stocke l'email en mémoire
    ↓
Interface web http://localhost:8025
    ↓
Vous visualisez l'email
```

**Avantages en développement :**
- Pas besoin de vraie boîte email
- Tous les emails en un seul endroit
- Pas de spam accidentel
- Tester sans connexion Internet

### Amélioration : Templates d'emails

**Au lieu de HTML inline :**

```php
// ❌ HTML dans le code (difficile à maintenir)
->html(sprintf('<h1>...</h1>', $content))
```

**Utiliser Twig :**

```php
// ✅ Template réutilisable
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

$email = (new TemplatedEmail())
    ->from('noreply@minirsn.com')
    ->to($postAuthor->getEmail())
    ->subject('Nouveau commentaire')
    ->htmlTemplate('emails/new_comment.html.twig')
    ->context([
        'comment' => $comment,
        'post' => $post,
    ]);
```

**Template `emails/new_comment.html.twig` :**

```twig
<h1>Nouveau commentaire</h1>

<p><strong>{{ comment.user.email }}</strong> a commenté :</p>

<blockquote>
    {{ comment.text }}
</blockquote>

<p>Votre publication :</p>
<blockquote>
    {{ post.content }}
</blockquote>

<a href="{{ url('app_post_show', {id: post.id}) }}">Voir la publication</a>
```

---

## 8. ADMINISTRATION - RÔLES ET PERMISSIONS

### Ajouter le rôle ROLE_ADMIN

**Méthode 1 : En BDD directement**

```sql
UPDATE user SET roles = '["ROLE_ADMIN"]' WHERE email = 'admin@minirsn.com';

-- Attention : JSON au format exact !
-- ["ROLE_ADMIN"] et non [ROLE_ADMIN] ou ['ROLE_ADMIN']
```

**Méthode 2 : Via console Doctrine**

```bash
docker exec symfony_php php bin/console doctrine:query:sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@minirsn.com'"
```

**Méthode 3 : En PHP (dans un contrôleur ou fixture)**

```php
$user = $userRepository->findOneBy(['email' => 'admin@minirsn.com']);
$user->setRoles(['ROLE_ADMIN']);
$entityManager->flush();
```

### Contrôleur Admin expliqué

```php
#[Route('/admin/user')]
// ⬆️ Toutes les routes de ce contrôleur commencent par /admin/user

#[IsGranted('ROLE_ADMIN')]
// ⬆️ TOUTES les méthodes nécessitent ROLE_ADMIN
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        // Récupérer TOUS les utilisateurs
        $users = $userRepository->findAll();
        // ⬆️ Équivalent SQL : SELECT * FROM user

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    // ⬆️ URL : /admin/user/5/delete
    // ⬆️ {id} est automatiquement passé comme paramètre
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // ⬆️ PARAM CONVERTER : Symfony transforme automatiquement {id} en objet User
        // Il fait un SELECT * FROM user WHERE id = {id}
        // Si introuvable → 404 automatique

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // ⬆️ Vérification CSRF

            $entityManager->remove($user);
            // ⬆️ Marque pour suppression

            $entityManager->flush();
            // ⬆️ Exécute DELETE FROM user WHERE id = ...

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
                // ========== RETIRER ROLE_ADMIN ==========

                // array_diff() : différence entre deux tableaux
                $user->setRoles(array_diff($roles, ['ROLE_ADMIN']));
                // ⬆️ Exemple :
                // $roles = ['ROLE_ADMIN', 'ROLE_USER']
                // array_diff(['ROLE_ADMIN', 'ROLE_USER'], ['ROLE_ADMIN'])
                // → ['ROLE_USER']

                $this->addFlash('success', 'Droits admin retirés');
            } else {
                // ========== AJOUTER ROLE_ADMIN ==========

                $roles[] = 'ROLE_ADMIN';
                // ⬆️ Ajoute à la fin du tableau

                $user->setRoles($roles);
                $this->addFlash('success', 'Droits admin ajoutés');
            }

            $entityManager->flush();
            // ⬆️ UPDATE user SET roles = '...' WHERE id = ...
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
```

### Le Param Converter

```php
// ❌ SANS Param Converter (manuel)
public function delete(Request $request, int $id, UserRepository $userRepository): Response
{
    $user = $userRepository->find($id);

    if (!$user) {
        throw $this->createNotFoundException('Utilisateur introuvable');
    }

    // ...
}

// ✅ AVEC Param Converter (automatique)
public function delete(Request $request, User $user): Response
{
    // $user est déjà chargé
    // Si id invalide → 404 automatique

    // ...
}
```

**Comment Symfony sait faire ça ?**

```
1. URL : /admin/user/5/delete
2. Route : /admin/user/{id}/delete
3. Symfony extrait : id = 5
4. Il voit le paramètre : User $user
5. Il devine : "User avec id = 5"
6. Il fait : $userRepository->find(5)
7. Il injecte l'objet dans le paramètre
```

---

## 9. TEMPLATES TWIG - SYNTAXE ET LOGIQUE

### Les bases de Twig

```twig
{# Ceci est un commentaire (invisible dans le HTML) #}

{# Afficher une variable #}
{{ variable }}

{# Exécuter du code (pas d'affichage) #}
{% set name = 'Jean' %}
{% if condition %}...{% endif %}

{# Filtres (transformations) #}
{{ text|upper }}  {# TEXTE EN MAJUSCULES #}
{{ date|date('d/m/Y') }}  {# 15/01/2024 #}
```

### Héritage de templates

```twig
{# base.html.twig #}
<!DOCTYPE html>
<html>
    <head>
        <title>{% block title %}Mon Site{% endblock %}</title>
    </head>
    <body>
        {% block body %}{% endblock %}
    </body>
</html>

{# post/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Publications{% endblock %}

{% block body %}
    <h1>Liste des publications</h1>
{% endblock %}

{# Résultat HTML :
<!DOCTYPE html>
<html>
    <head>
        <title>Publications</title>
    </head>
    <body>
        <h1>Liste des publications</h1>
    </body>
</html>
#}
```

### Accéder aux propriétés

```twig
{# Ces 3 syntaxes font la MÊME chose : #}
{{ post.content }}
{{ post['content'] }}
{{ attribute(post, 'content') }}

{# En PHP, Twig essaie dans cet ordre :
   1. $post->content (propriété publique)
   2. $post->getContent() (getter)
   3. $post->isContent() (pour les booléens)
   4. $post->hasContent()
   5. Erreur si rien ne marche
#}
```

### Sécurité : Échappement automatique

```twig
{% set dangerousHtml = '<script>alert("XSS")</script>' %}

{# Avec échappement (par défaut) #}
{{ dangerousHtml }}
{# Affiche : &lt;script&gt;alert("XSS")&lt;/script&gt; #}
{# L'utilisateur voit le texte, le script ne s'exécute PAS #}

{# Sans échappement (DANGEREUX !) #}
{{ dangerousHtml|raw }}
{# Affiche : <script>alert("XSS")</script> #}
{# Le script s'exécute ! ⚠️ #}
```

**Règle d'or :** N'utilisez `|raw` QUE si vous êtes CERTAIN que le contenu est sûr.

### Fonctions utiles

```twig
{# path() : génère une URL #}
<a href="{{ path('app_post_show', {id: post.id}) }}">Voir</a>
{# Résultat : <a href="/post/5">Voir</a> #}

{# asset() : lien vers un fichier public #}
<img src="{{ asset('uploads/posts/' ~ post.image) }}">
{# Résultat : <img src="/uploads/posts/image.jpg"> #}

{# is_granted() : vérifier un rôle #}
{% if is_granted('ROLE_ADMIN') %}
    <a href="/admin">Administration</a>
{% endif %}

{# app.user : utilisateur connecté #}
{% if app.user %}
    Bonjour {{ app.user.email }}
{% else %}
    <a href="/login">Connexion</a>
{% endif %}
```

### Boucles

```twig
{% for post in posts %}
    <div>{{ post.content }}</div>
{% else %}
    {# Exécuté si posts est vide #}
    <p>Aucune publication</p>
{% endfor %}

{# Variables spéciales dans les boucles #}
{% for post in posts %}
    {{ loop.index }}     {# 1, 2, 3... #}
    {{ loop.index0 }}    {# 0, 1, 2... #}
    {{ loop.first }}     {# true au premier tour #}
    {{ loop.last }}      {# true au dernier tour #}
    {{ loop.length }}    {# Nombre total d'éléments #}
{% endfor %}
```

### Flash messages

```twig
{# Dans le template #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}

{# Comment ça marche :
   1. Dans le contrôleur : $this->addFlash('success', 'Message')
   2. Le message est stocké en session
   3. Au prochain affichage, Twig le récupère
   4. Après affichage, le message est SUPPRIMÉ automatiquement
   → Un flash message ne s'affiche qu'UNE SEULE FOIS
#}
```

### Filtres personnalisés utiles

```twig
{# upper : MAJUSCULES #}
{{ 'hello'|upper }}  {# HELLO #}

{# lower : minuscules #}
{{ 'HELLO'|lower }}  {# hello #}

{# length : longueur #}
{{ posts|length }}  {# 5 #}

{# slice : découper #}
{{ 'Hello World'|slice(0, 5) }}  {# Hello #}

{# date : formater une date #}
{{ post.createdAt|date('d/m/Y à H:i') }}  {# 15/01/2024 à 14:30 #}

{# default : valeur par défaut si null #}
{{ post.image|default('no-image.jpg') }}

{# join : joindre un tableau #}
{{ ['a', 'b', 'c']|join(', ') }}  {# a, b, c #}

{# nl2br : sauts de ligne en <br> #}
{{ post.content|nl2br }}
```

---

## 10. COMPRENDRE LE FLUX COMPLET

### Exemple concret : "Publier un post"

```
1. L'utilisateur clique sur "Nouvelle publication"
   ↓
2. Navigateur envoie GET /post/new
   ↓
3. Symfony routing : trouve la route app_post_new
   ↓
4. Appelle PostController::new()
   ↓
5. Contrôleur :
   - Crée un objet Post vide
   - Crée le formulaire PostType
   - Rend le template post/new.html.twig
   ↓
6. Twig génère le HTML avec le formulaire
   ↓
7. Navigateur affiche la page
   ↓
8. L'utilisateur remplit et clique "Publier"
   ↓
9. Navigateur envoie POST /post/new avec :
   - post[content] = "Mon message"
   - post[imageFile] = [fichier binaire]
   - _token = "a3f5b2c8d1e9"
   ↓
10. Symfony routing : même route app_post_new
    ↓
11. Appelle PostController::new() (encore)
    ↓
12. $form->handleRequest($request) :
    - Récupère les données POST
    - Remplit $post->content = "Mon message"
    - Récupère le fichier uploadé
    ↓
13. $form->isSubmitted() : true
    $form->isValid() : valide les contraintes
    ↓
14. Traitement de l'image :
    - Slugification du nom
    - Déplacement vers public/uploads/posts/
    - Enregistrement du nom dans $post->image
    ↓
15. $post->setUser($this->getUser())
    ↓
16. $post->setCreatedAt(new DateTimeImmutable())
    ↓
17. $entityManager->persist($post)
    $entityManager->flush()
    ↓
18. Doctrine génère et exécute :
    INSERT INTO post (content, image, user_id, created_at)
    VALUES ('Mon message', 'mon-image-64f5.jpg', 1, '2024-01-15 14:30:00')
    ↓
19. NotificationService::notifyNewPost($post)
    - Crée un email
    - L'envoie via MailHog
    ↓
20. $this->addFlash('success', '...')
    - Stocke en session
    ↓
21. return $this->redirectToRoute('app_post_index')
    - Répond avec HTTP 302 Location: /post/
    ↓
22. Navigateur suit la redirection : GET /post/
    ↓
23. PostController::index()
    - SELECT * FROM post ORDER BY created_at DESC
    - Rend post/index.html.twig
    ↓
24. Template affiche :
    - Liste des posts
    - Flash message "Votre message a été publié !"
    ↓
25. L'utilisateur voit son post publié
```

---

## 11. ERREURS COURANTES ET SOLUTIONS

### "An exception occurred while executing a query: SQLSTATE[23000]"

**Cause :** Violation de contrainte (clé étrangère, unique, etc.)

```php
// Exemple :
$post = new Post();
$post->setContent('Test');
// ❌ OUBLI : $post->setUser($this->getUser())
$entityManager->persist($post);
$entityManager->flush();
// ERREUR : user_id cannot be NULL
```

**Solution :** Vérifier que tous les champs obligatoires sont remplis.

---

### "Argument #1 must be of type User, null given"

**Cause :** Tentative d'accès à une méthode sur null

```php
$post->getUser()->getEmail();
// ❌ Si $post->getUser() retourne null → erreur
```

**Solution :**

```php
// ✅ Vérifier avant
if ($post->getUser()) {
    echo $post->getUser()->getEmail();
}

// ✅ Ou en Twig
{% if post.user %}
    {{ post.user.email }}
{% endif %}
```

---

### "Access Denied"

**Cause :** Tentative d'accès à une route protégée sans les droits

```php
#[IsGranted('ROLE_ADMIN')]
public function admin(): Response
```

**Solutions :**

```php
// 1. Se connecter avec un compte admin
// 2. Retirer IsGranted si pas nécessaire
// 3. Vérifier que l'utilisateur a bien le rôle
$user->setRoles(['ROLE_ADMIN']);
$entityManager->flush();
```

---

### "The file could not be moved"

**Cause :** Problème de permissions sur le dossier

**Solutions :**

```bash
# Créer le dossier
docker exec symfony_php mkdir -p public/uploads/posts

# Donner les droits
docker exec symfony_php chmod 777 public/uploads/posts

# En production, utiliser plutôt :
docker exec symfony_php chmod 775 public/uploads/posts
docker exec symfony_php chown www-data:www-data public/uploads/posts
```

---

## 12. POUR ALLER PLUS LOIN

### Concepts à approfondir

1. **Doctrine avancé**
   - QueryBuilder
   - DQL (Doctrine Query Language)
   - Hydratation
   - Lazy/Eager loading

2. **Sécurité**
   - Voters (permissions fines)
   - Remember me
   - Two-factor authentication

3. **Formulaires avancés**
   - Collection de formulaires
   - Form events
   - Data transformers

4. **Performance**
   - Cache HTTP
   - Cache Doctrine
   - Profiler

5. **Tests**
   - PHPUnit
   - Tests fonctionnels
   - Tests d'intégration

6. **API**
   - API Platform
   - Serialization
   - JWT authentication

---

## CONCLUSION

Vous avez maintenant une compréhension complète de :

✅ L'architecture Symfony (MVC)
✅ Les formulaires et leur cycle de vie
✅ L'upload de fichiers
✅ Les relations Doctrine
✅ La sécurité et les rôles
✅ L'envoi d'emails
✅ Les templates Twig
✅ L'administration

**Prochaines étapes :**

1. Implémenter le code du guide
2. Tester chaque fonctionnalité
3. Lire les messages d'erreur (ils sont très utiles !)
4. Consulter la documentation officielle Symfony
5. Pratiquer, pratiquer, pratiquer !

**Ressources officielles :**

- Documentation Symfony : https://symfony.com/doc
- Doctrine ORM : https://www.doctrine-project.org
- Twig : https://twig.symfony.com

**Bon courage ! 🚀**

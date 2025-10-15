# Guide Complet : Système d'Authentification (Inscription & Connexion)

## Table des matières
1. [Vue d'ensemble du système](#vue-densemble-du-système)
2. [Configuration de base](#configuration-de-base)
3. [Entité User](#entité-user)
4. [Formulaire d'inscription](#formulaire-dinscription)
5. [Contrôleur d'inscription](#contrôleur-dinscription)
6. [Template d'inscription](#template-dinscription)
7. [Système de connexion](#système-de-connexion)
8. [Contrôleur de sécurité](#contrôleur-de-sécurité)
9. [Template de connexion](#template-de-connexion)
10. [Authenticator personnalisé](#authenticator-personnalisé)
11. [Configuration de sécurité](#configuration-de-sécurité)
12. [Page d'accueil](#page-daccueil)
13. [Traductions](#traductions)
14. [Résumé du flux complet](#résumé-du-flux-complet)

---

## Vue d'ensemble du système

### Fonctionnement global

```
┌─────────────────────────────────────────────────────────────────┐
│                     FLUX D'AUTHENTIFICATION                      │
└─────────────────────────────────────────────────────────────────┘

1. Utilisateur non connecté → http://localhost:8000/
   ↓
2. HomeController détecte "pas connecté"
   ↓
3. Redirection automatique → /login
   ↓
4. Affichage du formulaire de connexion
   ↓
5. L'utilisateur peut :
   - Se connecter (s'il a un compte)
   - Cliquer sur "S'inscrire" → /register

┌─────────────────────────────────────────────────────────────────┐
│                    PROCESSUS D'INSCRIPTION                       │
└─────────────────────────────────────────────────────────────────┘

1. Utilisateur sur /register
   ↓
2. Remplit le formulaire (email + mot de passe)
   ↓
3. Soumet le formulaire → RegistrationController
   ↓
4. Validation des données
   ↓
5. Hashage du mot de passe
   ↓
6. Sauvegarde dans la base de données
   ↓
7. Message de succès
   ↓
8. Redirection vers /login

┌─────────────────────────────────────────────────────────────────┐
│                     PROCESSUS DE CONNEXION                       │
└─────────────────────────────────────────────────────────────────┘

1. Utilisateur sur /login
   ↓
2. Remplit email + mot de passe
   ↓
3. Soumet le formulaire → AppCustomAuthAuthenticator
   ↓
4. Vérification du token CSRF
   ↓
5. Recherche de l'utilisateur par email
   ↓
6. Vérification du mot de passe
   ↓
7. Création de la session
   ↓
8. Redirection vers /
   ↓
9. Affichage de la liste des posts
```

---

## Configuration de base

### 1. Fichier `.env` - Variables d'environnement

**Chemin :** `.env`

```env
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=f84b3a93e2481cb7e4203577cdcca8fc
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://root:root@db:3306/minirsn_db?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
```

**Explication :**

- **APP_ENV=dev** : Mode développement (affiche les erreurs détaillées)
- **APP_SECRET** : Clé secrète utilisée pour :
  - Générer les tokens CSRF (protection contre les attaques)
  - Chiffrer les sessions
  - Signer les cookies
  - **IMPORTANT** : Ne JAMAIS partager cette clé !
- **DATABASE_URL** : Connexion à la base de données MySQL dans Docker

**Pourquoi APP_SECRET est crucial ?**
Sans APP_SECRET, les tokens CSRF ne peuvent pas être générés/validés → erreur "Jeton CSRF invalide"

---

### 2. Configuration Framework - CSRF et Sessions

**Chemin :** `config/packages/framework.yaml`

```yaml
framework:
    secret: '%env(APP_SECRET)%'
    csrf_protection: true
    session: true
```

**Explication :**

- **secret** : Utilise la variable APP_SECRET du fichier .env
- **csrf_protection: true** : Active la protection CSRF sur tous les formulaires
- **session: true** : Active le système de sessions (pour garder l'utilisateur connecté)

**Qu'est-ce que le CSRF ?**
CSRF (Cross-Site Request Forgery) = Attaque où un site malveillant essaie de soumettre des formulaires à votre place.

**Comment ça fonctionne ?**
1. Symfony génère un token unique pour chaque formulaire
2. Ce token est ajouté dans un champ caché du formulaire
3. Lors de la soumission, Symfony vérifie que le token est valide
4. Si le token ne correspond pas → erreur "Jeton CSRF invalide"

---

## Entité User

### Qu'est-ce qu'une Entité ?

Une **entité** est une classe PHP qui représente une table dans la base de données.

**Chemin :** `src/Entity/User.php`

```php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    // Relations
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $comments;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    // Getters et Setters...
}
```

**Explication détaillée :**

### Attributs de la classe

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
```
- Dit à Doctrine que cette classe est une entité (= table en BDD)
- Associe un Repository pour faire des requêtes SQL

```php
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
```
- Garantit qu'un email ne peut être utilisé qu'une seule fois
- Empêche les doublons dans la BDD

### Interfaces implémentées

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
```

**UserInterface** : Interface obligatoire pour Symfony Security
- Permet à Symfony de gérer l'authentification
- Oblige à avoir les méthodes : `getRoles()`, `eraseCredentials()`, `getUserIdentifier()`

**PasswordAuthenticatedUserInterface** :
- Indique que l'utilisateur a un mot de passe
- Oblige à avoir la méthode `getPassword()`

### Propriétés

```php
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
private ?int $id = null;
```
- **#[ORM\Id]** : Clé primaire
- **#[ORM\GeneratedValue]** : Auto-incrément
- **private ?int $id** : Type nullable (peut être null avant insertion)

```php
#[ORM\Column(length: 180)]
private ?string $email = null;
```
- Colonne VARCHAR(180) en BDD
- Stocke l'email de l'utilisateur

```php
#[ORM\Column]
private array $roles = [];
```
- Stocke les rôles de l'utilisateur sous forme de tableau JSON en BDD
- Exemple : `["ROLE_USER"]` ou `["ROLE_USER", "ROLE_ADMIN"]`

```php
#[ORM\Column]
private ?string $password = null;
```
- Stocke le mot de passe **hashé** (jamais en clair !)
- Exemple : `$2y$13$xyz...` (algorithme bcrypt)

### Relations

```php
#[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user', orphanRemoval: true)]
private Collection $posts;
```
- **OneToMany** : Un utilisateur peut avoir plusieurs posts
- **mappedBy: 'user'** : La propriété `user` dans l'entité Post fait le lien
- **orphanRemoval: true** : Si on supprime un User, ses posts sont aussi supprimés

**Tableau SQL équivalent :**
```
Table: user
+----+-------------------+----------+------------------------------------------------------+
| id | email             | roles    | password                                             |
+----+-------------------+----------+------------------------------------------------------+
| 1  | test@test.com     | ["ROLE_USER"] | $2y$13$Xyz...                                  |
+----+-------------------+----------+------------------------------------------------------+
```

---

## Formulaire d'inscription

### Qu'est-ce qu'un FormType ?

Un **FormType** est une classe qui définit la structure d'un formulaire :
- Quels champs afficher
- Quelles validations appliquer
- Quel type de champ utiliser (text, email, password, checkbox...)

**Chemin :** `src/Form/RegistrationFormType.php`

```php
<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un mot de passe',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
```

**Explication ligne par ligne :**

### La méthode buildForm()

```php
$builder->add('email')
```
- Ajoute un champ email
- Symfony devine automatiquement que c'est un champ email (grâce à la propriété `$email` dans User)
- Génère un `<input type="email">`

```php
->add('plainPassword', PasswordType::class, [
```
- **plainPassword** : Nom du champ (mot de passe en clair, avant hashage)
- **PasswordType::class** : Type de champ = `<input type="password">`

```php
'mapped' => false,
```
- **TRÈS IMPORTANT** : Ce champ n'est PAS lié à une propriété de l'entité User
- Pourquoi ? Car dans User, on a `$password` (hashé), pas `$plainPassword`
- On récupérera manuellement cette valeur dans le contrôleur pour la hasher

```php
'attr' => ['autocomplete' => 'new-password'],
```
- Attribut HTML pour dire aux navigateurs : "c'est un NOUVEAU mot de passe"
- Évite que le navigateur propose d'anciens mots de passe sauvegardés

```php
'constraints' => [
    new NotBlank([
        'message' => 'Veuillez entrer un mot de passe',
    ]),
```
- **NotBlank** : Le champ ne peut pas être vide
- Si vide → affiche "Veuillez entrer un mot de passe"

```php
    new Length([
        'min' => 6,
        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
        'max' => 4096,
    ]),
```
- **Length** : Contrainte de longueur
- Minimum 6 caractères
- Maximum 4096 (limite de sécurité Symfony)
- `{{ limit }}` sera remplacé par `6` dans le message

### La méthode configureOptions()

```php
$resolver->setDefaults([
    'data_class' => User::class,
]);
```
- Indique que ce formulaire est lié à l'entité User
- Permet à Symfony de remplir automatiquement l'objet User avec les données du formulaire

---

## Contrôleur d'inscription

### Qu'est-ce qu'un Contrôleur ?

Un **contrôleur** est une classe qui :
1. Reçoit les requêtes HTTP (GET, POST...)
2. Traite la logique métier
3. Retourne une réponse (affichage d'une page, redirection...)

**Chemin :** `src/Controller/RegistrationController.php`

```php
<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Connectez-vous maintenant.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
```

**Explication détaillée :**

### L'attribut Route

```php
#[Route('/register', name: 'app_register')]
```
- Définit l'URL : `http://localhost:8000/register`
- Donne un nom à la route : `app_register` (utilisé pour générer des liens)

### Les paramètres de la méthode (Injection de dépendances)

```php
public function register(
    Request $request,
    UserPasswordHasherInterface $userPasswordHasher,
    EntityManagerInterface $entityManager
): Response
```

**Request $request** :
- Contient toutes les infos de la requête HTTP
- POST, GET, cookies, session, etc.

**UserPasswordHasherInterface $userPasswordHasher** :
- Service pour hasher les mots de passe
- Utilise bcrypt par défaut (très sécurisé)

**EntityManagerInterface $entityManager** :
- Gestionnaire de base de données Doctrine
- Permet de sauvegarder, modifier, supprimer des entités

**Injection de dépendances ?**
Symfony injecte automatiquement ces objets. Pas besoin de faire `new Request()` ou `new EntityManager()`.

### Étape 1 : Créer le formulaire

```php
$user = new User();
```
- Crée une nouvelle instance vide de User
- Sera remplie avec les données du formulaire

```php
$form = $this->createForm(RegistrationFormType::class, $user);
```
- Crée le formulaire basé sur RegistrationFormType
- Lie le formulaire à l'objet `$user`

```php
$form->handleRequest($request);
```
- **CRUCIAL** : Traite la requête
- Si le formulaire a été soumis (POST), récupère les données
- Remplit automatiquement l'objet `$user` avec les valeurs

### Étape 2 : Vérifier et traiter le formulaire

```php
if ($form->isSubmitted() && $form->isValid()) {
```
- **isSubmitted()** : Vérifie si le formulaire a été envoyé
- **isValid()** : Vérifie toutes les contraintes de validation

**Que se passe-t-il lors de la validation ?**
1. Vérifie NotBlank → Le mot de passe n'est pas vide ?
2. Vérifie Length → Le mot de passe a au moins 6 caractères ?
3. Vérifie l'email → Format valide ?
4. Vérifie UniqueConstraint → L'email n'existe pas déjà en BDD ?

### Étape 3 : Hasher le mot de passe

```php
$plainPassword = $form->get('plainPassword')->getData();
```
- Récupère le mot de passe en clair du formulaire
- Exemple : "monmotdepasse123"

```php
$user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
```
- **hashPassword()** : Transforme "monmotdepasse123" en "$2y$13$xyz..."
- Définit le mot de passe hashé dans l'objet User

**Pourquoi hasher ?**
- Sécurité ! Si la BDD est piratée, les mots de passe ne sont pas en clair
- Bcrypt ajoute un "salt" unique par mot de passe
- Impossible de "déhasher" (fonction à sens unique)

### Étape 4 : Sauvegarder en base de données

```php
$entityManager->persist($user);
```
- Dit à Doctrine : "Prépare-toi à insérer cet utilisateur"
- Ne fait RIEN en BDD pour l'instant

```php
$entityManager->flush();
```
- **Exécute VRAIMENT** l'insertion en BDD
- Équivalent SQL : `INSERT INTO user (email, password, roles) VALUES (...)`

**Pourquoi persist() + flush() ?**
- Permet de grouper plusieurs opérations
- Flush() exécute tout d'un coup (plus performant)

### Étape 5 : Message flash et redirection

```php
$this->addFlash('success', 'Votre compte a été créé avec succès ! Connectez-vous maintenant.');
```
- Stocke un message dans la session
- Sera affiché UNE SEULE FOIS sur la prochaine page
- Type : 'success' (pour affichage en vert)

```php
return $this->redirectToRoute('app_login');
```
- Redirige vers la route nommée 'app_login'
- Équivalent à : `header('Location: /login')`

### Étape 6 : Afficher le formulaire (si pas soumis)

```php
return $this->render('registration/register.html.twig', [
    'registrationForm' => $form,
]);
```
- Affiche le template Twig
- Passe le formulaire au template (pour l'afficher en HTML)

---

## Template d'inscription

**Chemin :** `templates/registration/register.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Inscription - Mini RSN{% endblock %}

{% block body %}
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    {# Bouton Dark Mode #}
    <button id="darkModeToggle"
            class="fixed top-6 right-6 p-3 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-yellow-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 shadow-lg">
        <svg id="sunIcon" class="w-6 h-6 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <svg id="moonIcon" class="w-6 h-6 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>

    <div class="max-w-md w-full">
        {# En-tête #}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Créer un compte
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Rejoignez Mini RSN aujourd'hui
            </p>
        </div>

        {# Carte du formulaire #}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
            {# Affichage des erreurs globales #}
            {% if form_errors(registrationForm) %}
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-red-800 dark:text-red-200">
                            {{ form_errors(registrationForm) }}
                        </div>
                    </div>
                </div>
            {% endif %}

            {{ form_start(registrationForm, {'attr': {'class': 'space-y-6'}}) }}

                {# Champ Email #}
                <div>
                    {{ form_label(registrationForm.email, 'Adresse email', {
                        'label_attr': {'class': 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2'}
                    }) }}
                    {{ form_widget(registrationForm.email, {
                        'attr': {
                            'class': 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all',
                            'placeholder': 'votre@email.com'
                        }
                    }) }}
                    {% if form_errors(registrationForm.email) %}
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form_errors(registrationForm.email) }}
                        </p>
                    {% endif %}
                </div>

                {# Champ Mot de passe #}
                <div>
                    {{ form_label(registrationForm.plainPassword, 'Mot de passe', {
                        'label_attr': {'class': 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2'}
                    }) }}
                    {{ form_widget(registrationForm.plainPassword, {
                        'attr': {
                            'class': 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all',
                            'placeholder': '••••••••'
                        }
                    }) }}
                    {% if form_errors(registrationForm.plainPassword) %}
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                            {{ form_errors(registrationForm.plainPassword) }}
                        </p>
                    {% endif %}
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Minimum 6 caractères
                    </p>
                </div>

                {# Bouton Submit #}
                <button type="submit"
                        class="w-full py-3 px-6 text-center text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl font-semibold text-lg hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                    S'inscrire
                </button>

            {{ form_end(registrationForm) }}

            {# Lien vers connexion #}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Vous avez déjà un compte ?
                    <a href="{{ path('app_login') }}"
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold">
                        Se connecter
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

{# Script Dark Mode #}
<script>
    function initDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const htmlElement = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            htmlElement.classList.add('dark');
        }

        darkModeToggle.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            if (htmlElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initDarkMode);
</script>
{% endblock %}
```

**Explication des éléments Twig :**

### Héritage de template

```twig
{% extends 'base.html.twig' %}
```
- Hérite du template de base
- Le contenu ira dans les `{% block %}` définis

### Fonctions Twig pour les formulaires

```twig
{{ form_start(registrationForm, {'attr': {'class': 'space-y-6'}}) }}
```
- Génère `<form method="POST" action="/register">`
- Ajoute automatiquement le token CSRF caché
- `{'attr': {'class': 'space-y-6'}}` ajoute une classe CSS

```twig
{{ form_label(registrationForm.email, 'Adresse email', {
    'label_attr': {'class': '...'}
}) }}
```
- Génère `<label for="registration_form_email">Adresse email</label>`
- Ajoute les classes CSS Tailwind

```twig
{{ form_widget(registrationForm.email, {
    'attr': {
        'class': '...',
        'placeholder': 'votre@email.com'
    }
}) }}
```
- Génère `<input type="email" id="registration_form_email" name="registration_form[email]">`
- Ajoute les attributs personnalisés

```twig
{% if form_errors(registrationForm.email) %}
    <p class="...">
        {{ form_errors(registrationForm.email) }}
    </p>
{% endif %}
```
- Affiche les erreurs de validation pour ce champ
- Exemple : "Votre mot de passe doit contenir au moins 6 caractères"

```twig
{{ form_end(registrationForm) }}
```
- Ferme le formulaire `</form>`
- Affiche les champs restants non rendus

### Génération de liens

```twig
<a href="{{ path('app_login') }}">
    Se connecter
</a>
```
- `path('app_login')` génère l'URL de la route nommée 'app_login'
- Résultat : `<a href="/login">Se connecter</a>`

### Script Dark Mode

```javascript
const savedTheme = localStorage.getItem('theme');
```
- Récupère le thème sauvegardé dans localStorage
- Persiste même après fermeture du navigateur

```javascript
htmlElement.classList.add('dark');
```
- Ajoute la classe `dark` au `<html>`
- Tailwind applique automatiquement les styles `dark:...`

---

## Système de connexion

### Qu'est-ce qu'un Authenticator ?

Un **Authenticator** est une classe qui gère le processus d'authentification :
1. Récupérer les identifiants (email + mot de passe)
2. Vérifier que l'utilisateur existe
3. Vérifier le mot de passe
4. Créer la session
5. Rediriger après succès

**Chemin :** `src/Security/AppCustomAuthAuthenticator.php`

```php
<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
```

**Explication détaillée :**

### La méthode authenticate()

```php
public function authenticate(Request $request): Passport
```
- Méthode appelée automatiquement lors de la soumission du formulaire de login
- Doit retourner un objet `Passport` (= "passeport d'authentification")

```php
$email = $request->getPayload()->getString('email');
```
- Récupère l'email du formulaire POST
- Équivalent à `$_POST['email']` en PHP classique

```php
$request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
```
- Sauvegarde l'email dans la session
- Permet de réafficher l'email en cas d'erreur

```php
return new Passport(
    new UserBadge($email),
    new PasswordCredentials($request->getPayload()->getString('password')),
    [
        new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
    ]
);
```

**Passport** : Contient toutes les infos pour authentifier l'utilisateur

**UserBadge($email)** :
- Dit à Symfony : "Trouve l'utilisateur avec cet email"
- Symfony cherche automatiquement dans User via UserRepository

**PasswordCredentials** :
- Contient le mot de passe soumis
- Symfony le compare automatiquement avec le hash en BDD

**CsrfTokenBadge** :
- Vérifie le token CSRF
- Si invalide → erreur "Jeton CSRF invalide"

### La méthode onAuthenticationSuccess()

```php
public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
```
- Appelée si l'authentification réussit
- Décide où rediriger l'utilisateur

```php
if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
    return new RedirectResponse($targetPath);
}
```
- Si l'utilisateur essayait d'accéder à une page protégée avant de se connecter
- Redirige vers cette page
- Exemple : Essaie d'aller sur `/profile` → redirigé vers login → après login → retour sur `/profile`

```php
return new RedirectResponse($this->urlGenerator->generate('app_home'));
```
- Sinon, redirige vers la page d'accueil

### La méthode getLoginUrl()

```php
protected function getLoginUrl(Request $request): string
{
    return $this->urlGenerator->generate(self::LOGIN_ROUTE);
}
```
- Définit l'URL de la page de login
- Utilisée pour rediriger en cas d'erreur

---

## Contrôleur de sécurité

**Chemin :** `src/Controller/SecurityController.php`

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Géré automatiquement par Symfony
    }
}
```

**Explication :**

### login()

```php
$error = $authenticationUtils->getLastAuthenticationError();
```
- Récupère la dernière erreur d'authentification
- Exemple : "Email ou mot de passe incorrect"

```php
$lastUsername = $authenticationUtils->getLastUsername();
```
- Récupère le dernier email saisi
- Permet de pré-remplir le champ email en cas d'erreur

### logout()

```php
public function logout(): void
{
    // Géré automatiquement par Symfony
}
```
- Cette méthode ne sera JAMAIS exécutée
- Symfony intercepte `/logout` et détruit la session automatiquement
- Configuré dans `security.yaml`

---

## Template de connexion

**Chemin :** `templates/security/login.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Connexion - Mini RSN{% endblock %}

{% block body %}
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    {# Bouton Dark Mode #}
    <button id="darkModeToggle" class="...">
        {# SVG icons... #}
    </button>

    <div class="max-w-md w-full">
        {# En-tête #}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Connexion
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Connectez-vous à votre compte
            </p>
        </div>

        {# Carte du formulaire #}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
            {# Message de succès (après inscription) #}
            {% for message in app.flashes('success') %}
                <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-green-800 dark:text-green-200">
                            {{ message }}
                        </div>
                    </div>
                </div>
            {% endfor %}

            {# Message d'erreur #}
            {% if error %}
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-red-800 dark:text-red-200">
                            {{ error.messageKey|trans(error.messageData, 'security') }}
                        </div>
                    </div>
                </div>
            {% endif %}

            <form method="post" class="space-y-6">
                {# Champ Email #}
                <div>
                    <label for="inputEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Adresse email
                    </label>
                    <input type="email"
                           value="{{ last_username }}"
                           name="email"
                           id="inputEmail"
                           autocomplete="email"
                           required
                           autofocus
                           placeholder="votre@email.com"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all">
                </div>

                {# Champ Mot de passe #}
                <div>
                    <label for="inputPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mot de passe
                    </label>
                    <input type="password"
                           name="password"
                           id="inputPassword"
                           autocomplete="current-password"
                           required
                           placeholder="••••••••"
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all">
                </div>

                {# Token CSRF #}
                <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">

                {# Bouton Submit #}
                <button type="submit"
                        class="w-full py-3 px-6 text-center text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl font-semibold text-lg hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Se connecter
                </button>
            </form>

            {# Lien vers inscription #}
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Vous n'avez pas de compte ?
                    <a href="{{ path('app_register') }}"
                       class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold">
                        S'inscrire
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

{# Script Dark Mode #}
<script>
    function initDarkMode() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const htmlElement = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            htmlElement.classList.add('dark');
        }

        darkModeToggle.addEventListener('click', () => {
            htmlElement.classList.toggle('dark');
            if (htmlElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initDarkMode);
</script>
{% endblock %}
```

**Explication des éléments clés :**

### Messages flash

```twig
{% for message in app.flashes('success') %}
    <div class="...">
        {{ message }}
    </div>
{% endfor %}
```
- Affiche les messages de type 'success'
- Exemple : "Votre compte a été créé avec succès !"
- Les messages sont supprimés après affichage

### Affichage des erreurs

```twig
{% if error %}
    <div class="...">
        {{ error.messageKey|trans(error.messageData, 'security') }}
    </div>
{% endif %}
```
- **error.messageKey** : Clé du message (ex: "Invalid credentials.")
- **|trans(error.messageData, 'security')** : Traduit le message via le fichier `translations/security.fr.yaml`
- Résultat : "Email ou mot de passe incorrect."

### Formulaire HTML brut

```html
<form method="post" class="space-y-6">
```
- Formulaire HTML classique (pas de Symfony Form ici)
- `method="post"` : Envoi en POST

```html
<input type="email"
       value="{{ last_username }}"
       name="email"
       id="inputEmail">
```
- **value="{{ last_username }}"** : Pré-remplit avec le dernier email saisi
- **name="email"** : Nom du champ (récupéré côté serveur)

### Token CSRF

```twig
<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
```
- **csrf_token('authenticate')** : Génère un token CSRF unique
- **name="_csrf_token"** : Nom attendu par l'Authenticator
- Champ caché (l'utilisateur ne le voit pas)

---

## Configuration de sécurité

**Chemin :** `config/packages/security.yaml`

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\AppCustomAuthAuthenticator
            logout:
                path: app_logout

    access_control:
        # - { path: ^/admin, roles: ROLE_ADMIN }
        # - { path: ^/profile, roles: ROLE_USER }
```

**Explication détaillée :**

### password_hashers

```yaml
password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```
- Définit comment hasher les mots de passe
- `'auto'` : Symfony choisit le meilleur algorithme (bcrypt par défaut)
- S'applique à toutes les classes implémentant `PasswordAuthenticatedUserInterface`

### providers

```yaml
providers:
    app_user_provider:
        entity:
            class: App\Entity\User
            property: email
```
- **provider** : Dit à Symfony où trouver les utilisateurs
- **entity.class** : Utilise l'entité User
- **property: email** : Utilise l'email comme identifiant unique

**Que fait Symfony ?**
Quand on entre "test@test.com", Symfony fait :
```sql
SELECT * FROM user WHERE email = 'test@test.com'
```

### firewalls

```yaml
firewalls:
    dev:
        pattern: ^/(_(profiler|wdt)|css|images|js)/
        security: false
```
- **dev** : Pare-feu pour le mode développement
- **pattern** : Les URLs qui matchent ce pattern
- **security: false** : Pas d'authentification requise pour le profiler Symfony et les assets

```yaml
    main:
        lazy: true
        provider: app_user_provider
        custom_authenticator: App\Security\AppCustomAuthAuthenticator
        logout:
            path: app_logout
```
- **main** : Pare-feu principal
- **lazy: true** : N'initialise la session que si nécessaire (performance)
- **provider** : Utilise `app_user_provider` défini plus haut
- **custom_authenticator** : Utilise notre Authenticator personnalisé
- **logout.path** : Route pour se déconnecter

### access_control

```yaml
access_control:
    # - { path: ^/admin, roles: ROLE_ADMIN }
    # - { path: ^/profile, roles: ROLE_USER }
```
- Définit les règles d'accès par URL
- Commenté = pas de restrictions pour l'instant
- Exemple : `^/admin` nécessiterait le rôle ROLE_ADMIN

---

## Page d'accueil

**Chemin :** `src/Controller/HomeController.php`

```php
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
```

**Explication :**

```php
if (!$this->getUser()) {
    return $this->redirectToRoute('app_login');
}
```
- **$this->getUser()** : Retourne l'utilisateur connecté (ou null)
- Si null → redirige vers login
- **Protection simple** : Seuls les utilisateurs connectés voient les posts

```php
$posts = $postRepository->findAll();
```
- Récupère tous les posts de la BDD
- Équivalent SQL : `SELECT * FROM post`

**Template :** `templates/home/index.html.twig`

Le template affiche :
- Une navbar avec l'email de l'utilisateur
- Un bouton de déconnexion
- Un bouton dark mode
- La liste des posts (ou un message si aucun post)

---

## Traductions

**Chemin :** `translations/security.fr.yaml`

```yaml
# Traductions des messages de sécurité en français
"Invalid credentials.": "Email ou mot de passe incorrect."
"Username could not be found.": "Utilisateur introuvable."
"An authentication exception occurred.": "Une erreur d'authentification s'est produite."
"Invalid CSRF token.": "Jeton CSRF invalide. Veuillez réessayer."
```

**Explication :**

Symfony génère des messages en anglais. Ce fichier les traduit en français.

**Comment ça marche ?**

Dans le template :
```twig
{{ error.messageKey|trans(error.messageData, 'security') }}
```
- **error.messageKey** : "Invalid credentials."
- **|trans(...)** : Cherche dans `translations/security.fr.yaml`
- **'security'** : Domaine de traduction
- Résultat : "Email ou mot de passe incorrect."

**Configuration de la locale :** `config/packages/translation.yaml`

```yaml
framework:
    default_locale: fr
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks: ['fr']
```
- **default_locale: fr** : Langue par défaut = français
- **fallbacks: ['fr']** : Si une traduction manque, essaie en français

---

## Résumé du flux complet

### 1. Inscription (Register)

```
1. Utilisateur va sur /register
   ↓
2. GET /register → RegistrationController::register()
   ↓
3. Affiche le formulaire vide
   ↓
4. Utilisateur remplit email + mot de passe
   ↓
5. POST /register → RegistrationController::register()
   ↓
6. handleRequest() récupère les données
   ↓
7. isSubmitted() && isValid() → Validation
   ✓ Email valide ?
   ✓ Mot de passe >= 6 caractères ?
   ✓ Email unique en BDD ?
   ↓
8. Hashage du mot de passe : "password123" → "$2y$13$xyz..."
   ↓
9. persist($user) + flush() → INSERT INTO user
   ↓
10. addFlash('success', '...') → Message de succès
   ↓
11. redirectToRoute('app_login') → Redirection vers /login
```

### 2. Connexion (Login)

```
1. Utilisateur va sur /login
   ↓
2. GET /login → SecurityController::login()
   ↓
3. Affiche le formulaire de connexion
   ↓
4. Utilisateur remplit email + mot de passe
   ↓
5. POST /login → AppCustomAuthAuthenticator::authenticate()
   ↓
6. Récupère email et password du formulaire
   ↓
7. Vérifie le token CSRF
   ↓
8. UserBadge($email) → SELECT * FROM user WHERE email = '...'
   ↓
9. PasswordCredentials → Compare le hash
   password_verify("password123", "$2y$13$xyz...")
   ↓
10. Si OK → onAuthenticationSuccess()
    ↓
11. redirectToRoute('app_home') → Redirection vers /
    ↓
12. Session créée → Utilisateur connecté !
```

### 3. Page d'accueil (Home)

```
1. Utilisateur va sur /
   ↓
2. GET / → HomeController::index()
   ↓
3. $this->getUser() → Récupère l'utilisateur de la session
   ↓
4. Si null → redirectToRoute('app_login')
   ↓
5. Si connecté → $postRepository->findAll()
   ↓
6. Affiche templates/home/index.html.twig avec la liste des posts
```

### 4. Déconnexion (Logout)

```
1. Utilisateur clique sur "Déconnexion"
   ↓
2. GET /logout → Symfony intercepte automatiquement
   ↓
3. Destruction de la session
   ↓
4. Redirection vers / (configuré dans security.yaml)
   ↓
5. HomeController détecte "pas connecté"
   ↓
6. Redirection vers /login
```

---

## Points clés à retenir

### Sécurité

1. **Mots de passe TOUJOURS hashés** (jamais en clair)
2. **Protection CSRF** sur tous les formulaires
3. **APP_SECRET** obligatoire et secret
4. **Validation** côté serveur (pas seulement client)

### Architecture Symfony

1. **Entité** = Table en BDD
2. **Repository** = Requêtes SQL
3. **Controller** = Logique métier
4. **FormType** = Structure de formulaire
5. **Template** = Vue HTML
6. **Authenticator** = Processus d'authentification

### Flux de données

```
Utilisateur
    ↓
Template (HTML)
    ↓
Controller (Traitement)
    ↓
FormType (Validation)
    ↓
Entity (Objet PHP)
    ↓
Repository (SQL)
    ↓
Base de données
```

### Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Voir les routes
php bin/console debug:router

# Créer une migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Créer un utilisateur en ligne de commande
php bin/console security:hash-password
```

---

## Dépannage

### Problème : "Jeton CSRF invalide"

**Solutions :**
1. Vérifier que APP_SECRET est défini dans .env
2. Vérifier que csrf_protection: true dans framework.yaml
3. Vider le cache : `php bin/console cache:clear`
4. Vider le cache du navigateur (Ctrl+Shift+Delete)

### Problème : "Invalid credentials"

**Solutions :**
1. Vérifier que l'email existe en BDD
2. Vérifier que le mot de passe est correct
3. Vérifier les traductions dans security.fr.yaml

### Problème : "Access denied"

**Solutions :**
1. Vérifier que l'utilisateur est connecté : `dump($this->getUser())`
2. Vérifier les access_control dans security.yaml
3. Vérifier les rôles de l'utilisateur

---

## Fin du guide

Ce guide couvre **tout** le système d'authentification :
- ✅ Configuration
- ✅ Entités
- ✅ Formulaires
- ✅ Contrôleurs
- ✅ Templates
- ✅ Sécurité
- ✅ Traductions
- ✅ Flux complet

Bon courage pour la suite du projet ! 🚀

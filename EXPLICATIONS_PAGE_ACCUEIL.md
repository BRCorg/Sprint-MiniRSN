# EXPLICATIONS DÉTAILLÉES - Page d'accueil avec liste des posts

Ce document explique **ligne par ligne** comment fonctionne la nouvelle page d'accueil qui affiche la liste des posts.

---

## SOMMAIRE

1. [Flux de navigation](#1-flux-de-navigation)
2. [Le contrôleur HomeController](#2-le-contrôleur-homecontroller)
3. [Le template home/index.html.twig](#3-le-template-homeindexhtmltwig)
4. [Explications du code Tailwind](#4-explications-du-code-tailwind)
5. [Comment tester](#5-comment-tester)

---

## 1. FLUX DE NAVIGATION

### Nouveau comportement

```
Utilisateur visite http://localhost:8000/
    ↓
HomeController::index()
    ↓
Est-ce que l'utilisateur est connecté ?
    ├─ NON → Redirection vers /login
    └─ OUI → Récupération des posts + Affichage
```

### Ancien comportement (supprimé)

```
❌ Utilisateur visite /
    ├─ NON connecté → Page avec 2 boutons (Inscription/Connexion)
    └─ OUI connecté → Redirection vers /post/
```

### Pourquoi ce changement ?

**Avant :** Vous aviez une page intermédiaire inutile si quelqu'un est déjà connecté.

**Maintenant :**
- **Plus simple** : Si pas connecté → login direct
- **Plus logique** : La page d'accueil `/` affiche le contenu principal (les posts)

---

## 2. LE CONTRÔLEUR HOMECONTROLLER

### Code complet expliqué

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
        // ÉTAPE 1 : Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // ÉTAPE 2 : Récupérer tous les posts
        $posts = $postRepository->findAll();

        // ÉTAPE 3 : Afficher la page
        return $this->render('home/index.html.twig', [
            'posts' => $posts,
        ]);
    }
}
```

---

### Ligne par ligne

#### Import du PostRepository

```php
use App\Repository\PostRepository;
```

**Qu'est-ce qu'un Repository ?**

Un **Repository** est une classe qui fait les requêtes SQL vers la base de données.

- `PostRepository` = classe pour récupérer des `Post`
- `UserRepository` = classe pour récupérer des `User`
- etc.

C'est Doctrine qui génère automatiquement ces classes quand vous créez une entity.

---

#### La route

```php
#[Route('/', name: 'app_home')]
```

- `'/'` : URL de la page d'accueil
- `name: 'app_home'` : Nom de la route (pour générer des liens avec `path('app_home')`)

---

#### Injection de dépendances

```php
public function index(PostRepository $postRepository): Response
```

**Comment ça marche ?**

Symfony voit que vous demandez un `PostRepository` en paramètre.
→ Il crée automatiquement une instance et vous la passe !

C'est ce qu'on appelle **l'injection de dépendances**.

**Équivalent manuel (à NE PAS faire) :**

```php
// ❌ MAUVAIS (code complexe et répétitif)
public function index(): Response
{
    $entityManager = $this->getDoctrine()->getManager();
    $postRepository = $entityManager->getRepository(Post::class);
    // ...
}

// ✅ BON (injection automatique)
public function index(PostRepository $postRepository): Response
{
    // $postRepository est déjà prêt à utiliser !
}
```

---

#### Vérification de la connexion

```php
if (!$this->getUser()) {
    return $this->redirectToRoute('app_login');
}
```

**Explication :**

- `$this->getUser()` : Retourne l'utilisateur connecté (ou `null` si pas connecté)
- `!$this->getUser()` : Si PAS d'utilisateur (donc pas connecté)
- `return $this->redirectToRoute('app_login')` : Redirige vers `/login`

**En clair :**

```
Si tu n'es pas connecté, dégage vers la page de connexion !
```

**Équivalent HTTP :**

Symfony génère une réponse HTTP avec code 302 (Redirection) :

```http
HTTP/1.1 302 Found
Location: /login
```

Le navigateur suit automatiquement cette redirection.

---

#### Récupération des posts

```php
$posts = $postRepository->findAll();
```

**Qu'est-ce que fait `findAll()` ?**

C'est une méthode de Doctrine qui génère et exécute cette requête SQL :

```sql
SELECT * FROM post
```

**Résultat :**

`$posts` contient un **tableau d'objets Post**.

Exemple :
```php
$posts = [
    Post { id: 1, content: "Hello", user: User {...}, createdAt: ... },
    Post { id: 2, content: "World", user: User {...}, createdAt: ... },
]
```

**Autres méthodes utiles :**

```php
// Trouver UN post par son ID
$post = $postRepository->find(1);

// Trouver les posts d'un utilisateur spécifique
$posts = $postRepository->findBy(['user' => $user]);

// Trouver les 10 derniers posts
$posts = $postRepository->findBy([], ['createdAt' => 'DESC'], 10);
```

---

#### Rendu du template

```php
return $this->render('home/index.html.twig', [
    'posts' => $posts,
]);
```

**Que fait cette ligne ?**

1. Charge le fichier `templates/home/index.html.twig`
2. Passe la variable `$posts` au template (accessible avec `{{ posts }}`)
3. Symfony transforme le Twig en HTML
4. Retourne une réponse HTTP avec le HTML généré

**En Twig, vous pouvez maintenant faire :**

```twig
{% for post in posts %}
    {{ post.content }}
{% endfor %}
```

---

## 3. LE TEMPLATE HOME/INDEX.HTML.TWIG

### Structure générale

```twig
{% extends 'base.html.twig' %}

{% block body %}
    {# NAVBAR #}
    <nav>...</nav>

    {# CONTENU #}
    <div>
        {% if posts is empty %}
            {# Message "Aucune publication" #}
        {% else %}
            {% for post in posts %}
                {# Affichage du post #}
            {% endfor %}
        {% endif %}
    </div>

    {# SCRIPT DARK MODE #}
    <script>...</script>
{% endblock %}
```

---

### 1. LA NAVBAR

```twig
<nav class="bg-white dark:bg-gray-800 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
```

**Explications des classes :**

- `bg-white dark:bg-gray-800` : Fond blanc (clair) / gris foncé (sombre)
- `shadow-lg` : Ombre portée grande
- `max-w-7xl` : Largeur maximale de 80rem (1280px)
- `mx-auto` : Centré horizontalement (margin-left: auto, margin-right: auto)
- `px-4` : Padding horizontal de 16px
- `sm:px-6` : Sur écrans moyens (≥640px), padding de 24px
- `lg:px-8` : Sur grands écrans (≥1024px), padding de 32px
- `flex` : Flexbox
- `justify-between` : Espace entre les éléments (logo à gauche, boutons à droite)
- `items-center` : Alignement vertical centré
- `h-16` : Hauteur de 64px

---

#### Logo et titre

```twig
<div class="flex items-center gap-3">
    <div class="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
        <svg class="w-6 h-6 text-white">...</svg>
    </div>
    <span class="text-xl font-bold text-gray-900 dark:text-white">Mini RSN</span>
</div>
```

**Classes importantes :**

- `gap-3` : Espacement de 12px entre les éléments enfants
- `p-2` : Padding de 8px
- `bg-gradient-to-br` : Dégradé du haut-gauche vers bas-droite
- `from-blue-500 to-purple-600` : Dégradé bleu → violet
- `rounded-lg` : Coins arrondis
- `text-xl` : Taille de texte 1.25rem (20px)
- `font-bold` : Gras

---

#### Email de l'utilisateur

```twig
<span class="text-sm text-gray-600 dark:text-gray-300">
    {{ app.user.email }}
</span>
```

**`app.user` en Twig :**

- `app` : Variable globale Symfony disponible dans tous les templates
- `app.user` : L'utilisateur connecté (même que `$this->getUser()` en PHP)
- `app.user.email` : Appelle automatiquement `$user->getEmail()`

**Équivalent PHP :**

```php
$this->getUser()->getEmail();
```

---

#### Bouton de déconnexion

```twig
<a href="{{ path('app_logout') }}"
   class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
    Déconnexion
</a>
```

**`path('app_logout')` :**

Génère l'URL de la route nommée `app_logout` (définie dans `SecurityController`).

Résultat : `/logout`

**Classes Tailwind :**

- `px-4 py-2` : Padding horizontal 16px, vertical 8px
- `text-sm` : Petite taille de texte
- `font-medium` : Graisse moyenne
- `bg-red-600` : Fond rouge
- `hover:bg-red-700` : Au survol, rouge plus foncé
- `transition-colors` : Animation fluide des couleurs

---

### 2. MESSAGE "AUCUNE PUBLICATION"

```twig
{% if posts is empty %}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4">...</svg>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Aucune publication
        </h3>
        <p class="text-gray-600 dark:text-gray-400">
            Soyez le premier à publier un message !
        </p>
    </div>
{% endif %}
```

**Logique Twig :**

- `{% if posts is empty %}` : Si le tableau `$posts` est vide (aucune publication en BDD)
- Affiche un message sympa avec une icône
- `{% endif %}` : Fin de la condition

**Classes intéressantes :**

- `rounded-xl` : Coins très arrondis
- `p-12` : Padding de 48px
- `text-center` : Texte centré
- `mx-auto` : Centre l'icône SVG horizontalement
- `mb-4` : Marge bas de 16px

---

### 3. BOUCLE SUR LES POSTS

```twig
{% else %}
    <div class="space-y-6">
        {% for post in posts %}
            {# Carte du post #}
        {% endfor %}
    </div>
{% endif %}
```

**Logique :**

- `{% else %}` : Sinon (si `posts` n'est PAS vide)
- `space-y-6` : Espacement vertical de 24px entre chaque post
- `{% for post in posts %}` : Boucle sur chaque post
- `{% endfor %}` : Fin de la boucle

---

### 4. CARTE D'UN POST

#### En-tête (auteur + date)

```twig
<div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-3">
        {# Avatar #}
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
            {{ post.user.email|slice(0, 1)|upper }}
        </div>
```

**Filtre Twig `|slice(0, 1)` :**

Récupère **1 caractère** à partir de la position **0** (le premier caractère).

Exemple :
```twig
{{ "john@example.com"|slice(0, 1) }}
{# Résultat : "j" #}
```

**Filtre `|upper` :**

Met en majuscules.

```twig
{{ "john@example.com"|slice(0, 1)|upper }}
{# Résultat : "J" #}
```

**Résultat final :**

Un avatar circulaire avec la première lettre de l'email en majuscule.

---

#### Nom de l'auteur

```twig
<p class="font-semibold text-gray-900 dark:text-white">
    {{ post.user.email }}
</p>
```

**Accès aux relations Doctrine :**

- `post.user` : Relation ManyToOne (un post appartient à un user)
- Doctrine charge automatiquement l'utilisateur associé
- `post.user.email` : Appelle `$post->getUser()->getEmail()`

---

#### Date de création

```twig
<p class="text-sm text-gray-500 dark:text-gray-400">
    {{ post.createdAt|date('d/m/Y à H:i') }}
</p>
```

**Filtre `|date()` :**

Formate une date.

- `d` : Jour sur 2 chiffres (01-31)
- `m` : Mois sur 2 chiffres (01-12)
- `Y` : Année sur 4 chiffres (2024)
- `H` : Heure sur 2 chiffres (00-23)
- `i` : Minutes sur 2 chiffres (00-59)

Exemple :
```twig
{{ post.createdAt|date('d/m/Y à H:i') }}
{# Résultat : 15/01/2024 à 14:30 #}
```

**Autres formats utiles :**

```twig
{{ date|date('Y-m-d') }}          {# 2024-01-15 #}
{{ date|date('d F Y') }}          {# 15 January 2024 #}
{{ date|date('H:i:s') }}          {# 14:30:25 #}
{{ date|date('l, d F Y') }}       {# Monday, 15 January 2024 #}
```

---

#### Contenu du post

```twig
<p class="text-gray-900 dark:text-white whitespace-pre-line">
    {{ post.content }}
</p>
```

**`whitespace-pre-line` :**

CSS : `white-space: pre-line;`

**Effet :** Préserve les sauts de ligne dans le texte.

Sans :
```
Bonjour tout le monde !
Comment allez-vous ?
```
Devient : `Bonjour tout le monde ! Comment allez-vous ?`

Avec :
```
Bonjour tout le monde !
Comment allez-vous ?
```
Reste :
```
Bonjour tout le monde !
Comment allez-vous ?
```

---

#### Image (si présente)

```twig
{% if post.image %}
    <div class="px-6 pb-4">
        <img src="{{ asset('uploads/posts/' ~ post.image) }}"
             alt="Image du post"
             class="w-full rounded-lg shadow-md">
    </div>
{% endif %}
```

**Fonction `asset()` :**

Génère l'URL complète vers un fichier public.

```twig
{{ asset('uploads/posts/mon-image.jpg') }}
{# Résultat : /uploads/posts/mon-image.jpg #}
```

**Opérateur `~` (tilde) :**

Concaténation de chaînes (équivalent de `.` en PHP).

```twig
{{ 'uploads/posts/' ~ post.image }}
{# Si post.image = "photo-123.jpg" #}
{# Résultat : uploads/posts/photo-123.jpg #}
```

---

## 4. EXPLICATIONS DU CODE TAILWIND

### Responsive Design

Tailwind utilise des **préfixes** pour les différentes tailles d'écran :

```twig
<div class="px-4 sm:px-6 lg:px-8">
```

**Signification :**

- `px-4` : Padding 16px par défaut (mobile)
- `sm:px-6` : Sur écrans ≥640px, padding 24px
- `lg:px-8` : Sur écrans ≥1024px, padding 32px

**Breakpoints Tailwind :**

| Préfixe | Taille | Équivalent CSS |
|---------|--------|----------------|
| (rien) | 0px+ | Mobile |
| `sm:` | 640px+ | Tablette |
| `md:` | 768px+ | Tablette large |
| `lg:` | 1024px+ | Desktop |
| `xl:` | 1280px+ | Grand écran |
| `2xl:` | 1536px+ | Très grand écran |

---

### Hover et transitions

```twig
<a class="bg-red-600 hover:bg-red-700 transition-colors">
```

- `bg-red-600` : Fond rouge par défaut
- `hover:bg-red-700` : Au survol, rouge plus foncé
- `transition-colors` : Animation fluide des couleurs

**Équivalent CSS :**

```css
a {
    background-color: #dc2626; /* red-600 */
    transition: color 150ms;
}

a:hover {
    background-color: #b91c1c; /* red-700 */
}
```

---

### Flexbox

```twig
<div class="flex justify-between items-center gap-4">
```

- `flex` : `display: flex;`
- `justify-between` : `justify-content: space-between;` (espace entre les éléments)
- `items-center` : `align-items: center;` (centré verticalement)
- `gap-4` : `gap: 1rem;` (16px d'espacement entre les enfants)

---

### Grille de tailles

```
w-10  = width: 2.5rem  (40px)
h-16  = height: 4rem   (64px)
p-4   = padding: 1rem  (16px)
m-6   = margin: 1.5rem (24px)
```

**Formule :** Nombre × 4px (sauf exceptions)

- `p-1` = 4px
- `p-2` = 8px
- `p-3` = 12px
- `p-4` = 16px
- `p-5` = 20px
- `p-6` = 24px
- etc.

---

## 5. COMMENT TESTER

### Étape 1 : Créer un compte (si pas déjà fait)

```bash
# Ouvrir le navigateur
http://localhost:8000
```

Vous serez **automatiquement redirigé** vers `/login`.

1. Cliquer sur "S'inscrire"
2. Créer un compte : `test@test.com` / `password123`
3. Se connecter

---

### Étape 2 : Vérifier la page d'accueil

Une fois connecté, vous devriez voir :

- **Navbar en haut** avec :
  - Logo "Mini RSN"
  - Votre email
  - Bouton dark mode
  - Bouton "Déconnexion"

- **Message "Aucune publication"** (si aucun post en BDD)

---

### Étape 3 : Tester le dark mode

1. Cliquer sur le bouton lune/soleil
2. La page passe en mode sombre
3. Recharger la page (F5)
4. Le mode sombre est conservé ! (grâce à `localStorage`)

---

### Étape 4 : Créer des posts pour tester l'affichage

Pour créer des posts directement en base de données :

```bash
docker exec symfony_php php bin/console doctrine:query:sql "INSERT INTO post (content, created_at, user_id) VALUES ('Mon premier post !', NOW(), 1)"
```

Recharger la page → Vous devriez voir le post affiché !

---

## RÉCAPITULATIF DES CONCEPTS

### Symfony

| Concept | Explication |
|---------|-------------|
| `$this->getUser()` | Récupère l'utilisateur connecté |
| `$this->redirectToRoute()` | Redirige vers une route |
| `$postRepository->findAll()` | Récupère tous les posts en BDD |
| `$this->render()` | Affiche un template Twig |

### Twig

| Syntaxe | Explication |
|---------|-------------|
| `{{ variable }}` | Affiche une variable |
| `{% if condition %}` | Condition |
| `{% for item in items %}` | Boucle |
| `{{ text\|upper }}` | Filtre (transforme le texte) |
| `{{ path('route_name') }}` | Génère une URL |
| `{{ asset('path') }}` | Génère une URL vers un fichier public |

### Tailwind CSS

| Classe | CSS équivalent |
|--------|----------------|
| `flex` | `display: flex;` |
| `bg-blue-500` | `background-color: #3b82f6;` |
| `text-white` | `color: white;` |
| `p-4` | `padding: 1rem;` (16px) |
| `rounded-lg` | `border-radius: 0.5rem;` |
| `hover:bg-blue-600` | `background-color` au survol |
| `dark:bg-gray-800` | Styles pour le mode sombre |

---

## PROCHAINES ÉTAPES

Maintenant que vous avez la liste des posts, vous pouvez ajouter :

1. **Formulaire pour créer un post** (avec upload d'image)
2. **Bouton "Modifier" pour ses propres posts**
3. **Bouton "Supprimer" pour ses propres posts**
4. **Système de commentaires**
5. **Système de likes**

Bonne continuation ! 🚀

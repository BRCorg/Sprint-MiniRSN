# EXPLICATIONS DÉTAILLÉES - Inscription et Connexion avec Tailwind et Dark Mode

Ce document explique **en détail** toutes les étapes que nous avons effectuées pour créer un système d'inscription et de connexion moderne avec Tailwind CSS et un mode sombre.

---

## SOMMAIRE

1. [Vue d'ensemble du système](#1-vue-densemble-du-système)
2. [Configuration de Tailwind CSS](#2-configuration-de-tailwind-css)
3. [Création de la page d'accueil](#3-création-de-la-page-daccueil)
4. [Template d'inscription](#4-template-dinscription)
5. [Template de connexion](#5-template-de-connexion)
6. [Système de Dark Mode](#6-système-de-dark-mode)
7. [Modification du contrôleur d'inscription](#7-modification-du-contrôleur-dinscription)
8. [Comment tester](#8-comment-tester)

---

## 1. VUE D'ENSEMBLE DU SYSTÈME

### Architecture du flux

```
Utilisateur NON connecté
    ↓
Page d'accueil (/)
    ├── Bouton "Créer un compte" → /register
    └── Bouton "Se connecter" → /login
        ↓
Inscription (/register)
    ↓
Message de succès + Redirection vers /login
    ↓
Connexion (/login)
    ↓
Utilisateur CONNECTÉ → Redirection vers /post/
```

### Fichiers modifiés/créés

1. **templates/base.html.twig** - Template de base avec Tailwind
2. **src/Controller/HomeController.php** - Contrôleur pour la page d'accueil
3. **templates/home/index.html.twig** - Page d'accueil
4. **templates/registration/register.html.twig** - Page d'inscription
5. **templates/security/login.html.twig** - Page de connexion
6. **src/Controller/RegistrationController.php** - Modifié pour rediriger vers login

---

## 2. CONFIGURATION DE TAILWIND CSS

### Qu'est-ce que Tailwind CSS ?

**Tailwind CSS** est un framework CSS "utility-first" qui permet de styliser rapidement sans écrire de CSS personnalisé.

Au lieu de :
```css
.button {
    background-color: blue;
    padding: 10px 20px;
    border-radius: 8px;
}
```

On écrit directement dans le HTML :
```html
<button class="bg-blue-500 px-5 py-2 rounded-lg">Button</button>
```

### Pourquoi utiliser le CDN ?

Le CDN (Content Delivery Network) permet d'utiliser Tailwind **sans installation**.

**Fichier : `templates/base.html.twig`**

```twig
<script src="https://cdn.tailwindcss.com"></script>
```

**Avantages :**
- ✅ Pas besoin de npm install
- ✅ Pas de build process
- ✅ Fonctionne immédiatement

**Inconvénients :**
- ❌ Plus lourd en production
- ❌ Requiert une connexion Internet

### Configuration du dark mode

```javascript
<script>
    tailwind.config = {
        darkMode: 'class', // Active le mode sombre avec la classe 'dark'
    }
</script>
```

**Explication :**
- `darkMode: 'class'` : Le mode sombre est activé quand on ajoute la classe `dark` à l'élément `<html>`
- Alternative : `darkMode: 'media'` (se base sur les préférences système)

### Classes Tailwind sur le body

```twig
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
```

**Décomposition :**
- `bg-gray-50` : Fond gris très clair (mode clair)
- `dark:bg-gray-900` : Fond gris très foncé (mode sombre)
- `transition-colors` : Animation de transition pour les couleurs
- `duration-200` : Durée de la transition (200ms)

---

## 3. CRÉATION DE LA PAGE D'ACCUEIL

### Contrôleur HomeController

**Fichier : `src/Controller/HomeController.php`**

```php
#[Route('/', name: 'app_home')]
public function index(): Response
{
    // Si l'utilisateur est déjà connecté, on le redirige vers les posts
    if ($this->getUser()) {
        return $this->redirectToRoute('app_post_index');
    }

    // Sinon, on affiche la page d'accueil avec les boutons
    return $this->render('home/index.html.twig');
}
```

**Explications ligne par ligne :**

1. `#[Route('/', name: 'app_home')]` :
   - Définit la route `/` (page d'accueil)
   - Nomme la route `app_home` (utilisable avec `path('app_home')`)

2. `if ($this->getUser())` :
   - `$this->getUser()` retourne l'utilisateur connecté (ou `null` si pas connecté)
   - Si quelqu'un est déjà connecté, pas besoin de lui montrer inscription/connexion

3. `return $this->redirectToRoute('app_post_index')` :
   - Redirige vers la liste des posts (route `app_post_index`)
   - On verra ça plus tard !

4. `return $this->render('home/index.html.twig')` :
   - Affiche le template de la page d'accueil

### Template de la page d'accueil

**Fichier : `templates/home/index.html.twig`**

#### Structure globale

```twig
<div class="min-h-screen flex items-center justify-center px-4">
```

**Classes Tailwind expliquées :**
- `min-h-screen` : Hauteur minimale = hauteur de l'écran (100vh)
- `flex` : Active Flexbox
- `items-center` : Centre verticalement
- `justify-center` : Centre horizontalement
- `px-4` : Padding horizontal de 1rem (16px)

Résultat : **Contenu centré au milieu de l'écran**

#### Bouton Dark Mode

```twig
<button id="darkModeToggle"
        class="fixed top-6 right-6 p-3 rounded-full bg-gray-200 dark:bg-gray-700 ...">
```

**Classes expliquées :**
- `fixed` : Position fixe (ne bouge pas au scroll)
- `top-6` : 24px du haut
- `right-6` : 24px de la droite
- `p-3` : Padding de 12px
- `rounded-full` : Arrondi complet (cercle)
- `bg-gray-200` : Fond gris clair (mode clair)
- `dark:bg-gray-700` : Fond gris foncé (mode sombre)

#### Icônes SVG

```twig
<svg id="sunIcon" class="w-6 h-6 hidden dark:block" ...>
```

- `w-6 h-6` : Largeur et hauteur de 24px
- `hidden` : Caché par défaut
- `dark:block` : Visible en mode sombre

```twig
<svg id="moonIcon" class="w-6 h-6 block dark:hidden" ...>
```

- `block` : Visible par défaut
- `dark:hidden` : Caché en mode sombre

**Résultat :** Lune en mode clair, Soleil en mode sombre

#### Logo et titre

```twig
<div class="inline-block p-4 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-4 shadow-xl">
```

**Classes expliquées :**
- `inline-block` : Bloc en ligne (s'adapte au contenu)
- `p-4` : Padding de 16px
- `bg-gradient-to-br` : Dégradé du haut-gauche vers bas-droite
- `from-blue-500` : Commence en bleu
- `to-purple-600` : Termine en violet
- `rounded-full` : Cercle parfait
- `shadow-xl` : Ombre portée très grande

#### Boutons

**Bouton Inscription (bleu-violet) :**

```twig
<a href="{{ path('app_register') }}"
   class="... bg-gradient-to-r from-blue-500 to-purple-600 ... hover:from-blue-600 hover:to-purple-700 transform hover:scale-105 ...">
```

**Classes clés :**
- `bg-gradient-to-r` : Dégradé horizontal (left to right)
- `hover:from-blue-600` : Au survol, dégradé plus foncé
- `transform hover:scale-105` : Agrandit de 5% au survol
- `transition-all duration-200` : Anime tous les changements en 200ms

**Bouton Connexion (gris) :**

```twig
<a href="{{ path('app_login') }}"
   class="... bg-gray-100 dark:bg-gray-700 ...">
```

Plus sobre, sans dégradé, pour indiquer que c'est une action secondaire.

#### Séparateur "Ou"

```twig
<div class="relative">
    <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
    </div>
    <div class="relative flex justify-center text-sm">
        <span class="px-4 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">
            Ou
        </span>
    </div>
</div>
```

**Comment ça marche :**
1. Ligne horizontale en arrière-plan (`border-t`)
2. Texte "Ou" positionné au centre avec fond blanc
3. Le fond blanc cache la ligne derrière le texte

---

## 4. TEMPLATE D'INSCRIPTION

**Fichier : `templates/registration/register.html.twig`**

### Affichage des erreurs

```twig
{% if form_errors(registrationForm) %}
    <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
```

**Explications :**
- `form_errors(registrationForm)` : Récupère les erreurs globales du formulaire
- `bg-red-50` : Fond rouge très clair
- `dark:bg-red-900/20` : Fond rouge foncé avec 20% d'opacité en mode sombre
- `border-red-200` : Bordure rouge claire

### Champs de formulaire

#### Structure d'un champ

```twig
<div>
    {{ form_label(...) }}     {# Label #}
    {{ form_widget(...) }}    {# Input #}
    {% if form_errors(...) %} {# Erreurs #}
        <p class="...">{{ form_errors(...) }}</p>
    {% endif %}
</div>
```

#### Personnalisation avec Tailwind

```twig
{{ form_label(registrationForm.email, 'Adresse email', {
    'label_attr': {'class': 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2'}
}) }}
```

**Explication :**
- `form_label` : Génère un `<label>`
- Premier paramètre : Champ du formulaire
- Deuxième paramètre : Texte personnalisé
- `label_attr` : Attributs HTML du label

```twig
{{ form_widget(registrationForm.email, {
    'attr': {
        'class': 'w-full px-4 py-3 rounded-lg border ...',
        'placeholder': 'votre@email.com'
    }
}) }}
```

**Classes importantes :**
- `w-full` : Largeur 100%
- `px-4 py-3` : Padding horizontal 16px, vertical 12px
- `rounded-lg` : Coins arrondis
- `border-gray-300` : Bordure grise
- `focus:ring-2 focus:ring-blue-500` : Anneau bleu au focus
- `focus:border-transparent` : Enlève la bordure native au focus

### Checkbox des conditions

```twig
<div class="flex items-start">
    <div class="flex items-center h-5">
        {{ form_widget(registrationForm.agreeTerms, {
            'attr': {
                'class': 'w-4 h-4 rounded border-gray-300 ...'
            }
        }) }}
    </div>
    <div class="ml-3">
        {{ form_label(registrationForm.agreeTerms, null, {
            'label_attr': {'class': 'text-sm text-gray-700 dark:text-gray-300'}
        }) }}
    </div>
</div>
```

**Structure :**
1. Container flex pour aligner checkbox + label
2. Checkbox de 16x16px (w-4 h-4)
3. Label à côté avec marge gauche (ml-3)

---

## 5. TEMPLATE DE CONNEXION

**Fichier : `templates/security/login.html.twig`**

### Message de succès après inscription

```twig
{% for message in app.flashes('success') %}
    <div class="mb-6 bg-green-50 dark:bg-green-900/20 ...">
        ...
        {{ message }}
        ...
    </div>
{% endfor %}
```

**Comment ça marche :**
1. Le contrôleur d'inscription fait `$this->addFlash('success', '...')`
2. Le message est stocké en **session**
3. À la prochaine page (login), `app.flashes('success')` récupère le message
4. Le message est affiché **une seule fois** puis supprimé

### Message d'erreur de connexion

```twig
{% if error %}
    <div class="mb-6 bg-red-50 dark:bg-red-900/20 ...">
        {{ error.messageKey|trans(error.messageData, 'security') }}
    </div>
{% endif %}
```

**Explications :**
- `error` : Variable passée par Symfony si la connexion échoue
- `error.messageKey` : Clé du message d'erreur
- `|trans(error.messageData, 'security')` : Traduit le message

### Formulaire de connexion

```twig
<form method="post" class="space-y-6">
```

- `method="post"` : Méthode HTTP POST
- `space-y-6` : Espacement vertical de 24px entre les éléments enfants

#### Champ email

```html
<input type="email"
       value="{{ last_username }}"
       name="email"
       id="inputEmail"
       autocomplete="email"
       required
       autofocus
       placeholder="votre@email.com"
       class="...">
```

**Attributs HTML :**
- `value="{{ last_username }}"` : Pré-remplit avec le dernier email utilisé
- `name="email"` : Nom du champ (utilisé par Symfony)
- `autocomplete="email"` : Active l'auto-complétion du navigateur
- `required` : Champ obligatoire
- `autofocus` : Focus automatique au chargement

#### Token CSRF

```html
<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
```

**Qu'est-ce qu'un token CSRF ?**

CSRF = Cross-Site Request Forgery (attaque par falsification de requête)

**Exemple d'attaque SANS token :**
1. Vous êtes connecté sur minirsn.com
2. Vous visitez un site malveillant
3. Ce site envoie automatiquement une requête POST vers minirsn.com/logout
4. Vous êtes déconnecté sans le savoir !

**Avec token CSRF :**
1. Symfony génère un token unique : `a3f5b2c8d1e9`
2. Le stocke en session
3. L'insère dans le formulaire
4. À la soumission, vérifie que le token correspond
5. Si différent → **attaque détectée** ❌

#### Remember me

```html
<input type="checkbox"
       name="_remember_me"
       id="remember_me"
       class="...">
<label for="remember_me">Se souvenir de moi</label>
```

**Fonctionnement :**
- Si coché : cookie de session valable 7 jours (configuré dans `security.yaml`)
- Si non coché : cookie de session (supprimé à la fermeture du navigateur)

---

## 6. SYSTÈME DE DARK MODE

### Comment fonctionne le dark mode ?

#### Principe

Tailwind utilise la classe `dark:` pour les styles en mode sombre.

```html
<div class="bg-white dark:bg-gray-800">
```

- Mode clair : `bg-white` (fond blanc)
- Mode sombre : `dark:bg-gray-800` (fond gris foncé)

**Mais comment activer le mode sombre ?**

En ajoutant la classe `dark` à l'élément `<html>` :

```html
<html class="dark">
```

### Script JavaScript

```javascript
function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;

    // 1. Vérifier la préférence sauvegardée
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // 2. Appliquer le thème au chargement
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        htmlElement.classList.add('dark');
    }

    // 3. Écouter les clics sur le bouton
    darkModeToggle.addEventListener('click', () => {
        htmlElement.classList.toggle('dark');

        // 4. Sauvegarder la préférence
        if (htmlElement.classList.contains('dark')) {
            localStorage.setItem('theme', 'dark');
        } else {
            localStorage.setItem('theme', 'light');
        }
    });
}

document.addEventListener('DOMContentLoaded', initDarkMode);
```

#### Explications ligne par ligne

**1. Récupération des éléments**

```javascript
const darkModeToggle = document.getElementById('darkModeToggle');
```
- Récupère le bouton par son ID

```javascript
const htmlElement = document.documentElement;
```
- `document.documentElement` = élément `<html>`

**2. Vérifier les préférences**

```javascript
const savedTheme = localStorage.getItem('theme');
```
- `localStorage` : Stockage persistant dans le navigateur
- `getItem('theme')` : Récupère la valeur de la clé `theme`
- Retourne `'dark'`, `'light'`, ou `null` si pas défini

```javascript
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
```
- `window.matchMedia()` : Teste une media query CSS
- `(prefers-color-scheme: dark)` : Préférence système de l'utilisateur
- `.matches` : `true` si l'utilisateur préfère le mode sombre

**3. Appliquer le thème au chargement**

```javascript
if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
    htmlElement.classList.add('dark');
}
```

**Logique :**
- Si `savedTheme === 'dark'` : L'utilisateur a déjà choisi le mode sombre
- OU
- Si `!savedTheme` (jamais défini) ET `prefersDark` (préférence système)
- ALORS : Activer le mode sombre

**4. Écouter les clics**

```javascript
darkModeToggle.addEventListener('click', () => {
    htmlElement.classList.toggle('dark');
```

- `addEventListener('click', ...)` : Exécute la fonction au clic
- `classList.toggle('dark')` : Ajoute/retire la classe `dark`

**5. Sauvegarder la préférence**

```javascript
if (htmlElement.classList.contains('dark')) {
    localStorage.setItem('theme', 'dark');
} else {
    localStorage.setItem('theme', 'light');
}
```

- `classList.contains('dark')` : Vérifie si la classe existe
- `localStorage.setItem()` : Sauvegarde dans le navigateur
- **Persistant** : Même après fermeture du navigateur !

**6. Initialiser au chargement**

```javascript
document.addEventListener('DOMContentLoaded', initDarkMode);
```

- `DOMContentLoaded` : Événement déclenché quand le HTML est chargé
- Lance `initDarkMode()` automatiquement

### Pourquoi le script est dans chaque template ?

Parce que chaque page doit initialiser le dark mode indépendamment.

**Alternative** : Mettre le script dans `base.html.twig` dans un bloc `{% block javascripts %}` pour le réutiliser partout.

---

## 7. MODIFICATION DU CONTRÔLEUR D'INSCRIPTION

**Fichier : `src/Controller/RegistrationController.php`**

### Avant

```php
return $security->login($user, AppCustomAuthAuthenticator::class, 'main');
```

- Connecte automatiquement l'utilisateur après inscription
- Le redirige directement vers la page d'accueil

### Après

```php
// Afficher un message de succès
$this->addFlash('success', 'Votre compte a été créé avec succès ! Connectez-vous maintenant.');

// Rediriger vers la page de connexion
return $this->redirectToRoute('app_login');
```

**Pourquoi ce changement ?**

1. **Meilleure expérience utilisateur** : L'utilisateur sait qu'il doit se connecter
2. **Sécurité** : Vérifie que l'utilisateur connaît bien son mot de passe
3. **Cohérence** : Flux inscription → login → accueil

### Comment fonctionne addFlash() ?

```php
$this->addFlash('success', 'Message');
```

1. Symfony stocke le message en **session** avec la clé `success`
2. Le message est disponible sur la **prochaine page**
3. Après affichage, Symfony le **supprime automatiquement**

**Récupération dans Twig :**

```twig
{% for message in app.flashes('success') %}
    {{ message }}
{% endfor %}
```

---

## 8. COMMENT TESTER

### Étape 1 : Démarrer Docker

```bash
docker compose up -d
```

### Étape 2 : Vérifier les routes

```bash
docker exec symfony_php php bin/console debug:router | grep -E "(home|register|login)"
```

Vous devriez voir :
- `app_home` → `/`
- `app_register` → `/register`
- `app_login` → `/login`

### Étape 3 : Tester le parcours complet

1. **Ouvrir** http://localhost:8000
   - Vous devez voir la page d'accueil avec 2 boutons
   - Tester le bouton dark mode (en haut à droite)

2. **Cliquer sur "Créer un compte"**
   - Remplir le formulaire
   - Email : `test@test.com`
   - Mot de passe : `password123`
   - Cocher "Accepter les conditions"
   - Cliquer sur "S'inscrire"

3. **Redirection vers /login**
   - Vous devez voir un message vert : "Votre compte a été créé avec succès !"
   - Le formulaire de connexion est affiché

4. **Se connecter**
   - Email : `test@test.com`
   - Mot de passe : `password123`
   - Cliquer sur "Se connecter"

5. **Redirection vers /post/**
   - Si la route existe, vous êtes redirigé
   - Sinon, vous verrez une erreur 404 (normal pour l'instant)

### Étape 4 : Tester le dark mode

1. Cliquer sur le bouton soleil/lune
2. Le fond doit changer (clair ↔ sombre)
3. Recharger la page (F5)
4. Le thème doit être conservé !

### Vérifier dans le navigateur

Ouvrir les DevTools (F12) → Console :

```javascript
localStorage.getItem('theme')
```

Doit retourner `'dark'` ou `'light'`

---

## RÉCAPITULATIF DES CONCEPTS CLÉS

### Tailwind CSS

- **Utility-first** : Classes CSS réutilisables
- **Responsive** : `md:`, `lg:` pour différentes tailles d'écran
- **Dark mode** : `dark:` pour le mode sombre

### Dark Mode

- Configuration : `darkMode: 'class'`
- Activation : Ajouter `class="dark"` à `<html>`
- Persistance : `localStorage`

### Symfony

- **Routes** : `#[Route('/path', name: 'route_name')]`
- **Flash messages** : `$this->addFlash('type', 'message')`
- **Redirection** : `$this->redirectToRoute('route_name')`
- **Utilisateur** : `$this->getUser()`

### Sécurité

- **CSRF Token** : Protection contre les attaques
- **Password Hasher** : Ne jamais stocker en clair
- **Remember Me** : Session persistante

---

## PROCHAINES ÉTAPES

Maintenant que vous avez l'inscription et la connexion, vous pouvez créer :

1. **PostController** : Gérer les publications
2. **Post/index.html.twig** : Afficher la liste des posts
3. **Post/new.html.twig** : Formulaire de création
4. **Sécurité** : Bloquer l'accès si pas connecté

Bonne continuation ! 🚀

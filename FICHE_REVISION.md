# 📚 Fiche de Révision - MiniRSN

## 📋 Informations générales

**Nom du projet** : MiniRSN (Mini Réseau Social)
**Framework** : Symfony 7.3
**Langage** : PHP 8.2
**Base de données** : MySQL 8.0
**Environnement** : Docker + Docker Compose
**Frontend** : Twig, Tailwind CSS, Stimulus

---

## 🏗️ Architecture du projet

### Structure des dossiers principaux

```
Sprint-MiniRSN/
├── assets/              # JS, CSS (Stimulus controllers, Tailwind)
├── config/              # Configuration Symfony (routes, services, packages)
├── docker/              # Configuration Docker (PHP/Apache)
├── dumps/               # Exports SQL de la base de données
├── migrations/          # Migrations Doctrine (création/modification de tables)
├── public/              # Point d'entrée web (index.php, uploads)
├── src/
│   ├── Controller/      # Contrôleurs (logique métier)
│   ├── Entity/          # Entités Doctrine (modèles de données)
│   ├── Form/            # Formulaires Symfony
│   ├── Repository/      # Repositories (requêtes en base)
│   ├── Security/        # Authentification (Authenticator)
│   └── Service/         # Services métier
├── templates/           # Templates Twig (vues)
├── docker-compose.yml   # Configuration des conteneurs Docker
└── .env                 # Variables d'environnement
```

---

## 🗄️ Base de données

### Tables principales

#### **user**
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT | Clé primaire |
| email | VARCHAR(180) | Email unique (login) |
| roles | JSON | Rôles de l'utilisateur |
| password | VARCHAR(255) | Mot de passe hashé |
| name | VARCHAR(255) | Nom affiché |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

**Contraintes** :
- Mot de passe : min 8 caractères, 1 majuscule, 1 caractère spécial
- Email unique
- Rôles : ROLE_USER (par défaut), ROLE_ADMIN

#### **post**
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT | Clé primaire |
| user_id | INT | FK vers user |
| content | TEXT | Contenu du post |
| image | VARCHAR(255) | Nom du fichier image (nullable) |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

**Relations** :
- `ManyToOne` avec User
- `OneToMany` avec Comment (cascade remove)

#### **comment**
| Colonne | Type | Description |
|---------|------|-------------|
| id | INT | Clé primaire |
| user_id | INT | FK vers user |
| post_id | INT | FK vers post (cascade delete) |
| text | TEXT | Contenu du commentaire |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

**Relations** :
- `ManyToOne` avec User
- `ManyToOne` avec Post (onDelete: CASCADE)

#### **messenger_messages**
Gère la file d'attente des messages (emails, notifications)

---

## 🔐 Authentification & Sécurité

### Système d'authentification

**Fichier** : [src/Security/AppCustomAuthAuthenticator.php](src/Security/AppCustomAuthAuthenticator.php)

**Mécanisme** :
1. Formulaire de login avec email + password
2. Vérification du token CSRF
3. Hashage avec `password_hash()` (bcrypt)
4. Redirection selon le rôle :
   - ROLE_ADMIN → `/admin`
   - ROLE_USER → `/post/`

### Configuration de sécurité

**Fichier** : [config/packages/security.yaml](config/packages/security.yaml)

```yaml
password_hashers:
    Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

firewalls:
    main:
        lazy: true
        provider: app_user_provider
        custom_authenticator: App\Security\AppCustomAuthAuthenticator
        logout:
            path: app_logout
            target: app_login
```

### Protection CSRF

**Implémentation** :
- Token CSRF dans tous les formulaires
- Validation côté serveur
- Controller Stimulus : `csrf_protection_controller.js`

---

## 🎯 Fonctionnalités principales

### 1. Gestion des utilisateurs

**Inscription** : [src/Controller/RegistrationController.php](src/Controller/RegistrationController.php)
- Formulaire de création de compte
- Validation email unique
- Hashage du mot de passe
- Rôle ROLE_USER par défaut

**Connexion** : [src/Controller/SecurityController.php](src/Controller/SecurityController.php)
- Formulaire de login
- Authentification via AppCustomAuthAuthenticator
- Gestion des erreurs (bad credentials)

### 2. Gestion des posts

**Fichier** : [src/Controller/PostController.php](src/Controller/PostController.php)

**Routes** :
- `GET /post/` - Liste tous les posts (index)
- `GET /post/new` - Formulaire de création
- `POST /post/new` - Création d'un post
- `GET /post/{id}` - Affichage d'un post
- `GET /post/{id}/edit` - Formulaire d'édition
- `POST /post/{id}/edit` - Modification d'un post
- `POST /post/{id}` - Suppression d'un post

**Fonctionnalités** :
- Upload d'images (stockage dans `public/uploads/posts/`)
- Validation des droits (seul l'auteur peut modifier/supprimer)
- Envoi automatique d'email aux autres utilisateurs

### 3. Système de commentaires

**Relations** :
- Un post peut avoir plusieurs commentaires
- Un commentaire appartient à un user et un post
- Suppression en cascade (si post supprimé → commentaires supprimés)

### 4. Dashboard administrateur

**Fichier** : [src/Controller/AdminController.php](src/Controller/AdminController.php)

**Routes** :
- `/admin/` - Dashboard principal
- `/admin/users` - Liste des utilisateurs
- `/admin/users/{id}/edit` - Édition utilisateur
- `/admin/users/{id}/delete` - Suppression utilisateur
- `/admin/posts` - Modération des posts
- `/admin/post/{id}/delete` - Suppression de post
- `/admin/comments` - Modération des commentaires
- `/admin/comment/{id}/delete` - Suppression de commentaire

**Sécurité** : Accessible uniquement avec ROLE_ADMIN (`#[IsGranted('ROLE_ADMIN')]`)

### 5. Notifications par email

**Fichier** : [src/Service/PostNotificationService.php](src/Service/PostNotificationService.php)

**Fonctionnement** :
1. Lors de la création d'un post → `notifyNewPost()`
2. Récupère tous les utilisateurs sauf l'auteur
3. Génère un email HTML avec Twig
4. Envoie via Symfony Mailer → MailHog (dev)

**Template email** : [templates/emails/new_post_notification.html.twig](templates/emails/new_post_notification.html.twig)
- Design avec Tailwind CSS
- Informations du post (contenu, auteur, date)
- Bouton "Voir le post"

**Configuration** :
- Envoi synchrone (immédiat) via Messenger
- SMTP : MailHog sur port 1025
- Interface web MailHog : http://localhost:8025

---

## 🐳 Docker

### Conteneurs

**docker-compose.yml** contient 4 services :

1. **php** (symfony_php)
   - Image : PHP 8.2 + Apache
   - Port : 8000
   - Volumes : projet monté dans `/var/www/html`

2. **db** (minirsn_db)
   - Image : MySQL 8.0
   - Port : 3307 (externe) → 3306 (interne)
   - Variables : root/root, base minirsn_db

3. **phpmyadmin** (symfony_phpmyadmin)
   - Port : 8080
   - Interface web MySQL

4. **mailhog** (symfony_mailhog)
   - Port SMTP : 1025
   - Interface web : 8025

### Commandes Docker utiles

```bash
# Démarrer
docker-compose up -d

# Arrêter
docker-compose down

# Logs
docker-compose logs -f

# Exécuter une commande dans un conteneur
docker exec symfony_php php bin/console [commande]

# Accéder au shell
docker exec -it symfony_php bash
```

---

## 🎨 Frontend

### Technologies

- **Twig** : Moteur de templates
- **Tailwind CSS** : Framework CSS utility-first
- **Stimulus** : Framework JS léger pour interactions
- **Turbo** : Navigation rapide (SPA-like)

### Templates principaux

```
templates/
├── base.html.twig              # Layout principal
├── partials/
│   └── _navbar.html.twig       # Barre de navigation
├── security/
│   ├── login.html.twig         # Page de connexion
│   └── register.html.twig      # Page d'inscription
├── post/
│   ├── index.html.twig         # Liste des posts
│   ├── new.html.twig           # Créer un post
│   ├── show.html.twig          # Détail d'un post
│   └── edit.html.twig          # Éditer un post
├── admin/
│   ├── index.html.twig         # Dashboard admin
│   ├── users.html.twig         # Liste users
│   ├── posts.html.twig         # Liste posts
│   └── comments.html.twig      # Liste commentaires
└── emails/
    └── new_post_notification.html.twig  # Email
```

### Stimulus Controllers

**assets/controllers/** :
- `csrf_protection_controller.js` : Gestion CSRF automatique
- Autres controllers Symfony UX (possibles extensions)

### Dark Mode

Implémenté avec Tailwind :
- Toggle dans la navbar
- Stockage de la préférence en localStorage
- Classes `dark:` pour styles alternatifs

---

## 🔧 Configuration importante

### Variables d'environnement (.env)

```env
APP_ENV=dev
DATABASE_URL="mysql://root:root@db:3306/minirsn_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://mailhog:1025
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

### Messenger (emails)

**Fichier** : [config/packages/messenger.yaml](config/packages/messenger.yaml)

```yaml
framework:
    messenger:
        transports:
            sync: 'sync://'           # Transport synchrone
        routing:
            Symfony\Component\Mailer\Messenger\SendEmailMessage: sync
```

**Important** : Les emails sont envoyés de manière **synchrone** (immédiate) pour faciliter le développement.

---

## 📝 Commandes Symfony importantes

### Cache

```bash
# Vider le cache
docker exec symfony_php php bin/console cache:clear

# Réchauffer le cache
docker exec symfony_php php bin/console cache:warmup
```

### Base de données

```bash
# Créer une migration
docker exec symfony_php php bin/console make:migration

# Exécuter les migrations
docker exec symfony_php php bin/console doctrine:migrations:migrate

# Voir l'état des migrations
docker exec symfony_php php bin/console doctrine:migrations:status
```

### Génération de code

```bash
# Créer une entité
docker exec symfony_php php bin/console make:entity

# Créer un contrôleur
docker exec symfony_php php bin/console make:controller

# Créer un formulaire
docker exec symfony_php php bin/console make:form
```

### Debug

```bash
# Lister les routes
docker exec symfony_php php bin/console debug:router

# Voir les services disponibles
docker exec symfony_php php bin/console debug:container

# Vérifier la config
docker exec symfony_php php bin/console debug:config
```

---

## 🔍 Points techniques importants

### 1. Relations Doctrine

**User → Post** : OneToMany avec `orphanRemoval: true`
```php
#[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user', orphanRemoval: true)]
private Collection $posts;
```

**Post → Comment** : OneToMany avec `cascade: ['remove']`
```php
#[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['remove'])]
private Collection $comments;
```

**Comment → Post** : ManyToOne avec `onDelete: 'CASCADE'`
```php
#[ORM\ManyToOne(inversedBy: 'comments')]
#[ORM\JoinColumn(onDelete: 'CASCADE')]
private ?Post $post = null;
```

### 2. Upload de fichiers

**Méthode** :
1. Formulaire avec `FileType`
2. Récupération du fichier : `$form->get('imageFile')->getData()`
3. Nettoyage du nom : `preg_replace('/[^A-Za-z0-9-_]/', '', $originalFilename)`
4. Ajout d'un ID unique : `$safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension()`
5. Déplacement : `$imageFile->move('public/uploads/posts', $newFilename)`

### 3. Validation des contraintes

**User** :
```php
#[Assert\Regex(
    pattern: '/^(?=.*[A-Z])(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?])(?=.{8,})/',
    message: 'Le mot de passe doit contenir...'
)]
```

### 4. Sécurité CSRF

**Dans les formulaires Twig** :
```twig
<input type="hidden" name="_token" value="{{ csrf_token('delete' ~ post.id) }}">
```

**Dans les contrôleurs** :
```php
$this->isCsrfTokenValid('delete'.$post->getId(), $request->getPayload()->getString('_token'))
```

### 5. Flash Messages

```php
// Dans le contrôleur
$this->addFlash('success', 'Post créé avec succès !');
$this->addFlash('error', 'Une erreur est survenue');

// Dans le template
{% for message in app.flashes('success') %}
    <div class="alert-success">{{ message }}</div>
{% endfor %}
```

---

## 🧪 Données de test

### Comptes utilisateurs

**Admins** :
- admin@admin.com (ROLE_ADMIN)
- admin@gmail.com (ROLE_ADMIN)

**Users** :
- test@test.com (ROLE_USER)
- test2@test2.com (ROLE_USER)

**Note** : Mots de passe hashés dans la base. Créer de nouveaux comptes via `/register`

### Données initiales

- 6 posts de test
- 2 commentaires
- Images uploadées dans `public/uploads/posts/`

---

## 🐛 Dépannage fréquent

### Problème : Cache corrompu
```bash
docker exec symfony_php rm -rf var/cache/*
docker exec symfony_php php bin/console cache:clear
```

### Problème : Emails non envoyés
- Vérifier MailHog : `docker ps | grep mailhog`
- Vérifier config : `.env` → `MAILER_DSN=smtp://mailhog:1025`
- Vérifier Messenger : `config/packages/messenger.yaml` → routing sync

### Problème : Base de données non accessible
- Attendre 10-15 secondes après `docker-compose up`
- Vérifier conteneur : `docker ps`
- Vérifier hostname dans `.env` : `@db` (pas `localhost`)

### Problème : Permissions fichiers
```bash
docker exec symfony_php chmod -R 777 var/
docker exec symfony_php chmod -R 777 public/uploads/
```

---

## 📊 Points clés pour révision

### Concepts Symfony à maîtriser

1. **MVC** : Model (Entity) - View (Twig) - Controller
2. **Doctrine ORM** : Mapping objet-relationnel, relations
3. **Formulaires** : FormType, validation, CSRF
4. **Sécurité** : Authenticator, firewalls, voters
5. **Services** : Injection de dépendances, autowiring
6. **Events** : Listeners, subscribers
7. **Routing** : Annotations/Attributes
8. **Twig** : Syntaxe, filters, functions, inheritance

### Architecture à retenir

```
Request → Router → Controller → Service/Repository → Entity
                              ↓
                           Response ← Twig Template
```

### Commandes essentielles

| Action | Commande |
|--------|----------|
| Démarrer Docker | `docker-compose up -d` |
| Cache clear | `php bin/console cache:clear` |
| Migration | `php bin/console make:migration` |
| Migrer | `php bin/console doctrine:migrations:migrate` |
| Routes | `php bin/console debug:router` |
| Export DB | `mysqldump -u root -proot minirsn_db > dump.sql` |
| Import DB | `mysql -u root -proot minirsn_db < dump.sql` |

---

## 🎓 Concepts avancés implémentés

### 1. Messenger Component
Gestion asynchrone des tâches (emails)

### 2. Events et Listeners
Possibilité d'ajouter des listeners sur les events Doctrine

### 3. Service Container
Injection automatique des dépendances (autowiring)

### 4. Repository Pattern
Séparation de la logique de récupération des données

### 5. Form Component
Génération et validation automatique des formulaires

### 6. Security Component
Authentification, autorisation, hashage

---

## 📚 Ressources

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Twig Documentation](https://twig.symfony.com/)

---

**Date de dernière mise à jour** : 16 octobre 2025
**Version Symfony** : 7.3
**Version PHP** : 8.2

# FICHE DE RÉVISION - Projet Mini RSN (Réseau Social)

## 1. Configuration de l'environnement Docker

### Fichier docker-compose.yml
Le projet utilise 4 services Docker :

```yaml
services:
  php:        # Serveur Apache + PHP
  db:         # MySQL 8.0
  phpmyadmin: # Interface de gestion BDD
  mailhog:    # Interception des emails
```

### Ports exposés
- **8000** : Application Symfony
- **8080** : phpMyAdmin
- **3307** : MySQL (externe)
- **8025** : Interface MailHog
- **1025** : SMTP MailHog

### Commandes Docker essentielles
```bash
# Démarrer les conteneurs
docker compose up -d

# Arrêter les conteneurs
docker compose down

# Recréer complètement (avec suppression des volumes)
docker compose down -v
docker compose up -d

# Voir les conteneurs actifs
docker ps

# Exécuter une commande dans un conteneur
docker exec symfony_php php bin/console [commande]
docker exec minirsn_db mysql -uroot -proot
```

---

## 2. Configuration de la base de données

### Fichier .env
```env
DATABASE_URL="mysql://root:root@db:3306/minirsn_db?serverVersion=8.0&charset=utf8mb4"
```

**Points importants** :
- Host : `db` (nom du service Docker, PAS `localhost`)
- User : `root`
- Password : `root`
- Database : `minirsn_db`
- Port : `3306` (port interne au conteneur)

### phpMyAdmin
- URL : http://localhost:8080
- Utilisateur : `root`
- Mot de passe : `root`

---

## 3. Entities (Modèle de données)

### Entity User
**Commande** : `php bin/console make:user`

```php
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id;
    private ?string $email;           // Identifiant unique
    private array $roles;             // Rôles de sécurité
    private ?string $password;        // Mot de passe hashé
    private Collection $posts;        // Posts de l'utilisateur
    private Collection $comments;     // Commentaires de l'utilisateur
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
}
```

**Relations** :
- `OneToMany` avec `Post` (un User a plusieurs Posts)
- `OneToMany` avec `Comment` (un User a plusieurs Comments)

---

### Entity Post
**Commande** : `php bin/console make:entity Post`

```php
class Post
{
    private ?int $id;
    private ?string $content;         // Contenu TEXT
    private ?string $image;           // Chemin de l'image (nullable)
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?User $user;              // Auteur du post
    private Collection $comments;     // Commentaires sur le post
}
```

**Relations** :
- `ManyToOne` avec `User` (plusieurs Posts pour un User)
- `OneToMany` avec `Comment` (un Post a plusieurs Comments)

---

### Entity Comment
**Commande** : `php bin/console make:entity Comment`

```php
class Comment
{
    private ?int $id;
    private ?string $text;            // Contenu TEXT
    private ?\DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;
    private ?User $user;              // Auteur du commentaire
    private ?Post $post;              // Post commenté
}
```

**Relations** :
- `ManyToOne` avec `User` (plusieurs Comments pour un User)
- `ManyToOne` avec `Post` (plusieurs Comments pour un Post)

---

## 4. Système d'authentification

### make:auth - Création du système de connexion
**Commande** : `php bin/console make:auth`

**Ce qui a été généré** :
1. `SecurityController.php` - Contrôleur de connexion/déconnexion
2. `AppCustomAuthAuthenticator.php` - Authenticator personnalisé
3. `templates/security/login.html.twig` - Page de connexion
4. Configuration dans `config/packages/security.yaml`

### Fichier security.yaml
```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email    # Connexion avec email

    firewalls:
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\AppCustomAuthAuthenticator
            logout:
                path: app_logout
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800   # 7 jours
```

**Routes générées** :
- `/login` : Page de connexion
- `/logout` : Déconnexion

---

### make:registration-form - Création de l'inscription
**Commande** : `php bin/console make:registration-form`

**Ce qui a été généré** :
1. `RegistrationController.php` - Gestion de l'inscription
2. `templates/registration/register.html.twig` - Formulaire d'inscription
3. `RegistrationFormType.php` - Type de formulaire

**Fonctionnalités** :
- Validation de l'unicité de l'email
- Hashage automatique du mot de passe
- Connexion automatique après inscription

**Route** :
- `/register` : Page d'inscription

---

## 5. Migrations Doctrine

### Commandes de migration
```bash
# Créer une migration (après modification d'entity)
docker exec symfony_php php bin/console make:migration

# Exécuter les migrations
docker exec symfony_php php bin/console doctrine:migrations:migrate

# Voir l'état des migrations
docker exec symfony_php php bin/console doctrine:migrations:status

# Annuler la dernière migration
docker exec symfony_php php bin/console doctrine:migrations:migrate prev
```

### Fichiers de migration créés
- `Version20251014141947.php` - Première migration
- `Version20251014151223.php` - Deuxième migration

**Tables créées** :
- `user` - Utilisateurs
- `post` - Publications
- `comment` - Commentaires
- `doctrine_migration_versions` - Historique des migrations
- `messenger_messages` - Messages Symfony

---

## 6. Relations Doctrine

### Types de relations

#### OneToMany (Un vers Plusieurs)
```php
// Dans User.php
#[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user', orphanRemoval: true)]
private Collection $posts;
```
- Un User a plusieurs Posts
- `mappedBy` : indique le champ dans l'entité cible
- `orphanRemoval: true` : supprime les Posts si le User est supprimé

#### ManyToOne (Plusieurs vers Un)
```php
// Dans Post.php
#[ORM\ManyToOne(inversedBy: 'posts')]
#[ORM\JoinColumn(nullable: false)]
private ?User $user;
```
- Plusieurs Posts appartiennent à un User
- `inversedBy` : indique la collection dans l'entité parente
- `nullable: false` : un Post DOIT avoir un User

### Schéma des relations
```
User (1) ----< (N) Post
  |                  |
  |                  |
  +-----< (N) Comment >-----+
```

---

## 7. Commandes Symfony Make essentielles

### Entities
```bash
# Créer une entity
php bin/console make:entity NomEntity

# Modifier une entity existante
php bin/console make:entity NomEntity

# Créer une entity User (avec sécurité)
php bin/console make:user
```

### Sécurité
```bash
# Créer le système d'authentification
php bin/console make:auth

# Créer le formulaire d'inscription
php bin/console make:registration-form
```

### Controllers
```bash
# Créer un controller
php bin/console make:controller NomController
```

### Formulaires
```bash
# Créer un formulaire
php bin/console make:form
```

---

## 8. Structure du projet

```
Sprint-MiniRSN/
├── config/
│   ├── packages/
│   │   └── security.yaml        # Config sécurité
│   └── bundles.php               # Bundles activés
├── docker/
│   └── php/
│       └── Dockerfile            # Image PHP personnalisée
├── migrations/                   # Fichiers de migration
│   ├── Version20251014141947.php
│   └── Version20251014151223.php
├── src/
│   ├── Controller/
│   │   ├── RegistrationController.php
│   │   └── SecurityController.php
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Post.php
│   │   └── Comment.php
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── PostRepository.php
│   │   └── CommentRepository.php
│   └── Security/
│       └── AppCustomAuthAuthenticator.php
├── templates/
│   ├── registration/
│   │   └── register.html.twig
│   └── security/
│       └── login.html.twig
├── .env                          # Variables d'environnement
├── docker-compose.yml            # Configuration Docker
└── composer.json                 # Dépendances PHP
```

---

## 9. Workflow de développement typique

### 1. Créer une nouvelle Entity
```bash
docker exec symfony_php php bin/console make:entity NomEntity
# Ajouter les propriétés interactivement
```

### 2. Créer la migration
```bash
docker exec symfony_php php bin/console make:migration
```

### 3. Exécuter la migration
```bash
docker exec symfony_php php bin/console doctrine:migrations:migrate
```

### 4. Créer un Controller
```bash
docker exec symfony_php php bin/console make:controller NomController
```

### 5. Vérifier dans phpMyAdmin
- Ouvrir http://localhost:8080
- Vérifier que la table existe avec les bonnes colonnes

---

## 10. Troubleshooting courant

### La base de données n'apparaît pas dans phpMyAdmin
**Solution** : Supprimer les volumes et recréer
```bash
docker compose down -v
docker compose up -d
docker exec symfony_php php bin/console doctrine:migrations:migrate
```

### Erreur "MYSQL_USER cannot be root"
**Solution** : Retirer `MYSQL_USER` et `MYSQL_PASSWORD` du docker-compose.yml
```yaml
# CORRECT
environment:
  MYSQL_ROOT_PASSWORD: root
  MYSQL_DATABASE: minirsn_db
```

### Bundles manquants
**Solution** : Installer le bundle ou le retirer de `config/bundles.php`
```bash
docker exec symfony_php composer require nom/du-bundle
```

### Les migrations ne s'appliquent pas
```bash
# Vérifier l'état
docker exec symfony_php php bin/console doctrine:migrations:status

# Forcer la migration
docker exec symfony_php php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 11. Points clés à retenir

### Docker
- Toujours utiliser le nom du service (`db`) pas `localhost`
- Le port 3307 est pour l'accès externe, 3306 pour interne

### Doctrine
- Toujours faire une migration après modification d'entity
- Les relations bidirectionnelles nécessitent `mappedBy` et `inversedBy`

### Sécurité
- `make:user` crée l'entity User avec les interfaces de sécurité
- `make:auth` crée tout le système de connexion
- `make:registration-form` crée le formulaire d'inscription
- Les mots de passe sont automatiquement hashés

### Commandes Docker
- `docker exec symfony_php` pour exécuter des commandes Symfony
- `docker compose down -v` supprime TOUT (y compris les données)

---

## 12. MailHog (Emails de développement)

### Configuration
```env
MAILER_DSN=smtp://mailhog:1025
```

### Interface web
- URL : http://localhost:8025
- Tous les emails envoyés par l'application sont interceptés et visibles ici

### Utilisation
```php
// Les emails sont automatiquement interceptés
$mailer->send($email);
```

---

## Résumé des commandes les plus utilisées

```bash
# Démarrer le projet
docker compose up -d

# Créer une entity
docker exec symfony_php php bin/console make:entity

# Créer une migration
docker exec symfony_php php bin/console make:migration

# Appliquer les migrations
docker exec symfony_php php bin/console doctrine:migrations:migrate

# Créer un controller
docker exec symfony_php php bin/console make:controller

# Voir les routes
docker exec symfony_php php bin/console debug:router

# Vider le cache
docker exec symfony_php php bin/console cache:clear

# Arrêter le projet
docker compose down
```

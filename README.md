# MiniRSN - Mini Réseau Social

Une application de réseau social minimaliste développée avec Symfony 7.3, permettant aux utilisateurs de créer des posts, commenter et interagir.

## Fonctionnalités

- Authentification des utilisateurs (inscription/connexion)
- Création et gestion de posts avec upload d'images
- Système de commentaires
- Dashboard administrateur pour la modération
- Notifications par email (MailHog) lors de la création d'un post
- Mode sombre/clair (Dark mode)
- Interface responsive avec Tailwind CSS

## Prérequis

- Docker et Docker Compose
- PHP 8.2 ou supérieur
- Composer
- Node.js et NPM (pour les assets)

## Installation

### 1. Cloner le projet

```bash
git clone <votre-repo>
cd Sprint-MiniRSN
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configuration de l'environnement

Copiez le fichier `.env` si nécessaire (il devrait déjà être présent) :

```bash
# Le fichier .env est déjà configuré pour Docker
# Vérifiez que les variables suivantes sont présentes :
DATABASE_URL="mysql://root:root@db:3306/minirsn_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://mailhog:1025
```

### 4. Démarrer Docker

```bash
docker-compose up -d
```

Cela va démarrer :
- **PHP + Apache** sur `http://localhost:8000`
- **MySQL** sur le port `3307`
- **phpMyAdmin** sur `http://localhost:8080`
- **MailHog** sur `http://localhost:8025` (interface web pour les emails)

### 5. Importer la base de données

```bash
# Méthode 1 : Via la ligne de commande
docker exec -i minirsn_db mysql -u root -proot minirsn_db < dumps/dump.sql

# Méthode 2 : Via phpMyAdmin
# 1. Allez sur http://localhost:8080
# 2. Connectez-vous (user: root, password: root)
# 3. Sélectionnez la base "minirsn_db"
# 4. Importez le fichier dumps/dump.sql
```

### 6. Compiler les assets

```bash
npm run build
# ou pour le mode watch :
npm run watch
```

### 7. Vider le cache Symfony (optionnel)

```bash
docker exec symfony_php php bin/console cache:clear
```

## Accéder à l'application

- **Application** : http://localhost:8000
- **phpMyAdmin** : http://localhost:8080
- **MailHog** (emails de test) : http://localhost:8025

## Comptes de test

Le dump SQL contient déjà des comptes de test :

### Utilisateurs normaux
- **Email** : `test@test.com`
- **Mot de passe** : `Test@1234` (ou le mot de passe défini lors de l'inscription)
- **Nom** : Test

- **Email** : `test2@test2.com`
- **Nom** : test 2

### Administrateurs
- **Email** : `admin@admin.com`
- **Mot de passe** : `Admin@1234` (ou le mot de passe défini lors de l'inscription)
- **Nom** : AdminUser

- **Email** : `admin@gmail.com`
- **Nom** : AdminUser

> **Note** : Les mots de passe sont hashés dans la base de données. Si vous ne connaissez pas les mots de passe, vous devrez en créer de nouveaux via la page d'inscription.

## Fonctionnalités principales

### Posts
- Créer un post avec ou sans image
- Modifier ses propres posts
- Supprimer ses propres posts
- Voir tous les posts (fil d'actualité)

### Commentaires
- Commenter les posts
- Voir les commentaires d'un post

### Notifications par email (MailHog)
- Les utilisateurs reçoivent un email lorsqu'un nouveau post est créé
- Les emails sont visibles dans MailHog (http://localhost:8025)
- L'auteur du post ne reçoit pas de notification
- Voir la section "Configuration MailHog" ci-dessous pour plus de détails

### Administration
- Accès au dashboard admin : http://localhost:8000/admin
- Gestion des utilisateurs (voir, supprimer)
- Modération des posts (voir, supprimer)
- Modération des commentaires (voir, supprimer)

## Structure du projet

```
Sprint-MiniRSN/
├── assets/              # Assets JS/CSS (Stimulus, Tailwind)
├── config/              # Configuration Symfony
├── docker/              # Configuration Docker (PHP, Apache)
├── dumps/               # Dumps SQL de la base de données
├── migrations/          # Migrations Doctrine
├── public/              # Point d'entrée web
│   └── uploads/posts/   # Images uploadées des posts
├── src/
│   ├── Controller/      # Contrôleurs
│   ├── Entity/          # Entités Doctrine (User, Post, Comment)
│   ├── Form/            # Formulaires Symfony
│   ├── Repository/      # Repositories Doctrine
│   ├── Security/        # Authentification
│   └── Service/         # Services (PostNotificationService)
├── templates/           # Templates Twig
│   ├── admin/           # Templates admin
│   ├── emails/          # Templates d'emails
│   ├── post/            # Templates posts
│   └── security/        # Templates auth
├── docker-compose.yml   # Configuration Docker Compose
└── README.md            # Ce fichier
```

## Technologies utilisées

- **Backend** : Symfony 7.3, PHP 8.2
- **Base de données** : MySQL 8.0
- **Frontend** : Twig, Tailwind CSS, Stimulus
- **Email** : Symfony Mailer + MailHog (développement)
- **Conteneurisation** : Docker, Docker Compose

## Commandes utiles

### Symfony

```bash
# Vider le cache
docker exec symfony_php php bin/console cache:clear

# Créer une migration
docker exec symfony_php php bin/console make:migration

# Exécuter les migrations
docker exec symfony_php php bin/console doctrine:migrations:migrate

# Créer un nouvel utilisateur admin
docker exec symfony_php php bin/console make:user
```

### Docker

```bash
# Démarrer les conteneurs
docker-compose up -d

# Arrêter les conteneurs
docker-compose down

# Voir les logs
docker-compose logs -f

# Voir les conteneurs en cours
docker ps
```

### Base de données

#### Exporter la base de données

Pour sauvegarder votre base de données actuelle :

**Méthode 1 : Export avec nom de fichier horodaté (recommandé)**
```bash
# Sur Windows (PowerShell)
docker exec minirsn_db mysqldump -u root -proot minirsn_db > dumps/dump_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql

# Sur Linux/Mac
docker exec minirsn_db mysqldump -u root -proot minirsn_db > dumps/dump_$(date +%Y%m%d_%H%M%S).sql
```

**Méthode 2 : Export simple**
```bash
# Remplacer le dump existant
docker exec minirsn_db sh -c "mysqldump -u root -proot minirsn_db" > dumps/dump.sql
```

**Explication des commandes :**
- `docker exec minirsn_db` : Exécute une commande dans le conteneur MySQL
- `mysqldump` : Outil MySQL pour exporter les données
- `-u root` : Nom d'utilisateur MySQL
- `-proot` : Mot de passe (attention, pas d'espace après `-p`)
- `minirsn_db` : Nom de la base de données à exporter
- `> dumps/dump.sql` : Redirige la sortie vers un fichier

#### Importer la base de données

**Méthode 1 : Via la ligne de commande (recommandé)**
```bash
# Importer le dump principal
docker exec -i minirsn_db mysql -u root -proot minirsn_db < dumps/dump.sql

# Ou importer un dump spécifique avec horodatage
docker exec -i minirsn_db mysql -u root -proot minirsn_db < dumps/dump_20251016_153045.sql
```

**Explication des commandes :**
- `docker exec -i` : `-i` garde le flux d'entrée ouvert pour envoyer le fichier SQL
- `mysql` : Client MySQL pour exécuter des commandes SQL
- `minirsn_db` : Base de données cible
- `< dumps/dump.sql` : Lit le fichier SQL et l'envoie au client MySQL

**Méthode 2 : Via phpMyAdmin**
1. Ouvrez phpMyAdmin : http://localhost:8080
2. Connectez-vous avec :
   - **Utilisateur** : `root`
   - **Mot de passe** : `root`
3. Sélectionnez la base `minirsn_db` dans le menu de gauche
4. Cliquez sur l'onglet "Importer"
5. Cliquez sur "Choisir un fichier" et sélectionnez `dumps/dump.sql`
6. Faites défiler vers le bas et cliquez sur "Exécuter"

**Méthode 3 : Réinitialiser complètement la base**
```bash
# 1. Supprimer et recréer la base (ATTENTION : perte de données)
docker exec -i minirsn_db mysql -u root -proot -e "DROP DATABASE IF EXISTS minirsn_db; CREATE DATABASE minirsn_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importer le dump
docker exec -i minirsn_db mysql -u root -proot minirsn_db < dumps/dump.sql
```

#### Vérifier l'import

Après l'import, vérifiez que tout s'est bien passé :

```bash
# Lister les tables
docker exec -i minirsn_db mysql -u root -proot minirsn_db -e "SHOW TABLES;"

# Compter les utilisateurs
docker exec -i minirsn_db mysql -u root -proot minirsn_db -e "SELECT COUNT(*) as total_users FROM user;"

# Compter les posts
docker exec -i minirsn_db mysql -u root -proot minirsn_db -e "SELECT COUNT(*) as total_posts FROM post;"
```

#### Accéder à MySQL en ligne de commande

Pour exécuter des requêtes SQL manuellement :

```bash
# Entrer dans le shell MySQL
docker exec -it minirsn_db mysql -u root -proot minirsn_db

# Une fois connecté, vous pouvez exécuter des requêtes :
# mysql> SHOW TABLES;
# mysql> SELECT * FROM user;
# mysql> exit;
```

## Configuration Docker

### Qu'est-ce que Docker ?

**Docker** est une plateforme de conteneurisation qui permet d'empaqueter une application et toutes ses dépendances dans des conteneurs isolés. Cela garantit que l'application fonctionne de la même manière sur tous les environnements (développement, test, production).

**Avantages** :
- ✅ **Isolation** : Chaque service tourne dans son propre conteneur
- ✅ **Portabilité** : Fonctionne identiquement sur Windows, Mac, Linux
- ✅ **Simplicité** : Un seul fichier (`docker-compose.yml`) pour tout configurer
- ✅ **Reproductibilité** : Même environnement pour toute l'équipe

### Architecture Docker du projet

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Host                          │
│                                                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │   PHP 8.3    │  │  MySQL 8.0   │  │  phpMyAdmin  │ │
│  │   + Apache   │  │              │  │              │ │
│  │ symfony_php  │  │  minirsn_db  │  │   :8080      │ │
│  │   :8000      │  │   :3307      │  │              │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
│         │                 │                  │         │
│         └─────────────────┴──────────────────┘         │
│                           │                             │
│                  symfony_network (bridge)               │
│                           │                             │
│                  ┌────────┴─────────┐                  │
│                  │    MailHog       │                  │
│                  │  :1025 / :8025   │                  │
│                  └──────────────────┘                  │
└─────────────────────────────────────────────────────────┘
```

### Le fichier docker-compose.yml

**Emplacement** : [docker-compose.yml](docker-compose.yml)

Ce fichier orchestre tous les conteneurs et définit comment ils communiquent entre eux.

#### Structure générale

```yaml
services:      # Liste des conteneurs
  php:         # Service PHP + Apache
  db:          # Service MySQL
  phpmyadmin:  # Service phpMyAdmin
  mailhog:     # Service MailHog

networks:      # Réseaux pour la communication
  symfony_network:

volumes:       # Stockage persistant
  db_data:
```

#### Service 1 : PHP + Apache (symfony_php)

```yaml
php:
  build:
    context: ./docker/php      # Dossier contenant le Dockerfile
    dockerfile: Dockerfile     # Fichier de build
  container_name: symfony_php  # Nom du conteneur
  ports:
    - "8000:80"               # Port hôte:conteneur (localhost:8000 → Apache:80)
  volumes:
    - ./:/var/www/html        # Monte le projet dans le conteneur
  networks:
    - symfony_network         # Réseau partagé
  depends_on:
    - db                      # Attend que MySQL soit démarré
```

**Explication** :
- `build` : Construit l'image à partir d'un Dockerfile custom
- `ports: "8000:80"` : Redirige le port 8000 de votre machine vers le port 80 du conteneur Apache
- `volumes: ./:/var/www/html` : Synchronise votre code local avec le conteneur (modification en temps réel)
- `depends_on: db` : S'assure que MySQL démarre avant PHP

#### Service 2 : MySQL (minirsn_db)

```yaml
db:
  image: mysql:8.0            # Image Docker Hub officielle
  container_name: minirsn_db  # Nom du conteneur
  ports:
    - "3307:3306"             # Port externe:interne (évite conflit avec MySQL local)
  environment:
    MYSQL_ROOT_PASSWORD: root # Mot de passe root
    MYSQL_DATABASE: minirsn_db # Base créée automatiquement
  volumes:
    - db_data:/var/lib/mysql  # Volume persistant pour les données
  networks:
    - symfony_network
```

**Explication** :
- `image: mysql:8.0` : Utilise l'image MySQL 8.0 de Docker Hub (pas de build custom)
- `ports: "3307:3306"` : Port 3307 en externe pour éviter les conflits si vous avez déjà MySQL
- `environment` : Variables pour configurer MySQL au démarrage
- `volumes: db_data:/var/lib/mysql` : Stockage persistant (les données survivent au redémarrage)

#### Service 3 : phpMyAdmin (symfony_phpmyadmin)

```yaml
phpmyadmin:
  image: phpmyadmin/phpmyadmin
  container_name: symfony_phpmyadmin
  ports:
    - "8080:80"               # Interface web sur localhost:8080
  environment:
    PMA_HOST: db              # Nom du service MySQL (hostname)
    PMA_PORT: 3306            # Port MySQL interne
    MYSQL_ROOT_PASSWORD: root # Credentials
  networks:
    - symfony_network
  depends_on:
    - db
```

**Explication** :
- `PMA_HOST: db` : Utilise le nom du service comme hostname (pas d'IP, Docker résout automatiquement)
- Accessible sur http://localhost:8080

#### Service 4 : MailHog (symfony_mailhog)

```yaml
mailhog:
  image: mailhog/mailhog
  container_name: symfony_mailhog
  ports:
    - "1025:1025"             # Port SMTP pour envoyer
    - "8025:8025"             # Interface web pour consulter
  networks:
    - symfony_network
```

**Explication** :
- **Port 1025** : Serveur SMTP (l'application envoie les emails ici)
- **Port 8025** : Interface web pour visualiser les emails

#### Réseau (symfony_network)

```yaml
networks:
  symfony_network:
    driver: bridge            # Type de réseau (bridge = réseau privé)
```

**Explication** :
- Crée un réseau privé isolé pour tous les conteneurs
- Les conteneurs peuvent communiquer entre eux par leur nom (ex: `db`, `mailhog`)
- Type `bridge` : réseau virtuel sur la machine hôte

#### Volumes (db_data)

```yaml
volumes:
  db_data:                    # Volume nommé pour MySQL
```

**Explication** :
- Stockage persistant pour la base de données
- Les données survivent à l'arrêt/suppression des conteneurs
- Situé dans `/var/lib/docker/volumes/` sur votre machine

### Le Dockerfile PHP

**Emplacement** : [docker/php/Dockerfile](docker/php/Dockerfile)

Ce fichier définit **comment construire** l'image PHP personnalisée.

#### Contenu ligne par ligne

```dockerfile
# Image de base : PHP 8.3 avec Apache
FROM php:8.3-apache
```
- Utilise l'image officielle PHP 8.3 avec Apache intégré

```dockerfile
# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*
```
- `apt-get update` : Met à jour la liste des paquets disponibles
- `apt-get install -y` : Installe git, unzip, zip (nécessaires pour Composer)
- `rm -rf /var/lib/apt/lists/*` : Nettoie le cache pour réduire la taille de l'image

```dockerfile
# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql
```
- `pdo` : PHP Data Objects (interface d'accès aux bases de données)
- `pdo_mysql` : Driver MySQL pour PDO (utilisé par Doctrine)

```dockerfile
# Installer Composer (gestionnaire de dépendances)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
```
- Copie Composer depuis l'image officielle `composer:latest`
- Le place dans `/usr/bin/composer` pour l'utiliser globalement

```dockerfile
# Activer le module Apache pour les URLs propres
RUN a2enmod rewrite
```
- Active `mod_rewrite` : nécessaire pour les URLs Symfony (sans `index.php`)

```dockerfile
# Copier la configuration Apache personnalisée
COPY apache.conf /etc/apache2/sites-available/000-default.conf
```
- Copie la config Apache custom (définit le DocumentRoot, etc.)

```dockerfile
# Définir le dossier de travail
WORKDIR /var/www/html
```
- Définit `/var/www/html` comme dossier par défaut

```dockerfile
# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html
```
- Change le propriétaire du dossier pour l'utilisateur Apache (`www-data`)
- Évite les problèmes de permissions

### Configuration Apache

**Emplacement** : [docker/php/apache.conf](docker/php/apache.conf)

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/public  # Pointe vers le dossier public/ de Symfony

    <Directory /var/www/html/public>
        AllowOverride All              # Autorise .htaccess
        Require all granted            # Autorise tous les accès
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

**Points clés** :
- `DocumentRoot /var/www/html/public` : Le point d'entrée est `public/index.php`
- `AllowOverride All` : Permet à Symfony de gérer le routing via `.htaccess`

### Commandes Docker importantes

#### Gestion des conteneurs

```bash
# Démarrer tous les services
docker-compose up -d
# -d = mode détaché (en arrière-plan)

# Arrêter tous les services
docker-compose down

# Arrêter et supprimer les volumes (⚠️ perte de données)
docker-compose down -v

# Voir les conteneurs en cours
docker ps

# Voir tous les conteneurs (même arrêtés)
docker ps -a

# Voir les logs d'un service
docker-compose logs php
docker-compose logs -f db  # -f = mode suivi en temps réel

# Redémarrer un service
docker-compose restart php
```

#### Exécuter des commandes dans un conteneur

```bash
# Exécuter une commande Symfony
docker exec symfony_php php bin/console [commande]

# Entrer dans le shell du conteneur
docker exec -it symfony_php bash

# Exécuter Composer
docker exec symfony_php composer install
docker exec symfony_php composer update
```

#### Rebuild des conteneurs

```bash
# Reconstruire les images (après modification du Dockerfile)
docker-compose build

# Reconstruire et redémarrer
docker-compose up -d --build

# Reconstruire un service spécifique
docker-compose build php
```

#### Nettoyage

```bash
# Supprimer les conteneurs arrêtés
docker container prune

# Supprimer les images non utilisées
docker image prune

# Supprimer les volumes non utilisés
docker volume prune

# Nettoyage complet (⚠️ dangereux)
docker system prune -a
```

### Résolution de noms (DNS)

Docker crée automatiquement des entrées DNS pour chaque service.

**Exemple** : Dans `.env`, on utilise :
```env
DATABASE_URL="mysql://root:root@db:3306/minirsn_db"
```

- `@db` : Docker résout automatiquement vers l'IP du conteneur MySQL
- Pas besoin d'IP hard-codée !

**Communication entre conteneurs** :
```
symfony_php → db:3306 → minirsn_db
symfony_php → mailhog:1025 → symfony_mailhog
```

### Volumes : Données persistantes vs temporaires

#### Volume nommé (persistant)
```yaml
volumes:
  - db_data:/var/lib/mysql
```
- Les données **survivent** à `docker-compose down`
- Situé dans `/var/lib/docker/volumes/`

#### Bind mount (synchronisation)
```yaml
volumes:
  - ./:/var/www/html
```
- Lie un dossier de votre machine au conteneur
- Modifications synchronisées en temps réel
- Les données sont **sur votre machine**

### Ports : Mapping hôte → conteneur

```yaml
ports:
  - "8000:80"    # localhost:8000 → conteneur:80
```

| Service | Port Externe | Port Interne | URL |
|---------|--------------|--------------|-----|
| PHP/Apache | 8000 | 80 | http://localhost:8000 |
| MySQL | 3307 | 3306 | localhost:3307 |
| phpMyAdmin | 8080 | 80 | http://localhost:8080 |
| MailHog SMTP | 1025 | 1025 | smtp://localhost:1025 |
| MailHog Web | 8025 | 8025 | http://localhost:8025 |

### Ordre de démarrage

Grâce à `depends_on`, Docker démarre les services dans le bon ordre :

1. **db** (MySQL) - Aucune dépendance
2. **php** (Symfony) - Attend db
3. **phpmyadmin** - Attend db
4. **mailhog** - Aucune dépendance

### Dépannage Docker

#### Problème : Port déjà utilisé

```bash
Error: Bind for 0.0.0.0:8000 failed: port is already allocated
```

**Solution** :
- Changer le port dans `docker-compose.yml` : `"8001:80"`
- Ou arrêter le service qui occupe le port

#### Problème : Conteneur ne démarre pas

```bash
# Voir les logs
docker-compose logs [service]

# Voir les dernières lignes en temps réel
docker-compose logs -f [service]
```

#### Problème : Modifications non prises en compte

```bash
# Rebuild l'image
docker-compose up -d --build

# Ou
docker-compose build --no-cache
docker-compose up -d
```

#### Problème : Base de données corrompue

```bash
# Supprimer le volume et recommencer
docker-compose down -v
docker-compose up -d
# Puis réimporter le dump SQL
```

### Avantages de cette configuration

✅ **Environnement complet** : PHP, MySQL, phpMyAdmin, MailHog
✅ **Isolation** : Pas de conflit avec d'autres projets
✅ **Portable** : Fonctionne partout (Windows, Mac, Linux)
✅ **Reproductible** : Même config pour toute l'équipe
✅ **Facile** : Un seul `docker-compose up` pour tout démarrer
✅ **Développement** : Hot reload avec les bind mounts

### En production

⚠️ Cette configuration est pour le **développement uniquement**.

En production, vous devriez :
- Utiliser des images optimisées (multi-stage builds)
- Séparer les services (DB sur un serveur dédié)
- Utiliser des secrets pour les mots de passe
- Configurer un reverse proxy (Nginx)
- Utiliser des volumes distants (NFS, cloud storage)

## Configuration MailHog (Emails de développement)

### Qu'est-ce que MailHog ?

**MailHog** est un outil de test d'emails pour le développement. Il intercepte tous les emails envoyés par l'application et les affiche dans une interface web, **sans les envoyer réellement**. C'est parfait pour tester les fonctionnalités d'envoi d'emails sans spammer les vrais utilisateurs.

### Comment ça fonctionne ?

```
Application Symfony → Symfony Mailer → MailHog (SMTP:1025) → Interface Web (8025)
```

1. L'application envoie un email via Symfony Mailer
2. Le serveur SMTP de MailHog (port 1025) intercepte l'email
3. L'email est stocké en mémoire
4. Vous pouvez le consulter sur http://localhost:8025

### Configuration mise en place

#### 1. Docker Compose (docker-compose.yml)

```yaml
mailhog:
  image: mailhog/mailhog
  container_name: symfony_mailhog
  ports:
    - "1025:1025"  # Port SMTP (envoi)
    - "8025:8025"  # Interface web (consultation)
  networks:
    - symfony_network
```

#### 2. Configuration Symfony (.env)

```env
MAILER_DSN=smtp://mailhog:1025
```

- `smtp://` : Protocole SMTP
- `mailhog` : Nom du conteneur Docker (hostname)
- `1025` : Port SMTP de MailHog

#### 3. Configuration Mailer (config/packages/mailer.yaml)

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
```

#### 4. Configuration Messenger (config/packages/messenger.yaml)

Pour que les emails soient envoyés **immédiatement** (mode synchrone) :

```yaml
framework:
    messenger:
        transports:
            sync: 'sync://'
        routing:
            Symfony\Component\Mailer\Messenger\SendEmailMessage: sync
```

**Important** : Par défaut, Symfony envoie les emails de manière asynchrone (en file d'attente). On a configuré le mode synchrone pour simplifier le développement.

### Comment utiliser MailHog ?

#### Accéder à l'interface web

1. Ouvrez votre navigateur
2. Allez sur **http://localhost:8025**
3. Vous verrez tous les emails interceptés

#### Créer un post pour tester

1. Connectez-vous à l'application (http://localhost:8000)
2. Créez un nouveau post via `/post/new`
3. Retournez sur MailHog (http://localhost:8025)
4. Vous verrez un email pour chaque utilisateur (sauf l'auteur)

#### Fonctionnalités de l'interface MailHog

- **Liste des emails** : Tous les emails envoyés
- **Vue HTML** : Aperçu de l'email avec le design
- **Vue Source** : Code HTML brut
- **Vue Plain Text** : Version texte de l'email
- **Headers** : En-têtes de l'email (From, To, Subject, etc.)
- **Supprimer** : Vider la boîte mail

### Service de notification créé

**Fichier** : [src/Service/PostNotificationService.php](src/Service/PostNotificationService.php)

Ce service gère l'envoi d'emails lorsqu'un post est créé :

```php
public function notifyNewPost(Post $post): void
{
    // 1. Récupère tous les utilisateurs
    $allUsers = $this->userRepository->findAll();

    // 2. Filtre pour exclure l'auteur
    $recipients = [];
    foreach ($allUsers as $user) {
        if ($user->getEmail() && $user->getId() !== $post->getUser()->getId()) {
            $recipients[] = $user->getEmail();
        }
    }

    // 3. Génère le contenu HTML avec Twig
    $htmlContent = $this->twig->render('emails/new_post_notification.html.twig', [
        'post' => $post,
        'author' => $post->getUser(),
    ]);

    // 4. Envoie l'email à chaque destinataire
    foreach ($recipients as $recipient) {
        $email = (new Email())
            ->from('noreply@minirsn.local')
            ->to($recipient)
            ->subject('Nouveau post créé sur MiniRSN')
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
```

### Template email

**Fichier** : [templates/emails/new_post_notification.html.twig](templates/emails/new_post_notification.html.twig)

L'email utilise **Tailwind CSS** pour le design :
- Header avec dégradé de couleurs
- Carte du post avec fond coloré
- Avatar de l'auteur (première lettre)
- Badge si le post contient une image
- Bouton "Voir le post"
- Footer avec informations

### Intégration dans le contrôleur

**Fichier** : [src/Controller/PostController.php](src/Controller/PostController.php)

Dans la méthode `new()`, après la création du post :

```php
// Envoyer une notification par email aux admins
try {
    $notificationService->notifyNewPost($post);
} catch (\Exception $e) {
    // En cas d'erreur, le post est quand même créé
    // L'erreur n'est pas affichée à l'utilisateur
}
```

### Dépannage MailHog

#### Les emails n'apparaissent pas

1. **Vérifier que MailHog est démarré** :
   ```bash
   docker ps | grep mailhog
   ```
   Doit afficher : `symfony_mailhog   Up X minutes`

2. **Vérifier la configuration .env** :
   ```bash
   # Doit contenir :
   MAILER_DSN=smtp://mailhog:1025
   ```

3. **Vérifier les logs Symfony** :
   ```bash
   docker exec symfony_php tail -f var/log/dev.log
   ```
   Recherchez les lignes contenant "Mailer" ou "SendEmailMessage"

4. **Redémarrer MailHog** :
   ```bash
   docker-compose restart mailhog
   ```

5. **Vider le cache Symfony** :
   ```bash
   docker exec symfony_php php bin/console cache:clear
   ```

#### Emails en file d'attente (asynchrone)

Si les emails ne partent pas immédiatement, vérifiez `config/packages/messenger.yaml` :

```yaml
routing:
    Symfony\Component\Mailer\Messenger\SendEmailMessage: sync  # Doit être "sync"
```

Si c'est sur "async", les emails sont mis en file d'attente. Pour les envoyer :
```bash
docker exec symfony_php php bin/console messenger:consume async -vv
```

### Avantages de MailHog

✅ **Pas de spamming** : Les emails ne sont jamais envoyés réellement
✅ **Visualisation rapide** : Interface web intuitive
✅ **Léger** : Aucune configuration complexe
✅ **Debug facile** : Voir le HTML, les headers, etc.
✅ **Nettoyage simple** : Supprimer tous les emails en un clic
✅ **Parfait pour dev** : Idéal pour tester avant la production

### En production

⚠️ **Important** : MailHog est uniquement pour le développement !

En production, remplacez dans `.env.prod` :
```env
# Exemple avec Gmail SMTP
MAILER_DSN=smtp://username:password@smtp.gmail.com:587

# Exemple avec Mailtrap
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525

# Exemple avec SendGrid
MAILER_DSN=smtp://apikey:YOUR_SENDGRID_API_KEY@smtp.sendgrid.net:587
```

## Développement

### Ajouter une nouvelle fonctionnalité

1. Créer l'entité si nécessaire :
   ```bash
   docker exec symfony_php php bin/console make:entity
   ```

2. Créer la migration :
   ```bash
   docker exec symfony_php php bin/console make:migration
   docker exec symfony_php php bin/console doctrine:migrations:migrate
   ```

3. Créer le contrôleur :
   ```bash
   docker exec symfony_php php bin/console make:controller
   ```

### Compiler les assets en mode watch

```bash
npm run watch
```

## Dépannage

### Les emails ne s'envoient pas

- Vérifiez que MailHog est démarré : `docker ps | grep mailhog`
- Vérifiez la configuration dans `.env` : `MAILER_DSN=smtp://mailhog:1025`
- Vérifiez que `messenger.yaml` utilise le transport `sync` pour les emails
- Consultez les logs : `docker exec symfony_php tail -f var/log/dev.log`

### Erreur de cache

```bash
docker exec symfony_php rm -rf var/cache/*
docker exec symfony_php php bin/console cache:clear
```

### Problème de permissions

```bash
docker exec symfony_php chmod -R 777 var/
docker exec symfony_php chmod -R 777 public/uploads/
```

### La base de données ne se connecte pas

- Vérifiez que le conteneur MySQL est démarré : `docker ps`
- Vérifiez le nom d'hôte dans `.env` : `db` (nom du service Docker)
- Attendez quelques secondes après le démarrage de Docker

## Contribuer

1. Fork le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## Licence

Ce projet est sous licence MIT.

## Auteurs

- Votre équipe - Sprint MiniRSN

## Support

Pour toute question ou problème, n'hésitez pas à ouvrir une issue sur GitHub.

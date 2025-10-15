# Mini RSN - Mini Réseau Social

## Description du projet

Mini RSN est un mini réseau social développé avec Symfony 7, permettant aux utilisateurs de :
- S'inscrire et se connecter
- Publier des messages avec images
- Commenter les publications
- Recevoir des notifications par email

## Prérequis

Avant de commencer, assurez-vous d'avoir installé :

- **Docker Desktop** (pour Windows/Mac) ou **Docker** + **Docker Compose** (pour Linux)
  - [Télécharger Docker Desktop](https://www.docker.com/products/docker-desktop/)
- **Git** (pour cloner le projet)
  - [Télécharger Git](https://git-scm.com/downloads)

**C'est tout !** Docker s'occupe de tout le reste (PHP, MySQL, phpMyAdmin, MailHog).

---

## Installation du projet (étape par étape)

### Étape 1 : Cloner le projet

Ouvrez un terminal (PowerShell, CMD, ou Git Bash) et exécutez :

```bash
git clone https://github.com/VOTRE_USERNAME/Sprint-MiniRSN.git
cd Sprint-MiniRSN
```

Remplacez `VOTRE_USERNAME` par le nom d'utilisateur GitHub du propriétaire du dépôt.

---

### Étape 2 : Vérifier que le fichier `.env` existe

Le fichier `.env` contient les configurations importantes. Il devrait déjà être présent après le clone.

**Vérifiez qu'il contient bien :**

```env
APP_ENV=dev
APP_SECRET=f84b3a93e2481cb7e4203577cdcca8fc
DATABASE_URL="mysql://root:root@db:3306/minirsn_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://mailhog:1025
```

⚠️ **IMPORTANT** : Si `APP_SECRET` est vide, ajoutez-y cette valeur : `f84b3a93e2481cb7e4203577cdcca8fc`

---

### Étape 3 : Démarrer Docker

**Dans le dossier du projet**, exécutez :

```bash
docker compose up -d
```

**Explication :**
- `docker compose up` : Démarre tous les conteneurs (PHP, MySQL, phpMyAdmin, MailHog)
- `-d` : Mode détaché (les conteneurs tournent en arrière-plan)

**Attendez quelques secondes** que tous les conteneurs démarrent.

**Vérifier que tout fonctionne :**

```bash
docker ps
```

Vous devriez voir 4 conteneurs en cours d'exécution :
- `symfony_php`
- `minirsn_db`
- `symfony_phpmyadmin`
- `symfony_mailhog`

---

### Étape 4 : Installer les dépendances Composer

Symfony utilise Composer pour gérer ses dépendances (bibliothèques tierces).

**Entrez dans le conteneur PHP :**

```bash
docker exec -it symfony_php bash
```

Vous êtes maintenant **à l'intérieur du conteneur**. Votre prompt devrait changer en quelque chose comme `root@abc123:/var/www/html#`

**Installez les dépendances :**

```bash
composer install
```

Cette commande va :
- Lire le fichier `composer.json`
- Télécharger toutes les bibliothèques nécessaires dans le dossier `vendor/`
- Créer l'autoloader de Symfony

⏱️ **Cela peut prendre 1-2 minutes.**

**Une fois terminé, sortez du conteneur :**

```bash
exit
```

---

### Étape 5 : Créer la base de données

La base de données MySQL tourne dans Docker, mais elle est vide. Il faut la créer.

**Créer la base de données :**

```bash
docker exec -it symfony_php php bin/console doctrine:database:create
```

**Résultat attendu :**
```
Created database `minirsn_db` for connection named default
```

Si vous voyez "database exists", c'est normal (la BDD existe déjà).

---

### Étape 6 : Exécuter les migrations

Les **migrations** sont des fichiers qui créent/modifient les tables de la base de données.

**Exécuter les migrations :**

```bash
docker exec -it symfony_php php bin/console doctrine:migrations:migrate
```

**Appuyez sur `yes` (ou juste `y`)** quand on vous demande de confirmer.

**Résultat attendu :**
```
[notice] Migrating up to DoctrineMigrations\VersionXXXXXXXXXXXXXX
[notice] finished in XXXms, used XXM memory, X migrations executed, X sql queries
```

Cela va créer les tables : `user`, `post`, `comment`, `messenger_messages`.

---

### Étape 7 : Vérifier que tout fonctionne

**Ouvrez votre navigateur et allez sur :**

🌐 **http://localhost:8000**

✅ Vous devriez être **automatiquement redirigé vers la page de connexion** (`/login`).

**Si vous voyez la page de connexion avec le bouton dark mode**, c'est bon ! 🎉

---

## URLs importantes

Une fois le projet lancé, vous pouvez accéder à :

| Service | URL | Description |
|---------|-----|-------------|
| **Application Symfony** | http://localhost:8000 | Le site Mini RSN |
| **phpMyAdmin** | http://localhost:8080 | Interface pour gérer la base de données MySQL |
| **MailHog** | http://localhost:8025 | Interface pour voir les emails envoyés (simulés) |

### Identifiants phpMyAdmin

- **Serveur** : `minirsn_db`
- **Utilisateur** : `root`
- **Mot de passe** : `root`
- **Base de données** : `minirsn_db`

---

## Utilisation du projet

### 1. Créer un compte

1. Allez sur http://localhost:8000
2. Cliquez sur **"S'inscrire"**
3. Remplissez le formulaire :
   - **Email** : `votre@email.com`
   - **Mot de passe** : au moins 6 caractères
4. Cliquez sur **"S'inscrire"**
5. Vous serez redirigé vers la page de connexion avec un message de succès

### 2. Se connecter

1. Entrez votre email et mot de passe
2. Cliquez sur **"Se connecter"**
3. Vous êtes redirigé vers la page d'accueil

### 3. Voir les publications

La page d'accueil affiche :
- La liste de tous les posts
- L'email de l'auteur
- La date de création
- Le contenu
- L'image (si présente)

**Pour l'instant**, il n'y a pas de posts. Plus tard, vous pourrez en créer.

### 4. Se déconnecter

Cliquez sur le bouton **"Déconnexion"** dans la barre de navigation.

---

## Commandes utiles

### Démarrer les conteneurs Docker

```bash
docker compose up -d
```

### Arrêter les conteneurs Docker

```bash
docker compose down
```

### Voir les logs (en temps réel)

```bash
docker compose logs -f
```

### Entrer dans le conteneur PHP

```bash
docker exec -it symfony_php bash
```

Une fois à l'intérieur, vous pouvez exécuter des commandes Symfony :

```bash
# Vider le cache
php bin/console cache:clear

# Voir toutes les routes
php bin/console debug:router

# Créer une nouvelle migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

Pour sortir du conteneur :

```bash
exit
```

### Voir les conteneurs en cours d'exécution

```bash
docker ps
```

### Redémarrer un conteneur spécifique

```bash
docker restart symfony_php
```

---

## Structure du projet

```
Sprint-MiniRSN/
├── config/                  # Configuration Symfony
│   ├── packages/           # Configuration des bundles
│   └── routes.yaml         # Définition des routes
├── migrations/             # Migrations de base de données
├── public/                 # Point d'entrée web
│   └── index.php          # Front controller
├── src/
│   ├── Controller/        # Contrôleurs (logique métier)
│   ├── Entity/            # Entités Doctrine (tables BDD)
│   ├── Form/              # Formulaires Symfony
│   ├── Repository/        # Requêtes SQL
│   └── Security/          # Authentification
├── templates/             # Templates Twig (vues)
│   ├── base.html.twig    # Template de base
│   ├── home/             # Page d'accueil
│   ├── registration/     # Inscription
│   └── security/         # Connexion
├── translations/          # Traductions (français)
├── vendor/               # Dépendances Composer (généré)
├── var/                  # Fichiers temporaires (cache, logs)
├── .env                  # Variables d'environnement
├── composer.json         # Dépendances PHP
├── docker-compose.yml    # Configuration Docker
└── Dockerfile           # Image Docker PHP personnalisée
```

---

## Fonctionnalités actuelles

✅ **Inscription** : Créer un compte avec email + mot de passe
✅ **Connexion** : S'authentifier avec email + mot de passe
✅ **Déconnexion** : Se déconnecter
✅ **Page d'accueil** : Afficher la liste des posts (vide pour l'instant)
✅ **Dark mode** : Basculer entre mode clair et sombre
✅ **Traductions** : Messages d'erreur en français
✅ **Sécurité** : Protection CSRF, mots de passe hashés

---

## Fonctionnalités à venir

🔜 Créer un post
🔜 Ajouter une image à un post
🔜 Commenter un post
🔜 Modifier/Supprimer ses propres posts
🔜 Notifications par email (via MailHog)
🔜 Administration (CRUD utilisateurs)

---

## Dépannage

### Problème : "Port 8000 already in use"

**Solution :** Un autre service utilise déjà le port 8000.

**Option 1 :** Arrêtez l'autre service (MAMP, XAMPP, Laragon...)

**Option 2 :** Changez le port dans `docker-compose.yml` :

```yaml
services:
  php:
    ports:
      - "8001:80"  # Changez 8000 en 8001
```

Puis accédez au site via http://localhost:8001

### Problème : "Connection refused" lors de l'accès à la BDD

**Solution :** Le conteneur MySQL n'est pas encore prêt.

Attendez 10-20 secondes que MySQL démarre complètement :

```bash
docker logs minirsn_db
```

Cherchez le message : "mysqld: ready for connections"

### Problème : "Jeton CSRF invalide"

**Solutions :**

1. Vérifiez que `APP_SECRET` n'est pas vide dans `.env`
2. Videz le cache :
   ```bash
   docker exec -it symfony_php php bin/console cache:clear
   ```
3. Videz le cache de votre navigateur (Ctrl+Shift+Delete)
4. Redémarrez les conteneurs :
   ```bash
   docker compose restart
   ```

### Problème : "Class not found" ou "Composer dependencies not installed"

**Solution :** Les dépendances ne sont pas installées.

```bash
docker exec -it symfony_php composer install
```

### Problème : "Permission denied" sur Linux/Mac

**Solution :** Problèmes de permissions sur les fichiers.

```bash
sudo chown -R $USER:$USER .
chmod -R 755 var/
```

### Problème : Les conteneurs ne démarrent pas

**Solution :** Vérifiez les logs :

```bash
docker compose logs
```

Ou pour un conteneur spécifique :

```bash
docker logs symfony_php
docker logs minirsn_db
```

---

## Base de données

### Schéma de la base de données

```
Table: user
+----+-------------------+---------------+------------------------------------------------------+
| id | email             | roles         | password                                             |
+----+-------------------+---------------+------------------------------------------------------+
| 1  | test@test.com     | ["ROLE_USER"] | $2y$13$xyz...                                       |
+----+-------------------+---------------+------------------------------------------------------+

Table: post
+----+---------+------------------+------------+---------------------+
| id | user_id | content          | image      | created_at          |
+----+---------+------------------+------------+---------------------+
| 1  | 1       | Mon premier post | image.jpg  | 2025-01-15 10:30:00 |
+----+---------+------------------+------------+---------------------+

Table: comment
+----+---------+---------+------------------+---------------------+
| id | user_id | post_id | content          | created_at          |
+----+---------+---------+------------------+---------------------+
| 1  | 1       | 1       | Super post !     | 2025-01-15 11:00:00 |
+----+---------+---------+------------------+---------------------+
```

### Accéder à la base de données via phpMyAdmin

1. Allez sur http://localhost:8080
2. Connectez-vous avec :
   - **Serveur** : `minirsn_db`
   - **Utilisateur** : `root`
   - **Mot de passe** : `root`
3. Sélectionnez la base `minirsn_db`
4. Explorez les tables : `user`, `post`, `comment`

### Accéder à la base de données via terminal

```bash
docker exec -it minirsn_db mysql -uroot -proot minirsn_db
```

Ensuite, exécutez des requêtes SQL :

```sql
-- Voir tous les utilisateurs
SELECT * FROM user;

-- Voir tous les posts
SELECT * FROM post;

-- Voir les posts avec leurs auteurs
SELECT p.*, u.email
FROM post p
JOIN user u ON p.user_id = u.id;
```

Pour quitter MySQL :

```sql
exit
```

---

## Technologies utilisées

- **Backend** : Symfony 7.2 (PHP 8.2)
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine
- **Template engine** : Twig
- **CSS** : Tailwind CSS (via CDN)
- **Conteneurisation** : Docker + Docker Compose
- **Outils de développement** :
  - phpMyAdmin (gestion BDD)
  - MailHog (test des emails)

---

## Contribuer au projet

### Workflow Git recommandé

1. **Créer une branche pour chaque fonctionnalité :**

   ```bash
   git checkout -b feature/nom-de-la-fonctionnalite
   ```

2. **Faire vos modifications et les commiter :**

   ```bash
   git add .
   git commit -m "Description des changements"
   ```

3. **Pousser votre branche sur GitHub :**

   ```bash
   git push origin feature/nom-de-la-fonctionnalite
   ```

4. **Créer une Pull Request sur GitHub**

5. **Après validation, merger dans main**

### Conventions de code

- **PHP** : Suivre les PSR-12
- **Nommage** :
  - Classes : `PascalCase`
  - Méthodes : `camelCase`
  - Variables : `camelCase`
  - Constantes : `UPPER_SNAKE_CASE`
- **Commits** : Messages en français, descriptifs
  - ✅ `Ajout de la fonctionnalité de création de posts`
  - ❌ `fix`

---

## Ressources utiles

### Documentation officielle

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)
- [Twig Documentation](https://twig.symfony.com/doc/)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Docker Documentation](https://docs.docker.com/)

### Guides du projet

- **GUIDE_COMPLET_AUTH.md** : Guide détaillé du système d'authentification (75 000+ caractères)
- **DARK_MODE.md** : Documentation du système dark mode avec Tailwind CSS
- **FICHE_REVISION.md** : Révision des concepts de base
- **GUIDE_IMPLEMENTATION.md** : Étapes d'implémentation
- **EXPLICATIONS_INSCRIPTION_LOGIN.md** : Explications inscription/connexion
- **EXPLICATIONS_PAGE_ACCUEIL.md** : Explications page d'accueil

---

## Auteurs

- **Votre équipe** - Développement initial

---

## Licence

Ce projet est un projet éducatif. Aucune licence particulière.

---

## FAQ

### Pourquoi Docker ?

Docker permet d'avoir un environnement de développement **identique** pour toute l'équipe :
- Même version de PHP
- Même version de MySQL
- Pas besoin d'installer PHP/MySQL localement
- Fonctionne sur Windows, Mac et Linux

### Où sont stockées les données ?

Les données MySQL sont stockées dans un **volume Docker** nommé `db_data`.
Même si vous arrêtez les conteneurs, les données persistent.

Pour supprimer TOUTES les données (y compris la BDD) :

```bash
docker compose down -v
```

⚠️ **ATTENTION** : Cela supprime définitivement toutes les données !

### Comment ajouter une nouvelle dépendance ?

**Exemple : Installer un bundle Symfony**

```bash
docker exec -it symfony_php composer require nom/du-bundle
```

### Comment voir les logs Symfony ?

**Option 1 : Via Docker**

```bash
docker exec -it symfony_php tail -f var/log/dev.log
```

**Option 2 : Via le Profiler Symfony**

En mode dev, une barre de debug apparaît en bas de chaque page avec les logs, requêtes SQL, etc.

### Puis-je utiliser MAMP/XAMPP au lieu de Docker ?

Oui, mais ce n'est **pas recommandé** car :
- L'environnement sera différent pour chaque personne
- Risque de problèmes de versions (PHP, MySQL)
- Docker garantit que tout le monde a le même environnement

### Le dark mode se sauvegarde-t-il ?

Non, le dark mode **ne persiste pas** entre les pages. C'est un choix de conception pour garder le code simple.

Si vous rechargez la page ou changez de page, vous revenez au mode clair.

**Pourquoi ?** Pour éviter la complexité du localStorage et garantir un comportement prévisible.

Pour plus de détails, consultez **DARK_MODE.md**.

---

## Support

En cas de problème :

1. Vérifiez la section **Dépannage** ci-dessus
2. Consultez les guides dans le dossier `docs/`
3. Vérifiez les logs Docker : `docker compose logs`
4. Demandez de l'aide à l'équipe

---

**Bon développement ! 🚀**

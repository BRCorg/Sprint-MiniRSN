# 📋 JOURNAL DE CONFIGURATION - Sprint MiniRSN

**Date de début** : 13 octobre 2025
**Projet** : Sprint-MiniRSN (Application Symfony avec Docker)

---

## 🎯 OBJECTIF GLOBAL

Mettre en place un environnement Docker complet pour l'application Symfony avec :
- PHP 8.3 + Apache
- MySQL 8.0
- phpMyAdmin
- MailHog (interception emails)

---

## ✅ ÉTAPE 1 : CRÉATION DE LA STRUCTURE DOCKER

### Actions réalisées :
```bash
mkdir -p docker/php
```

### Fichiers créés :
- `docker/php/` (dossier)

### Impact sur le projet :
- Création d'un dossier dédié pour la configuration Docker
- Séparation claire entre le code applicatif et la configuration infrastructure
- Suit les bonnes pratiques de l'architecture Docker

### État du projet :
```
Sprint-MiniRSN/
├── docker/
│   └── php/          ← NOUVEAU
├── src/
├── public/
└── ...
```

---

## ✅ ÉTAPE 2 : CRÉATION DU DOCKERFILE PHP

### Actions réalisées :
Création du fichier `docker/php/Dockerfile`

### Contenu du Dockerfile :
```dockerfile
FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN a2enmod rewrite
COPY apache.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html
```

### Impact sur le projet :
- **Image de base** : PHP 8.3 avec Apache intégré
- **Extensions PHP** : Installation de `pdo` et `pdo_mysql` pour la connexion à MySQL
- **Composer** : Copié depuis l'image officielle Composer (permet d'installer les dépendances Symfony)
- **Module Apache** : `rewrite` activé (nécessaire pour le routing Symfony avec URLs propres)
- **Configuration Apache** : Copie de notre fichier de configuration personnalisé
- **Permissions** : Les fichiers appartiennent à l'utilisateur `www-data` (utilisateur Apache standard)

### Pourquoi ces choix ?
- `pdo_mysql` : Sans cette extension, Symfony ne peut PAS se connecter à MySQL
- `rewrite` : Sans ce module, seules les URLs basiques fonctionnent (pas de `/user/profile`, etc.)
- Composer : Nécessaire pour installer les bundles Symfony

---

## ✅ ÉTAPE 3 : CRÉATION DE LA CONFIGURATION APACHE

### Actions réalisées :
Création du fichier `docker/php/apache.conf`

### Contenu du fichier :
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
        FallbackResource /index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Impact sur le projet :
- **DocumentRoot** : Pointe vers `/var/www/html/public` (dossier public de Symfony)
  - Sans ça : Apache chercherait les fichiers à la racine et l'app ne démarrerait pas

- **AllowOverride All** : Permet à Symfony d'utiliser son `.htaccess`
  - Impact : Active le système de routing Symfony

- **Require all granted** : Autorise l'accès public
  - Sans ça : Erreur 403 Forbidden

- **FallbackResource /index.php** : Toutes les requêtes passent par `index.php`
  - Impact : C'est le cœur du front controller Symfony
  - Exemple : `/user/profile` → `/index.php` → Routing Symfony → Controller

### Pourquoi c'est crucial ?
Sans cette configuration, l'application Symfony afficherait des erreurs 404 sur toutes les routes sauf la page d'accueil.

---

## ✅ ÉTAPE 4 : CRÉATION DU DOCKER-COMPOSE.YML

### Actions réalisées :
Création du fichier `docker-compose.yml` avec 4 services

### Contenu :
```yaml
version: '3.8'

services:
  php:          # Service PHP + Apache
  db:           # Service MySQL
  phpmyadmin:   # Interface graphique MySQL
  mailhog:      # Interception emails

networks:
  symfony_network:
    driver: bridge

volumes:
  db_data:      # Persistance des données MySQL
```

### Impact détaillé par service :

#### 1. Service PHP + Apache (`php`)
```yaml
build: ./docker/php
ports: "8000:80"
volumes: ./:/var/www/html
networks: symfony_network
depends_on: db
```

**Impact** :
- Construction de l'image depuis notre Dockerfile personnalisé
- Port 8000 de votre machine → Port 80 du container
- **Volume bind mount** : Synchronisation en temps réel du code
  - Modification locale → Visible instantanément dans le container
- Dépend de `db` : Démarre après MySQL

#### 2. Service MySQL (`db`)
```yaml
image: mysql:8.0
ports: "3306:3306"
environment:
  MYSQL_ROOT_PASSWORD: root
  MYSQL_DATABASE: symfony_db
volumes: db_data:/var/lib/mysql
```

**Impact** :
- Image officielle MySQL 8.0
- Base de données `symfony_db` créée automatiquement
- **Named volume** : Les données persistent même si le container est supprimé
- Accessible via le hostname `db` (PAS `localhost`)

#### 3. Service phpMyAdmin (`phpmyadmin`)
```yaml
image: phpmyadmin/phpmyadmin
ports: "8080:80"
environment:
  PMA_HOST: db
```

**Impact** :
- Interface web pour gérer MySQL
- Se connecte automatiquement au service `db`
- Login : `root` / `root`

#### 4. Service MailHog (`mailhog`)
```yaml
image: mailhog/mailhog
ports:
  - "1025:1025"  # SMTP
  - "8025:8025"  # Web UI
```

**Impact** :
- Intercepte tous les emails envoyés par Symfony
- Les emails ne sont PAS vraiment envoyés
- Interface web pour les consulter

### Réseau Docker :
```yaml
networks:
  symfony_network:
    driver: bridge
```

**Impact** :
- Tous les services peuvent communiquer entre eux
- Utilisation du nom du service comme hostname DNS
- Exemple : `php` peut joindre `db` via le hostname `db`

---

## ✅ ÉTAPE 5 : MISE À JOUR DU FICHIER .ENV

### Actions réalisées :
Modification du fichier `.env` de Symfony

### Changements effectués :

#### Avant :
```env
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
MAILER_DSN=null://null
```

#### Après :
```env
DATABASE_URL="mysql://root:root@db:3306/symfony_db?serverVersion=8.0&charset=utf8mb4"
MAILER_DSN=smtp://mailhog:1025
```

### Impact sur le projet :

#### 1. DATABASE_URL
- **Changement de SGBD** : PostgreSQL → MySQL
- **Hostname** : `127.0.0.1` → `db`
  - `127.0.0.1` (localhost) ne fonctionne PAS dans Docker
  - `db` est le nom du service MySQL dans docker-compose.yml
- **Credentials** : `root:root` (définis dans docker-compose.yml)
- **Base de données** : `symfony_db` (créée automatiquement au démarrage)

**Pourquoi `@db` et pas `@localhost` ?**
- Chaque container Docker a son propre `localhost`
- Pour communiquer entre containers, on utilise le nom du service
- Docker fournit un DNS interne qui résout `db` vers l'IP du container MySQL

#### 2. MAILER_DSN
- **Avant** : `null://null` (aucun email envoyé)
- **Après** : `smtp://mailhog:1025` (interception par MailHog)

**Impact** :
- Les emails sont interceptés par MailHog
- Consultables via http://localhost:8025
- Évite l'envoi de vrais emails pendant le développement

---

## ✅ ÉTAPE 6 : SUPPRESSION DES ANCIENS FICHIERS

### Actions réalisées :
```bash
docker-compose down
rm compose.yaml compose.override.yaml
```

### Fichiers supprimés :
- `compose.yaml` (configuration PostgreSQL auto-générée par Symfony)
- `compose.override.yaml` (surcharges pour le développement)

### Impact sur le projet :
- **Évite les conflits** : Docker utilisait `compose.yaml` en priorité
- **Configuration unique** : Un seul fichier `docker-compose.yml` à maintenir
- **Cohérence** : Suppression de la config PostgreSQL (remplacée par MySQL)

---

## ✅ ÉTAPE 7 : DÉMARRAGE DES SERVICES DOCKER

### Actions réalisées :
```bash
docker-compose up -d --build
```

### Détail de la commande :
- `up` : Démarre les services
- `-d` : Mode détaché (en arrière-plan)
- `--build` : Reconstruit les images si modifiées

### Impact sur le projet :

#### 1. Construction de l'image PHP
- Téléchargement de l'image PHP 8.3-Apache
- Installation des extensions `pdo` et `pdo_mysql`
- Installation de Composer
- Configuration d'Apache avec notre fichier `apache.conf`

#### 2. Démarrage des containers
- **symfony_php** : Application Symfony accessible sur port 8000
- **symfony_db** : MySQL 8.0 accessible sur port 3306
- **symfony_phpmyadmin** : phpMyAdmin accessible sur port 8080
- **symfony_mailhog** : MailHog accessible sur ports 1025 et 8025

#### 3. Création du réseau
- **symfony_network** : Réseau bridge pour la communication inter-containers

#### 4. Création du volume
- **db_data** : Volume persistant pour les données MySQL

---

## ❌ ÉTAPE 8 : RÉSOLUTION DU PROBLÈME "vendor/autoload_runtime.php not found"

### Problème rencontré :
Lors de l'accès à http://localhost:8000, l'erreur suivante est apparue :
```
Warning: require_once(/var/www/html/vendor/autoload_runtime.php): Failed to open stream: No such file or directory in /var/www/html/public/index.php on line 5

Fatal error: Uncaught Error: Failed opening required '/var/www/html/vendor/autoload_runtime.php'
```

### Cause du problème :
Le dossier `vendor/` (contenant les dépendances Composer) n'existait pas dans le container car :
1. **Les dépendances n'étaient pas installées**
2. **Git et unzip manquaient dans le Dockerfile**, empêchant Composer de télécharger les packages

### Solution appliquée :

#### 1. Modification du Dockerfile
Ajout des outils système nécessaires à Composer :

**Avant** :
```dockerfile
FROM php:8.3-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql
```

**Après** :
```dockerfile
FROM php:8.3-apache

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql
```

**Impact** :
- `git` : Permet à Composer de cloner les dépendances depuis les dépôts Git
- `unzip` : Permet à Composer de décompresser les archives téléchargées
- `zip` : Utile pour certaines opérations Composer
- `&& rm -rf /var/lib/apt/lists/*` : Nettoie le cache APT pour réduire la taille de l'image

#### 2. Reconstruction de l'image Docker
```bash
docker-compose up -d --build
```

**Impact** :
- Reconstruction complète de l'image PHP avec les nouveaux outils
- Téléchargement et installation de git, unzip, zip (~15 MB)
- Durée : ~30 secondes

#### 3. Installation des dépendances Composer
```bash
docker-compose exec php composer install --no-interaction
```

**Résultat** :
```
Installing dependencies from lock file (including require-dev)
Package operations: 127 installs, 0 updates, 0 removals
  - Installing symfony/flex (v2.8.2)
  - Installing symfony/runtime (v7.3.4)
  - Installing symfony/routing (v7.3.4)
  [... 124 autres packages ...]
  - Installing twig/extra-bundle (v3.21.0)

Generating autoload files
110 packages you are using are looking for funding.

Executing script cache:clear [OK]
Executing script assets:install public [OK]
Executing script importmap:install [OK]
```

**Impact** :
- ✅ **127 packages Symfony installés** (framework + bundles)
- ✅ Création du dossier `/var/www/html/vendor/`
- ✅ Génération de l'autoloader Composer
- ✅ Exécution automatique des scripts post-install (cache, assets)

#### 4. Vérification du résultat
```bash
curl -s http://localhost:8000 | head -20
```

**Résultat** :
```html
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Welcome to Symfony!</title>
    ...
</head>
```

### Impact final :
| Élément | Avant | Après |
|---------|-------|-------|
| Dossier vendor/ | ❌ Absent | ✅ Présent (127 packages) |
| Application Symfony | ❌ Erreur fatale | ✅ Fonctionne |
| Page http://localhost:8000 | ❌ Fatal error | ✅ "Welcome to Symfony!" |

### Leçon apprise :
**Pourquoi git et unzip sont-ils nécessaires ?**

Composer utilise deux méthodes pour télécharger les packages :
1. **Méthode "dist"** (par défaut) : Télécharge une archive ZIP
   - Nécessite : `unzip`
   - Avantage : Plus rapide

2. **Méthode "source"** (fallback) : Clone le dépôt Git
   - Nécessite : `git`
   - Avantage : Garde l'historique Git

Sans ces outils, Composer **ne peut télécharger aucune dépendance** et affiche :
```
The zip extension and unzip/7z commands are both missing, skipping.
git was not found in your PATH, skipping source download
```

### Fichier Dockerfile final et complet :
```dockerfile
# Image de base : PHP 8.3 avec Apache
FROM php:8.3-apache

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Installer Composer (gestionnaire de dépendances)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer le module Apache pour les URLs propres
RUN a2enmod rewrite

# Copier la configuration Apache personnalisée
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Définir le dossier de travail
WORKDIR /var/www/html

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html
```

---

## 📊 ÉTAT FINAL DU PROJET

### Structure complète :
```
Sprint-MiniRSN/
├── docker/
│   └── php/
│       ├── Dockerfile          ← Image PHP personnalisée
│       └── apache.conf         ← Configuration Apache
├── docker-compose.yml          ← Orchestration des services
├── .env                        ← Configuration Symfony (modifiée)
├── src/
├── public/
├── config/
└── ...
```

### Services actifs :
| Service | Container | Status | Port | URL | État |
|---------|-----------|--------|------|-----|------|
| PHP + Apache | symfony_php | ✅ Running | 8000 | http://localhost:8000 | ✅ **Fonctionnel** |
| MySQL | symfony_db | ✅ Running | 3306 | - | ✅ Actif |
| phpMyAdmin | symfony_phpmyadmin | ✅ Running | 8080 | http://localhost:8080 | ✅ Actif |
| MailHog | symfony_mailhog | ✅ Running | 8025 | http://localhost:8025 | ✅ Actif |

### Packages installés :
- ✅ **127 packages Composer** installés avec succès
- ✅ Dossier `vendor/` créé et fonctionnel
- ✅ Autoloader Composer généré

### Connexions entre services :
```
┌─────────────────────────────────────────────────┐
│         symfony_network (Docker Bridge)         │
│                                                 │
│  ┌──────────┐      ┌──────────┐                │
│  │   PHP    │─────▶│  MySQL   │                │
│  │  :8000   │      │  db:3306 │                │
│  └──────────┘      └──────────┘                │
│       │                  ▲                      │
│       │                  │                      │
│       ▼                  │                      │
│  ┌──────────┐      ┌──────────┐                │
│  │ MailHog  │      │phpMyAdmin│                │
│  │  :1025   │      │  :8080   │                │
│  └──────────┘      └──────────┘                │
└─────────────────────────────────────────────────┘
```

---

## 🔄 FLUX DE DONNÉES

### 1. Requête HTTP entrante
```
Navigateur (localhost:8000)
    ↓
Container PHP (symfony_php:80)
    ↓
Apache reçoit la requête
    ↓
Redirige vers /var/www/html/public/index.php
    ↓
Symfony traite la requête
    ↓
Retour de la réponse HTML
```

### 2. Connexion à la base de données
```
Symfony
    ↓
DATABASE_URL="mysql://root:root@db:3306/symfony_db"
    ↓
Docker DNS résout "db" → IP du container symfony_db
    ↓
Connexion TCP sur port 3306
    ↓
MySQL (container symfony_db)
```

### 3. Envoi d'email
```
Symfony Mailer
    ↓
MAILER_DSN=smtp://mailhog:1025
    ↓
Docker DNS résout "mailhog" → IP du container symfony_mailhog
    ↓
Email intercepté par MailHog
    ↓
Consultation via http://localhost:8025
```

---

## ⚠️ POINTS D'ATTENTION

### 1. Utilisation des hostnames Docker
❌ **NE PAS FAIRE** :
```env
DATABASE_URL="mysql://root:root@localhost:3306/symfony_db"
DATABASE_URL="mysql://root:root@127.0.0.1:3306/symfony_db"
```

✅ **FAIRE** :
```env
DATABASE_URL="mysql://root:root@db:3306/symfony_db"
```

**Raison** : `localhost` dans un container pointe vers le container lui-même, pas vers l'hôte.

### 2. Synchronisation du code
- Les modifications de fichiers PHP sont **immédiatement visibles** (volume bind mount)
- Pas besoin de rebuild après modification du code
- **Rebuild nécessaire** uniquement si modification du Dockerfile

### 3. Persistance des données
- **Code source** : Bind mount → Modifications persistantes sur votre machine
- **Base de données** : Named volume → Survit à `docker-compose down`
- **⚠️ Attention** : `docker-compose down -v` supprime le volume (données perdues !)

---

## 🎯 PROCHAINES ÉTAPES RECOMMANDÉES

### 1. Vérification de l'application
```bash
# Ouvrir dans le navigateur
http://localhost:8000
```

**Attendu** : Page d'accueil Symfony

### 2. Test de la connexion MySQL
```bash
# Entrer dans le container PHP
docker-compose exec php bash

# Tester la connexion
php bin/console doctrine:database:create --if-not-exists
```

### 3. Création d'entités
```bash
docker-compose exec php php bin/console make:entity User
```

### 4. Migrations de base de données
```bash
docker-compose exec php php bin/console make:migration
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### 5. Vérification phpMyAdmin
```
URL : http://localhost:8080
Login : root
Password : root
```

**Vérifier** : Présence de la base `symfony_db`

### 6. Test MailHog
```
URL : http://localhost:8025
```

**Test** : Envoyer un email depuis Symfony et vérifier qu'il apparaît dans MailHog

---

## 📝 COMMANDES UTILES

### Gestion des containers
```bash
# Démarrer
docker-compose up -d

# Arrêter
docker-compose stop

# Arrêter et supprimer
docker-compose down

# Voir les logs
docker-compose logs -f php

# Voir les containers actifs
docker-compose ps
```

### Exécution de commandes Symfony
```bash
# Structure générale
docker-compose exec php [commande]

# Exemples
docker-compose exec php composer install
docker-compose exec php php bin/console cache:clear
docker-compose exec php php bin/console make:controller
```

### Accès shell
```bash
# Shell dans le container PHP
docker-compose exec php bash

# Shell dans le container MySQL
docker-compose exec db bash
```

---

## 🔧 DÉPANNAGE

### Problème : Port déjà utilisé
**Erreur** : `Bind for 0.0.0.0:8000 failed: port is already allocated`

**Solution** : Modifier le port dans docker-compose.yml
```yaml
ports:
  - "8001:80"  # Au lieu de 8000
```

### Problème : Connection refused à MySQL
**Vérifications** :
1. Le service `db` est-il démarré ? → `docker-compose ps`
2. Utilisez-vous `@db` dans DATABASE_URL ? (pas `@localhost`)
3. Les credentials sont-ils corrects ? (`root:root`)

### Problème : 403 Forbidden
**Cause probable** : Permissions sur les fichiers

**Solution** :
```bash
docker-compose exec php chown -R www-data:www-data /var/www/html
```

### Problème : Modifications PHP non prises en compte
**Solution** : Vider le cache Symfony
```bash
docker-compose exec php php bin/console cache:clear
```

---

## 📚 RESSOURCES

- Fiche de révision : `FICHE_REVISION_DOCKER_SYMFONY.md`
- Documentation Docker : https://docs.docker.com/
- Documentation Symfony : https://symfony.com/doc/

---

---

## ✅ RÉSUMÉ DES ÉTAPES DE CONFIGURATION

| # | Étape | Fichiers créés/modifiés | Status |
|---|-------|-------------------------|--------|
| 1 | Création structure Docker | `docker/php/` | ✅ |
| 2 | Création Dockerfile PHP | `docker/php/Dockerfile` | ✅ |
| 3 | Configuration Apache | `docker/php/apache.conf` | ✅ |
| 4 | Docker Compose | `docker-compose.yml` | ✅ |
| 5 | Configuration .env | `.env` (DATABASE_URL, MAILER_DSN) | ✅ |
| 6 | Nettoyage fichiers | Suppression de `compose.yaml` et `compose.override.yaml` | ✅ |
| 7 | Démarrage services | 4 containers actifs | ✅ |
| 8 | **Résolution erreur vendor/** | Ajout git/unzip + composer install | ✅ |

### Résultat final :
🎉 **Environnement Docker Symfony 7 opérationnel à 100%**

- ✅ PHP 8.3 + Apache configuré avec extensions MySQL
- ✅ MySQL 8.0 accessible via hostname `db`
- ✅ phpMyAdmin fonctionnel (root/root)
- ✅ MailHog prêt pour l'interception d'emails
- ✅ 127 packages Composer installés
- ✅ Application Symfony affiche "Welcome to Symfony!"

---

**Dernière mise à jour** : 13 octobre 2025, 20:52
**Version finale** : Configuration complète et fonctionnelle

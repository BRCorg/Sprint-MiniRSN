# 📚 FICHE DE RÉVISION : Docker + Symfony

---

## 🎯 CONCEPTS FONDAMENTAUX

### Qu'est-ce que Docker ?
- **Container** = environnement isolé qui contient une application + ses dépendances
- **Image** = modèle/template pour créer des containers
- **Docker Compose** = outil pour gérer plusieurs containers ensemble

### Pourquoi utiliser Docker ?
✅ Environnement identique pour toute l'équipe
✅ Pas de "ça marche sur ma machine" !
✅ Installation rapide (pas besoin d'installer PHP, MySQL manuellement)
✅ Isolation (ne pollue pas votre système)

---

## 🏗️ ARCHITECTURE DU PROJET

```
┌─────────────────────────────────────────────┐
│         Votre Machine (Windows)             │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │     Docker Compose                  │   │
│  │                                     │   │
│  │  ┌──────────┐  ┌──────────┐       │   │
│  │  │   PHP    │  │  MySQL   │       │   │
│  │  │ +Apache  │──│    db    │       │   │
│  │  │  :8000   │  │  :3306   │       │   │
│  │  └──────────┘  └──────────┘       │   │
│  │       │                            │   │
│  │  ┌──────────┐  ┌──────────┐       │   │
│  │  │phpMyAdmin│  │MailCatch │       │   │
│  │  │  :8080   │  │  :1080   │       │   │
│  │  └──────────┘  └──────────┘       │   │
│  │                                     │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  Volumes:                                   │
│  • symfony/ (votre code)                    │
│  • db_data (données MySQL)                  │
└─────────────────────────────────────────────┘
```

---

## 📦 LES 4 SERVICES

### 1️⃣ PHP + APACHE
**Rôle** : Exécute votre application Symfony

| Élément | Valeur | Explication |
|---------|--------|-------------|
| Port | 8000 | http://localhost:8000 |
| Image | php:8.3-apache | PHP 8.3 + serveur Apache |
| Volume | ./symfony → /var/www/html | Synchronise votre code |
| Extensions | pdo, pdo_mysql | Pour communiquer avec MySQL |

**À retenir** :
- Apache = serveur web qui reçoit les requêtes HTTP
- PHP = traite le code Symfony
- DocumentRoot = `/var/www/html/public` (dossier public de Symfony)

---

### 2️⃣ MYSQL
**Rôle** : Stocke les données de votre application

| Élément | Valeur | Explication |
|---------|--------|-------------|
| Port | 3306 | Port standard MySQL |
| Hostname | `db` | Nom utilisé par les autres services |
| Root Password | root | Mot de passe admin |
| Database | symfony_db | Base créée automatiquement |
| Volume | db_data | Persistance des données |

**À retenir** :
- ⚠️ Dans Symfony : utiliser `@db` et PAS `@localhost`
- Volume nommé = les données survivent même si le container est détruit

---

### 3️⃣ PHPMYADMIN
**Rôle** : Interface graphique pour gérer MySQL

| Élément | Valeur | Explication |
|---------|--------|-------------|
| Port | 8080 | http://localhost:8080 |
| Se connecte à | `db:3306` | Service MySQL |
| Login | root / root | Identifiants MySQL |

**À retenir** :
- Interface web pour voir/modifier les tables
- Alternative à la ligne de commande MySQL

---

### 4️⃣ MAILHOG
**Rôle** : Intercepte les emails en développement

| Élément | Valeur | Explication |
|---------|--------|-------------|
| Interface Web | 8025 | http://localhost:8025 |
| Serveur SMTP | 1025 | Port d'envoi d'email |
| Config Symfony | `smtp://mailhog:1025` | |

**À retenir** :
- Les emails ne sont PAS vraiment envoyés
- Vous les voyez dans l'interface web
- Évite d'envoyer des vrais emails en dev

---

## 🔗 COMMUNICATION ENTRE SERVICES

### Règle d'or
❌ **localhost** ne fonctionne PAS entre containers
✅ Utiliser le **nom du service** comme hostname

### Exemples

**Symfony se connecte à MySQL :**
```env
DATABASE_URL=mysql://root:root@db:3306/symfony_db
                                ^^
                            Nom du service
```

**Symfony envoie un email :**
```env
MAILER_DSN=smtp://mailhog:1025
                  ^^^^^^^
              Nom du service
```

**phpMyAdmin se connecte à MySQL :**
```yaml
PMA_HOST: db  # Nom du service MySQL
```

---

## 📂 STRUCTURE DES FICHIERS

```
Sprint-MiniRSN/
│
├── docker-compose.yml          # ⭐ Fichier principal : orchestration
│
├── docker/
│   ├── php/
│   │   ├── Dockerfile          # Construction de l'image PHP
│   │   └── apache.conf         # Configuration Apache
│
├── symfony/                    # 📁 Votre projet Symfony
│   ├── public/
│   │   └── index.php           # Point d'entrée
│   ├── src/
│   ├── config/
│   ├── .env                    # ⚙️ Configuration (DB, mailer)
│   └── composer.json
│
└── FICHE_REVISION_DOCKER_SYMFONY.md  # 📚 Ce fichier
```

---

## 🚀 COMMANDES ESSENTIELLES

### Démarrage
```bash
# Démarrer tous les services
docker-compose up -d

# -d = détaché (en arrière-plan)
# --build = reconstruit les images si modifiées
docker-compose up -d --build
```

### Vérification
```bash
# Voir les containers actifs
docker-compose ps

# Voir les logs
docker-compose logs php
docker-compose logs db
docker-compose logs -f php    # Suivi en temps réel (-f = follow)
```

### Exécuter des commandes dans un container
```bash
# Structure : docker-compose exec [service] [commande]

# Créer le projet Symfony
docker-compose exec php composer create-project symfony/skeleton symfony

# Installer un bundle
docker-compose exec php composer require symfony/orm-pack

# Créer une entité
docker-compose exec php php bin/console make:entity

# Migration
docker-compose exec php php bin/console make:migration
docker-compose exec php php bin/console doctrine:migrations:migrate

# Shell interactif dans le container
docker-compose exec php bash
```

### Arrêt
```bash
# Arrêter les containers
docker-compose stop

# Arrêter ET supprimer les containers
docker-compose down

# ⚠️ Supprimer aussi les volumes (données perdues !)
docker-compose down -v
```

---

## 🔐 CONFIGURATION SYMFONY

### Fichier .env (symfony/.env)

```env
# Base de données
DATABASE_URL="mysql://root:root@db:3306/symfony_db?serverVersion=8.0"
#                              ^^
#                          Nom du service Docker

# Mailer
MAILER_DSN=smtp://mailhog:1025
```

### Points d'accès

| Service | URL | Usage |
|---------|-----|-------|
| Application Symfony | http://localhost:8000 | Votre site |
| phpMyAdmin | http://localhost:8080 | Gestion BDD |
| MailHog | http://localhost:8025 | Emails interceptés |

---

## 🔧 DOCKERFILE PHP - Explications

```dockerfile
# Image de base : PHP 8.3 avec Apache
FROM php:8.3-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql
# pdo = interface générique pour bases de données
# pdo_mysql = driver spécifique MySQL

# Installer Composer (gestionnaire de dépendances)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer le module Apache pour les URLs propres
RUN a2enmod rewrite

# Définir le dossier de travail
WORKDIR /var/www/html
```

**Pourquoi ces extensions ?**
- Sans `pdo_mysql`, Symfony ne peut PAS communiquer avec MySQL
- Composer est nécessaire pour installer Symfony et ses dépendances

---

## 📋 DOCKER-COMPOSE.YML - Structure

```yaml
version: '3.8'  # Version du format docker-compose

services:       # Liste de tous les containers
  php:          # Service 1
    build: ...
    ports: ...
    volumes: ...
    networks: ...

  db:           # Service 2
    image: ...
    environment: ...
    volumes: ...

networks:       # Réseau pour la communication
  symfony_network:
    driver: bridge

volumes:        # Volumes persistants
  db_data:
```

---

## 🧠 CONCEPTS AVANCÉS

### Volumes : bind mount vs named volume

**Bind mount** : `./symfony:/var/www/html`
- Synchronise un dossier local avec le container
- Changements locaux = visibles dans le container immédiatement
- Utilisé pour le code source

**Named volume** : `db_data:/var/lib/mysql`
- Volume géré par Docker
- Données persistantes (survit à la suppression du container)
- Utilisé pour les bases de données

### Réseaux Docker

```yaml
networks:
  symfony_network:
    driver: bridge
```

- `bridge` = type de réseau le plus courant
- Tous les services sur ce réseau peuvent communiquer
- Utilisation du nom du service comme hostname DNS

### Ports : mapping local:container

```yaml
ports:
  - "8000:80"
```

- Port **8000** sur votre machine
- Port **80** dans le container
- `localhost:8000` → redirige vers le port 80 du container

---

## ❓ QUESTIONS DE RÉVISION

### Q1 : Pourquoi utiliser `@db` et pas `@localhost` dans DATABASE_URL ?
**R** : Chaque container a son propre `localhost`. Pour communiquer entre containers, on utilise le nom du service comme hostname.

### Q2 : Que se passe-t-il si je supprime le container MySQL ?
**R** : Le container est détruit, MAIS les données survivent grâce au volume `db_data`.

### Q3 : Comment voir les logs d'erreur Apache ?
**R** : `docker-compose logs php` ou `docker-compose logs -f php` (temps réel)

### Q4 : Où se trouve le point d'entrée de Symfony ?
**R** : `/var/www/html/public/index.php` (dans le container)

### Q5 : Comment installer un nouveau bundle Symfony ?
**R** : `docker-compose exec php composer require [nom-du-bundle]`

### Q6 : Quelle est la différence entre `docker-compose stop` et `down` ?
**R** :
- `stop` : arrête les containers (peuvent être redémarrés)
- `down` : arrête ET supprime les containers

### Q7 : Comment accéder au shell d'un container ?
**R** : `docker-compose exec php bash`

### Q8 : Pourquoi utiliser MailHog en développement ?
**R** : Pour intercepter les emails et éviter d'envoyer de vrais emails pendant les tests.

---

## 🎓 WORKFLOW COMPLET

### 1️⃣ Démarrage initial

```bash
# 1. Démarrer les services
docker-compose up -d --build

# 2. Vérifier que tout fonctionne
docker-compose ps

# 3. Créer le projet Symfony
docker-compose exec php composer create-project symfony/skeleton symfony

# 4. Installer les dépendances
docker-compose exec php composer require webapp
docker-compose exec php composer require symfony/orm-pack
docker-compose exec php composer require symfony/maker-bundle --dev
```

### 2️⃣ Configuration

```bash
# Éditer symfony/.env
DATABASE_URL="mysql://root:root@db:3306/symfony_db?serverVersion=8.0"
MAILER_DSN=smtp://mailcatcher:1025
```

### 3️⃣ Création d'entités

```bash
# Créer l'entité User
docker-compose exec php php bin/console make:entity User

# Créer la migration
docker-compose exec php php bin/console make:migration

# Appliquer la migration
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### 4️⃣ Authentification

```bash
# Installer le security bundle
docker-compose exec php composer require symfony/security-bundle

# Créer le système de login
docker-compose exec php php bin/console make:security:form-login
```

### 5️⃣ Vérification

- http://localhost:8000 → Application Symfony
- http://localhost:8080 → phpMyAdmin (vérifier les tables)
- http://localhost:8025 → MailHog (tester l'envoi d'emails)

---

## 🐛 DÉPANNAGE

### Problème : "Port already in use"
**Solution** : Un autre programme utilise le port
```bash
# Changer le port dans docker-compose.yml
ports:
  - "8001:80"  # Au lieu de 8000
```

### Problème : "Connection refused" à la base de données
**Vérifications** :
1. Le service `db` est-il démarré ? → `docker-compose ps`
2. Avez-vous utilisé `@db` dans DATABASE_URL ? (pas `@localhost`)
3. Les credentials sont-ils corrects ?

### Problème : Changements PHP non pris en compte
**Solution** : Reconstruire l'image
```bash
docker-compose down
docker-compose up -d --build
```

### Problème : "Permission denied" dans le container
**Solution** : Donner les permissions au dossier
```bash
docker-compose exec php chown -R www-data:www-data /var/www/html
```

---

## 💡 ASTUCES PRO

### Alias pour gagner du temps
Ajoutez dans votre terminal :
```bash
alias dc="docker-compose"
alias dce="docker-compose exec php"

# Utilisation :
dce composer require symfony/mailer
dce php bin/console make:entity
```

### Voir l'utilisation des ressources
```bash
docker stats
```

### Nettoyer Docker
```bash
# Supprimer les containers arrêtés
docker container prune

# Supprimer les images inutilisées
docker image prune

# Tout nettoyer (⚠️ attention !)
docker system prune -a
```

---

## 📌 CHECKLIST DE RÉVISION

- [ ] Je comprends la différence entre image et container
- [ ] Je sais pourquoi utiliser `@db` au lieu de `@localhost`
- [ ] Je connais les 4 services et leur rôle
- [ ] Je sais démarrer/arrêter les containers
- [ ] Je sais exécuter des commandes dans un container
- [ ] Je comprends le concept de volumes
- [ ] Je sais configurer Symfony avec Docker
- [ ] Je peux créer une entité et une migration
- [ ] Je sais accéder à phpMyAdmin et MailCatcher
- [ ] Je peux lire les logs en cas d'erreur

---

## 🔗 RESSOURCES COMPLÉMENTAIRES

- **Documentation Docker** : https://docs.docker.com/
- **Documentation Symfony** : https://symfony.com/doc/current/index.html
- **Docker Compose reference** : https://docs.docker.com/compose/compose-file/
- **Dockerfile reference** : https://docs.docker.com/engine/reference/builder/

---

## 📝 NOTES PERSONNELLES

_(Espace pour vos propres annotations)_

---

**Dernière mise à jour** : 13 octobre 2025
**Version** : PHP 8.3 + Symfony 7 + MySQL 8.0

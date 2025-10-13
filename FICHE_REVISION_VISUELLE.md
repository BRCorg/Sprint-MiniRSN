# 🎨 FICHE DE RÉVISION VISUELLE : Docker + Symfony

---

## 🏗️ ARCHITECTURE GLOBALE

```
╔═══════════════════════════════════════════════════════════════════════╗
║                    VOTRE MACHINE (Windows)                            ║
║                                                                       ║
║   Navigateur Web                                                      ║
║   ┌─────────┐  ┌─────────┐  ┌─────────┐                             ║
║   │localhost│  │localhost│  │localhost│                             ║
║   │  :8000  │  │  :8080  │  │  :1080  │                             ║
║   └────┬────┘  └────┬────┘  └────┬────┘                             ║
║        │            │            │                                   ║
║   ═════╧════════════╧════════════╧════════════════════════════════   ║
║                    DOCKER COMPOSE                                     ║
║   ┌───────────────────────────────────────────────────────────────┐  ║
║   │                                                               │  ║
║   │  ┌──────────────┐         ┌──────────────┐                  │  ║
║   │  │   PHP 8.3    │         │   MySQL 8.0  │                  │  ║
║   │  │  + Apache    │◄───────►│     (db)     │                  │  ║
║   │  │              │  SQL    │              │                  │  ║
║   │  │  Container 1 │         │  Container 2 │                  │  ║
║   │  └──────┬───────┘         └───────▲──────┘                  │  ║
║   │         │                         │                          │  ║
║   │         │                         │                          │  ║
║   │  ┌──────▼───────┐         ┌──────┴───────┐                  │  ║
║   │  │   MailHog    │         │  phpMyAdmin  │                  │  ║
║   │  │              │         │              │                  │  ║
║   │  │  Container 3 │         │  Container 4 │                  │  ║
║   │  └──────────────┘         └──────────────┘                  │  ║
║   │                                                               │  ║
║   │              symfony_network (bridge)                        │  ║
║   └───────────────────────────────────────────────────────────────┘  ║
║                                                                       ║
║   Volumes synchronisés:                                               ║
║   📁 ./symfony ◄──────► /var/www/html (dans container PHP)           ║
║   💾 db_data (données MySQL persistantes)                             ║
║                                                                       ║
╚═══════════════════════════════════════════════════════════════════════╝
```

---

## 🔄 FLUX D'UNE REQUÊTE HTTP

```
┌─────────────────────────────────────────────────────────────────────┐
│                    REQUÊTE : GET /users                             │
└─────────────────────────────────────────────────────────────────────┘

    Utilisateur tape : http://localhost:8000/users
              │
              ▼
    ┌─────────────────┐
    │   Navigateur    │
    │    (Chrome)     │
    └────────┬────────┘
             │ HTTP Request
             │
             ▼
    ┌─────────────────┐
    │  Port 8000      │  ◄─── Mapping : 8000 (local) → 80 (container)
    └────────┬────────┘
             │
             ▼
╔════════════╧═══════════════════════════════════════════════════════╗
║         CONTAINER PHP (symfony_php)                                ║
║                                                                    ║
║   ┌────────────────┐                                              ║
║   │  Apache :80    │  ◄─── Reçoit la requête                     ║
║   └───────┬────────┘                                              ║
║           │                                                        ║
║           ▼                                                        ║
║   ┌────────────────────────────────────┐                          ║
║   │  /var/www/html/public/index.php   │  ◄─── Front Controller   ║
║   └───────┬────────────────────────────┘                          ║
║           │                                                        ║
║           ▼                                                        ║
║   ┌────────────────┐                                              ║
║   │ Symfony Kernel │  ◄─── Analyse la route "/users"             ║
║   └───────┬────────┘                                              ║
║           │                                                        ║
║           ▼                                                        ║
║   ┌────────────────┐                                              ║
║   │  Controller    │  ◄─── UserController::index()               ║
║   └───────┬────────┘                                              ║
║           │                                                        ║
║           ▼                                                        ║
║   ┌────────────────┐                                              ║
║   │ Doctrine ORM   │  ◄─── $userRepository->findAll()            ║
║   └───────┬────────┘                                              ║
║           │ SQL: SELECT * FROM users                              ║
╚═══════════╧════════════════════════════════════════════════════════╝
            │
            │ Via réseau Docker (symfony_network)
            ▼
╔═══════════════════════════════════════════════════════════════════╗
║         CONTAINER MYSQL (symfony_db)                              ║
║                                                                   ║
║   ┌────────────────┐                                             ║
║   │  MySQL :3306   │  ◄─── Hostname: "db" (pas localhost!)      ║
║   └───────┬────────┘                                             ║
║           │                                                       ║
║           ▼                                                       ║
║   ┌────────────────┐                                             ║
║   │ symfony_db     │  ◄─── Exécute la requête SQL               ║
║   │  Table: users  │                                             ║
║   └───────┬────────┘                                             ║
║           │                                                       ║
║           │ Résultat : [user1, user2, user3, ...]               ║
╚═══════════╧═══════════════════════════════════════════════════════╝
            │
            │ Données retournées
            ▼
╔═══════════════════════════════════════════════════════════════════╗
║         CONTAINER PHP (symfony_php)                               ║
║                                                                   ║
║   ┌────────────────┐                                             ║
║   │  Controller    │  ◄─── Reçoit les données                   ║
║   └───────┬────────┘                                             ║
║           │                                                       ║
║           ▼                                                       ║
║   ┌────────────────┐                                             ║
║   │     Twig       │  ◄─── Génère le HTML                       ║
║   └───────┬────────┘                                             ║
║           │                                                       ║
║           │ HTML                                                 ║
╚═══════════╧═══════════════════════════════════════════════════════╝
            │
            ▼
    ┌─────────────────┐
    │   Navigateur    │  ◄─── Affiche la liste des users
    └─────────────────┘
```

---

## 🗺️ CARTE DES PORTS

```
VOTRE MACHINE                    CONTAINERS DOCKER
═══════════════                  ══════════════════

Port 8000  ──────────────────►  PHP Container (Port 80)
  │                                     │
  └─► http://localhost:8000             └─► Apache écoute sur :80


Port 8080  ──────────────────►  phpMyAdmin (Port 80)
  │                                     │
  └─► http://localhost:8080             └─► Interface web


Port 8025  ──────────────────►  MailHog (Port 8025)
  │                                     │
  └─► http://localhost:8025             └─► Interface web emails


Port 1025  ──────────────────►  MailHog (Port 1025)
  │                                     │
  └─► Serveur SMTP                      └─► Serveur SMTP


Port 3306  ──────────────────►  MySQL (Port 3306)
  │                                     │
  └─► Accès direct MySQL                └─► Base de données
      (optionnel)
```

---

## 🧩 COMMUNICATION ENTRE SERVICES

### ❌ CE QUI NE FONCTIONNE PAS

```
╔═══════════════════════════════════════════════════════════════════╗
║  Container PHP essaie de se connecter à localhost:3306           ║
╚═══════════════════════════════════════════════════════════════════╝

┌──────────────────┐
│  Container PHP   │
│                  │
│  DATABASE_URL=   │
│  @localhost:3306 │  ───X───►  Cherche MySQL sur son propre
│                  │             localhost = ÉCHEC !
└──────────────────┘

┌──────────────────┐
│ Container MySQL  │  ◄─── MySQL est ICI, pas dans le container PHP
│   Port 3306      │
└──────────────────┘

❌ Erreur : "Connection refused"
```

### ✅ CE QUI FONCTIONNE

```
╔═══════════════════════════════════════════════════════════════════╗
║  Container PHP utilise le nom du service : "db"                   ║
╚═══════════════════════════════════════════════════════════════════╝

┌──────────────────┐              symfony_network
│  Container PHP   │              (réseau Docker)
│                  │                     │
│  DATABASE_URL=   │                     │
│  @db:3306        │  ──────────────────►│ DNS Docker résout
│                  │         ✓           │ "db" → adresse IP
└──────────────────┘                     │ du container MySQL
                                         │
                                         ▼
                              ┌──────────────────┐
                              │ Container MySQL  │
                              │  Hostname: "db"  │
                              │   Port 3306      │
                              └──────────────────┘

✅ Connexion réussie !
```

---

## 📦 ANATOMIE D'UN VOLUME

### Volume Bind Mount (Code source)

```
╔════════════════════════════════════════════════════════════════╗
║                  SYNCHRONISATION EN TEMPS RÉEL                 ║
╚════════════════════════════════════════════════════════════════╝

VOTRE MACHINE                          CONTAINER PHP
═════════════                          ═════════════

C:\MAMP\htdocs\                        /var/www/html/
Sprint-MiniRSN\symfony\

├── src/                 ◄──────────►  ├── src/
│   ├── Controller/                    │   ├── Controller/
│   │   └── UserController.php         │   │   └── UserController.php
│   └── Entity/                        │   └── Entity/
│       └── User.php                   │       └── User.php
│                                      │
├── public/              ◄──────────►  ├── public/
│   └── index.php                      │   └── index.php
│                                      │
├── config/              ◄──────────►  ├── config/
    └── packages/                          └── packages/

    Vous éditez un fichier    ───►    Changement visible
    avec VSCode sur Windows            immédiatement dans
                                       le container !
```

### Volume Nommé (Base de données)

```
╔════════════════════════════════════════════════════════════════╗
║                  PERSISTANCE DES DONNÉES                       ║
╚════════════════════════════════════════════════════════════════╝

CONTAINER MYSQL                    VOLUME DOCKER (db_data)
═══════════════                    ═════════════════════

/var/lib/mysql/                    Stocké par Docker dans :
├── symfony_db/    ──────────────► C:\ProgramData\docker\
│   ├── users.ibd                  volumes\db_data\
│   ├── posts.ibd
│   └── ...                        ✅ Survit à la suppression
│                                     du container !
└── mysql/
    └── ...

Cycle de vie :

1. Container créé        2. Données écrites      3. Container supprimé
   ┌──────────┐            ┌──────────┐            ┌──────────┐
   │  MySQL   │            │  MySQL   │            │    💀    │
   │          │  ─────►    │ db_data  │  ─────►    │          │
   └──────────┘            └────┬─────┘            └──────────┘
                                │                        │
                                ▼                        │
                           ┌─────────┐                  │
                           │ Volume  │                  │
                           │ db_data │◄─────────────────┘
                           └─────────┘
                           ✅ Données
                           conservées !

4. Nouveau container créé
   ┌──────────┐
   │  MySQL   │  ◄─── Récupère les données du volume
   │          │
   └────┬─────┘
        │
        ▼
   ┌─────────┐
   │ Volume  │  ✅ Toutes les données sont là !
   │ db_data │
   └─────────┘
```

---

## 🎯 LES 4 SERVICES EN DÉTAIL

```
╔═══════════════════════════════════════════════════════════════════╗
║                        SERVICE 1 : PHP + APACHE                   ║
╚═══════════════════════════════════════════════════════════════════╝

    ┌─────────────────────────────────────────────────────────┐
    │                    Container: symfony_php               │
    │                                                         │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │              Apache (Port 80)                    │  │
    │  │  • Reçoit les requêtes HTTP                      │  │
    │  │  • Sert les fichiers statiques (CSS, JS, img)   │  │
    │  │  • Passe les .php à PHP-FPM                      │  │
    │  └─────────────────┬────────────────────────────────┘  │
    │                    │                                    │
    │                    ▼                                    │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │              PHP 8.3                             │  │
    │  │  • Exécute le code Symfony                       │  │
    │  │  • Extensions : pdo, pdo_mysql, intl, opcache   │  │
    │  └─────────────────┬────────────────────────────────┘  │
    │                    │                                    │
    │                    ▼                                    │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │              Symfony 7                           │  │
    │  │  📁 /var/www/html/                               │  │
    │  │     ├── public/index.php (front controller)     │  │
    │  │     ├── src/ (votre code)                       │  │
    │  │     ├── config/ (configuration)                 │  │
    │  │     └── var/ (cache, logs)                      │  │
    │  └──────────────────────────────────────────────────┘  │
    │                                                         │
    │  🔌 Connecté à : db (MySQL), mailcatcher (SMTP)        │
    └─────────────────────────────────────────────────────────┘

    Accès : http://localhost:8000


╔═══════════════════════════════════════════════════════════════════╗
║                        SERVICE 2 : MYSQL                          ║
╚═══════════════════════════════════════════════════════════════════╝

    ┌─────────────────────────────────────────────────────────┐
    │                    Container: symfony_db                │
    │                                                         │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │              MySQL 8.0 (Port 3306)               │  │
    │  │                                                  │  │
    │  │  Hostname dans le réseau : db                   │  │
    │  │  Root Password : root                           │  │
    │  │                                                  │  │
    │  │  Base de données créée : symfony_db             │  │
    │  │                                                  │  │
    │  │  Tables (exemple après migration) :             │  │
    │  │  ┌─────────────────────────────────────────┐    │  │
    │  │  │  users                                  │    │  │
    │  │  │  ├── id (INT, PRIMARY KEY)              │    │  │
    │  │  │  ├── email (VARCHAR)                    │    │  │
    │  │  │  ├── password (VARCHAR)                 │    │  │
    │  │  │  └── roles (JSON)                       │    │  │
    │  │  └─────────────────────────────────────────┘    │  │
    │  │                                                  │  │
    │  │  ┌─────────────────────────────────────────┐    │  │
    │  │  │  posts                                  │    │  │
    │  │  │  ├── id (INT, PRIMARY KEY)              │    │  │
    │  │  │  ├── title (VARCHAR)                    │    │  │
    │  │  │  ├── content (TEXT)                     │    │  │
    │  │  │  └── user_id (INT, FOREIGN KEY)         │    │  │
    │  │  └─────────────────────────────────────────┘    │  │
    │  └──────────────────────────────────────────────────┘  │
    │                                                         │
    │  💾 Volume : db_data → /var/lib/mysql                  │
    │     (données persistantes)                             │
    └─────────────────────────────────────────────────────────┘

    Accès direct (optionnel) : localhost:3306
    Accès depuis PHP : db:3306


╔═══════════════════════════════════════════════════════════════════╗
║                      SERVICE 3 : PHPMYADMIN                       ║
╚═══════════════════════════════════════════════════════════════════╝

    ┌─────────────────────────────────────────────────────────┐
    │                 Container: symfony_phpmyadmin           │
    │                                                         │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │           Interface Web phpMyAdmin               │  │
    │  │                                                  │  │
    │  │  ┌────────────────────────────────────────┐     │  │
    │  │  │  🏠 Page d'accueil                     │     │  │
    │  │  │  Serveur : db:3306                     │     │  │
    │  │  │  Login auto : root / root              │     │  │
    │  │  └────────────────────────────────────────┘     │  │
    │  │                                                  │  │
    │  │  ┌────────────────────────────────────────┐     │  │
    │  │  │  📊 Base de données : symfony_db       │     │  │
    │  │  │  ├── Structure (voir les tables)       │     │  │
    │  │  │  ├── SQL (exécuter des requêtes)       │     │  │
    │  │  │  ├── Rechercher                        │     │  │
    │  │  │  └── Exporter                          │     │  │
    │  │  └────────────────────────────────────────┘     │  │
    │  │                                                  │  │
    │  │  Fonctionnalités :                              │  │
    │  │  ✅ Créer/modifier des tables                   │  │
    │  │  ✅ Voir les données                            │  │
    │  │  ✅ Exécuter des requêtes SQL                   │  │
    │  │  ✅ Importer/Exporter des données               │  │
    │  └──────────────────────────────────────────────────┘  │
    │                                                         │
    │  🔌 Se connecte à : db:3306                            │
    └─────────────────────────────────────────────────────────┘

    Accès : http://localhost:8080


╔═══════════════════════════════════════════════════════════════════╗
║                      SERVICE 4 : MAILHOG                          ║
╚═══════════════════════════════════════════════════════════════════╝

    ┌─────────────────────────────────────────────────────────┐
    │                 Container: symfony_mailhog              │
    │                                                         │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │       Serveur SMTP (Port 1025)                   │  │
    │  │  • Intercepte TOUS les emails                    │  │
    │  │  • Ne les envoie PAS réellement                  │  │
    │  └─────────────────┬────────────────────────────────┘  │
    │                    │                                    │
    │                    ▼                                    │
    │  ┌──────────────────────────────────────────────────┐  │
    │  │       Interface Web (Port 8025)                  │  │
    │  │                                                  │  │
    │  │  ┌────────────────────────────────────────┐     │  │
    │  │  │  📧 Boîte de réception                 │     │  │
    │  │  │                                        │     │  │
    │  │  │  From: noreply@example.com             │     │  │
    │  │  │  To: user@example.com                  │     │  │
    │  │  │  Subject: Bienvenue !                  │     │  │
    │  │  │  ────────────────────────────────      │     │  │
    │  │  │  Bonjour et bienvenue...               │     │  │
    │  │  │                                        │     │  │
    │  │  │  [Voir le HTML] [Voir la source]       │     │  │
    │  │  └────────────────────────────────────────┘     │  │
    │  │                                                  │  │
    │  │  Utilisation :                                  │  │
    │  │  • Tester l'inscription (email de bienvenue)   │  │
    │  │  • Tester la réinitialisation de mot de passe  │  │
    │  │  • Voir le rendu HTML des emails               │  │
    │  └──────────────────────────────────────────────────┘  │
    │                                                         │
    │  🔌 Reçoit les emails depuis : Container PHP           │
    └─────────────────────────────────────────────────────────┘

    Interface web : http://localhost:8025
    SMTP (depuis Symfony) : smtp://mailhog:1025
```

---

## 🔄 CYCLE DE VIE D'UN PROJET

```
╔═══════════════════════════════════════════════════════════════════╗
║                      1. DÉMARRAGE INITIAL                         ║
╚═══════════════════════════════════════════════════════════════════╝

Terminal: docker-compose up -d --build

    ┌──────────────┐
    │ Lecture du   │
    │ docker-      │
    │ compose.yml  │
    └──────┬───────┘
           │
           ▼
    ┌──────────────┐         ┌──────────────┐
    │ Construction │   ───►  │  Création    │
    │ des images   │         │  des volumes │
    └──────┬───────┘         └──────┬───────┘
           │                        │
           ▼                        ▼
    ┌──────────────────────────────────┐
    │  Démarrage des 4 containers      │
    │  ├── PHP + Apache  (symfony_php) │
    │  ├── MySQL         (symfony_db)  │
    │  ├── phpMyAdmin                  │
    │  └── MailCatcher                 │
    └──────┬───────────────────────────┘
           │
           ▼
    ┌──────────────┐
    │ Création du  │
    │ réseau Docker│
    └──────┬───────┘
           │
           ▼
    ┌────────────────────────────────────────┐
    │ ✅ Environnement prêt !                │
    │                                        │
    │ • http://localhost:8000 (Symfony)     │
    │ • http://localhost:8080 (phpMyAdmin)  │
    │ • http://localhost:1080 (MailCatcher) │
    └────────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                   2. CRÉATION DU PROJET SYMFONY                   ║
╚═══════════════════════════════════════════════════════════════════╝

Terminal: docker-compose exec php composer create-project symfony/skeleton symfony

    ┌──────────────────┐
    │ Commande exécutée│
    │ DANS le container│
    │ PHP              │
    └────────┬─────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Composer télécharge Symfony         │
    │                                     │
    │ Downloading...                      │
    │ ████████████████░░░░ 75%            │
    └────────┬────────────────────────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Structure créée dans symfony/       │
    │                                     │
    │ symfony/                            │
    │ ├── bin/                            │
    │ ├── config/                         │
    │ ├── public/                         │
    │ │   └── index.php                   │
    │ ├── src/                            │
    │ ├── var/                            │
    │ ├── vendor/                         │
    │ ├── .env                            │
    │ └── composer.json                   │
    └────────┬────────────────────────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ ✅ Projet Symfony créé !            │
    │                                     │
    │ Accès : http://localhost:8000       │
    └─────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                   3. CONFIGURATION DE LA BDD                      ║
╚═══════════════════════════════════════════════════════════════════╝

Éditer: symfony/.env

    Avant (par défaut) :
    ┌────────────────────────────────────────────────────┐
    │ DATABASE_URL="postgresql://app:!ChangeMe!@127...  │
    └────────────────────────────────────────────────────┘
             │
             │ Modification
             ▼
    Après :
    ┌────────────────────────────────────────────────────┐
    │ DATABASE_URL="mysql://root:root@db:3306/symfony_db│
    │                                  ^^                │
    │                    Nom du service Docker !         │
    └────────────────────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                   4. CRÉATION D'UNE ENTITÉ                        ║
╚═══════════════════════════════════════════════════════════════════╝

Terminal: docker-compose exec php php bin/console make:entity User

    ┌──────────────────┐
    │ Maker Bundle     │
    │ démarre          │
    └────────┬─────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Questions interactives :            │
    │                                     │
    │ Property name: email                │
    │ Type: string                        │
    │ Length: 180                         │
    │                                     │
    │ Property name: password             │
    │ Type: string                        │
    │                                     │
    │ Property name: (entrée vide)        │
    │ ✅ Success!                         │
    └────────┬────────────────────────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Fichiers créés :                    │
    │                                     │
    │ ✅ src/Entity/User.php               │
    │    (classe avec propriétés)         │
    │                                     │
    │ ✅ src/Repository/UserRepository.php │
    │    (requêtes DB)                    │
    └─────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                   5. MIGRATION DE LA BASE DE DONNÉES              ║
╚═══════════════════════════════════════════════════════════════════╝

Terminal: docker-compose exec php php bin/console make:migration

    ┌──────────────────┐
    │ Doctrine compare │
    │ Entités ↔ BDD    │
    └────────┬─────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Différences détectées :             │
    │                                     │
    │ • Table "user" n'existe pas         │
    │   → Générer CREATE TABLE            │
    └────────┬────────────────────────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Fichier créé :                      │
    │ migrations/Version20250113120000.php│
    │                                     │
    │ Contient :                          │
    │ CREATE TABLE user (                 │
    │   id INT PRIMARY KEY,               │
    │   email VARCHAR(180),               │
    │   password VARCHAR(255)             │
    │ );                                  │
    └─────────────────────────────────────┘

Terminal: docker-compose exec php php bin/console doctrine:migrations:migrate

    ┌──────────────────┐
    │ Exécution de la  │
    │ migration        │
    └────────┬─────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Container PHP                       │
    │   └─► Connexion à : db:3306         │
    └────────┬────────────────────────────┘
             │
             ▼
    ┌─────────────────────────────────────┐
    │ Container MySQL                     │
    │                                     │
    │ Exécution SQL :                     │
    │ CREATE TABLE user (...);            │
    │                                     │
    │ ✅ Table "user" créée !             │
    └─────────────────────────────────────┘

Vérification dans phpMyAdmin (http://localhost:8080) :

    ┌─────────────────────────────────────┐
    │ 📊 symfony_db                       │
    │  └── 📋 user                        │
    │       ├── id (INT)                  │
    │       ├── email (VARCHAR)           │
    │       └── password (VARCHAR)        │
    └─────────────────────────────────────┘
```

---

## 🛠️ COMMANDES DOCKER : CHEAT SHEET VISUEL

```
╔═══════════════════════════════════════════════════════════════════╗
║                        GESTION DES CONTAINERS                     ║
╚═══════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose up -d                                           │
│  ▲                                                              │
│  └─► Démarre tous les containers en arrière-plan               │
│                                                                 │
│  État : ⭕ Arrêté  ────────────►  🟢 Démarré                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose ps                                              │
│  ▲                                                              │
│  └─► Liste les containers actifs                               │
│                                                                 │
│  Résultat :                                                     │
│  NAME                  STATUS       PORTS                       │
│  symfony_php           Up 2 hours   0.0.0.0:8000->80           │
│  symfony_db            Up 2 hours   0.0.0.0:3306->3306         │
│  symfony_phpmyadmin    Up 2 hours   0.0.0.0:8080->80           │
│  symfony_mailhog       Up 2 hours   0.0.0.0:8025->8025         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose stop                                            │
│  ▲                                                              │
│  └─► Arrête les containers (peut les redémarrer)               │
│                                                                 │
│  État : 🟢 Démarré  ────────────►  🟡 Arrêté (sauvegardé)     │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose down                                            │
│  ▲                                                              │
│  └─► Arrête ET supprime les containers                         │
│                                                                 │
│  État : 🟢 Démarré  ────────────►  ⭕ Supprimé                 │
│                                                                 │
│  ⚠️  Les volumes (db_data) sont CONSERVÉS                      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose down -v                                         │
│  ▲                                                              │
│  └─► Supprime containers ET volumes                            │
│                                                                 │
│  ⚠️  ATTENTION : Toutes les données MySQL seront PERDUES !     │
│                                                                 │
│  État : 🟢 Démarré  ────────────►  💀 Tout supprimé           │
└─────────────────────────────────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                     EXÉCUTION DE COMMANDES                        ║
╚═══════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose exec php [commande]                             │
│  ▲                       ▲                                      │
│  │                       └─► Commande à exécuter               │
│  └─► Service cible                                              │
│                                                                 │
│  Exemples :                                                     │
│                                                                 │
│  docker-compose exec php composer install                      │
│  docker-compose exec php php bin/console make:entity           │
│  docker-compose exec php php bin/console cache:clear           │
│  docker-compose exec php bash  ◄─── Shell interactif           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  docker-compose logs [service]                                  │
│  ▲                                                              │
│  └─► Affiche les logs d'un service                             │
│                                                                 │
│  docker-compose logs php        ◄─── Logs PHP/Apache           │
│  docker-compose logs db         ◄─── Logs MySQL                │
│  docker-compose logs -f php     ◄─── Suivi en temps réel       │
└─────────────────────────────────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                     COMMANDES SYMFONY                             ║
╚═══════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────┐
│  Créer une entité                                               │
│  docker-compose exec php php bin/console make:entity User       │
│                                                                 │
│  Résultat : src/Entity/User.php créé                            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  Créer une migration                                            │
│  docker-compose exec php php bin/console make:migration         │
│                                                                 │
│  Résultat : migrations/VersionXXX.php créé                      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  Exécuter les migrations                                        │
│  docker-compose exec php php bin/console doctrine:migrations:migrate│
│                                                                 │
│  Résultat : Tables créées dans MySQL                            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  Créer un contrôleur                                            │
│  docker-compose exec php php bin/console make:controller        │
│                                                                 │
│  Résultat : src/Controller/XXXController.php créé               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  Vider le cache                                                 │
│  docker-compose exec php php bin/console cache:clear            │
│                                                                 │
│  Utile quand les changements ne sont pas pris en compte        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔍 DÉBOGAGE VISUEL

```
╔═══════════════════════════════════════════════════════════════════╗
║             PROBLÈME : "Connection refused" (MySQL)               ║
╚═══════════════════════════════════════════════════════════════════╝

1️⃣ Vérifier que MySQL est démarré

Terminal: docker-compose ps

    Attendu :
    ┌────────────────────────────────────────┐
    │ NAME         STATUS       PORTS        │
    │ symfony_db   Up 10 min    3306->3306   │  ◄─── STATUS doit être "Up"
    └────────────────────────────────────────┘

    Si STATUS = "Exit" :
    ┌────────────────────────────────────────┐
    │ NAME         STATUS       PORTS        │
    │ symfony_db   Exit (1)     -            │  ◄─── ❌ Container arrêté
    └────────────────────────────────────────┘

    Solution : docker-compose logs db
               (voir l'erreur dans les logs)


2️⃣ Vérifier la configuration Symfony

symfony/.env :

    ❌ Incorrect :
    ┌──────────────────────────────────────────────────┐
    │ DATABASE_URL="mysql://root:root@localhost:3306   │
    │                                     ^^^^^^^^^    │
    │                                     ERREUR !     │
    └──────────────────────────────────────────────────┘

    ✅ Correct :
    ┌──────────────────────────────────────────────────┐
    │ DATABASE_URL="mysql://root:root@db:3306          │
    │                                     ^^           │
    │                            Nom du service !      │
    └──────────────────────────────────────────────────┘


3️⃣ Tester la connexion depuis le container PHP

Terminal: docker-compose exec php ping db

    ✅ Si ça fonctionne :
    ┌──────────────────────────────────────────────────┐
    │ PING db (172.18.0.3): 56 data bytes             │
    │ 64 bytes from 172.18.0.3: icmp_seq=0 ttl=64     │
    └──────────────────────────────────────────────────┘

    ❌ Si ça échoue :
    ┌──────────────────────────────────────────────────┐
    │ ping: unknown host db                           │
    └──────────────────────────────────────────────────┘
    → Vérifier que les services sont sur le même réseau


╔═══════════════════════════════════════════════════════════════════╗
║             PROBLÈME : "Page not found" (Symfony)                 ║
╚═══════════════════════════════════════════════════════════════════╝

1️⃣ Vérifier l'accès à index.php

http://localhost:8000/index.php

    ✅ Si ça fonctionne : problème de configuration Apache
    ❌ Si ça échoue : problème plus profond


2️⃣ Vérifier les logs Apache

Terminal: docker-compose logs php

    Rechercher :
    ┌──────────────────────────────────────────────────┐
    │ [php:error] [pid 23] PHP Fatal error:           │
    │ Uncaught Error: Class '...' not found           │
    └──────────────────────────────────────────────────┘


3️⃣ Vider le cache Symfony

Terminal: docker-compose exec php php bin/console cache:clear


╔═══════════════════════════════════════════════════════════════════╗
║             PROBLÈME : Changements non pris en compte             ║
╚═══════════════════════════════════════════════════════════════════╝

1️⃣ Vérifier que le volume est bien monté

Terminal: docker-compose exec php ls -la /var/www/html

    ✅ Attendu :
    ┌──────────────────────────────────────────────────┐
    │ drwxr-xr-x  src                                  │
    │ drwxr-xr-x  public                               │
    │ -rw-r--r--  .env                                 │
    └──────────────────────────────────────────────────┘

    ❌ Si vide :
    ┌──────────────────────────────────────────────────┐
    │ total 0                                          │
    └──────────────────────────────────────────────────┘
    → Problème de montage du volume


2️⃣ Reconstruire le container

Terminal: docker-compose down
          docker-compose up -d --build
```

---

## 📊 TABLEAU RÉCAPITULATIF

```
╔═══════════════════════════════════════════════════════════════════╗
║                      POINTS D'ACCÈS                               ║
╚═══════════════════════════════════════════════════════════════════╝

┌───────────────┬──────────────────────┬────────────────────────────┐
│   SERVICE     │         URL          │         USAGE              │
├───────────────┼──────────────────────┼────────────────────────────┤
│ Symfony       │ localhost:8000       │ Votre application web      │
│               │                      │                            │
├───────────────┼──────────────────────┼────────────────────────────┤
│ phpMyAdmin    │ localhost:8080       │ Gestion de la BDD          │
│               │ Login: root/root     │ Voir/modifier les tables   │
│               │                      │                            │
├───────────────┼──────────────────────┼────────────────────────────┤
│ MailHog       │ localhost:8025       │ Voir les emails interceptés│
│               │                      │                            │
├───────────────┼──────────────────────┼────────────────────────────┤
│ MySQL (direct)│ localhost:3306       │ Connexion directe MySQL    │
│               │ (optionnel)          │ (avec client DB externe)   │
└───────────────┴──────────────────────┴────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                  HOSTNAMES DANS DOCKER                            ║
╚═══════════════════════════════════════════════════════════════════╝

┌────────────────────┬───────────────────────────────────────────────┐
│   SERVICE          │         HOSTNAME (dans le réseau Docker)      │
├────────────────────┼───────────────────────────────────────────────┤
│ PHP                │ php                                           │
│                    │ (rarement utilisé directement)                │
│                    │                                               │
├────────────────────┼───────────────────────────────────────────────┤
│ MySQL              │ db                                            │
│                    │ Utilisé dans DATABASE_URL                     │
│                    │                                               │
├────────────────────┼───────────────────────────────────────────────┤
│ phpMyAdmin         │ phpmyadmin                                    │
│                    │ (rarement utilisé)                            │
│                    │                                               │
├────────────────────┼───────────────────────────────────────────────┤
│ MailHog            │ mailhog                                       │
│                    │ Utilisé dans MAILER_DSN                       │
└────────────────────┴───────────────────────────────────────────────┘


╔═══════════════════════════════════════════════════════════════════╗
║                  CONFIGURATION SYMFONY (.env)                     ║
╚═══════════════════════════════════════════════════════════════════╝

DATABASE_URL="mysql://root:root@db:3306/symfony_db?serverVersion=8.0"
               │    │    │    │     │    └─► Version MySQL
               │    │    │    │     └──────► Nom de la base
               │    │    │    └────────────► Port MySQL
               │    │    └─────────────────► ⭐ Hostname Docker
               │    └──────────────────────► Mot de passe
               └───────────────────────────► Utilisateur

MAILER_DSN=smtp://mailhog:1025
                  └───────┘ └──┘
                       │      └──► Port SMTP
                       └─────────► ⭐ Hostname Docker
```

---

## 🎓 QUIZ DE RÉVISION

```
╔═══════════════════════════════════════════════════════════════════╗
║                        TESTEZ VOS CONNAISSANCES                   ║
╚═══════════════════════════════════════════════════════════════════╝

❓ Q1 : J'édite un fichier dans ./symfony/src/Controller/
        Quand le changement est-il visible dans le container ?

    A) Après un redémarrage du container
    B) Immédiatement (grâce au bind mount)
    C) Après reconstruction de l'image

    Réponse : [ Faites défiler pour voir ]








    ✅ B) Immédiatement !
       Le bind mount synchronise en temps réel.


─────────────────────────────────────────────────────────────────────

❓ Q2 : Je supprime le container MySQL avec "docker-compose down"
        Qu'arrive-t-il aux données ?

    A) Tout est perdu
    B) Les données sont conservées dans le volume db_data
    C) Ça dépend de la phase de la lune

    Réponse : [ Faites défiler pour voir ]








    ✅ B) Les données sont conservées !
       Le volume db_data persiste même après suppression du container.

       ⚠️  Sauf si vous utilisez : docker-compose down -v


─────────────────────────────────────────────────────────────────────

❓ Q3 : Dans DATABASE_URL, pourquoi utiliser @db et pas @localhost ?

    Schéma mental :

    ┌──────────────┐
    │ Container PHP│
    │              │
    │ localhost ──┼──► Lui-même (pas MySQL !)
    │              │
    │ db ──────────┼──► Container MySQL ✅
    └──────────────┘

    Réponse :
    Chaque container a son propre "localhost".
    Le nom du service ("db") est résolu par le DNS Docker
    vers l'adresse IP du container MySQL.


─────────────────────────────────────────────────────────────────────

❓ Q4 : Comment voir les logs d'erreur PHP ?

    Réponse : [ Faites défiler pour voir ]








    ✅ docker-compose logs php
       ou
       docker-compose logs -f php (suivi en temps réel)


─────────────────────────────────────────────────────────────────────

❓ Q5 : Quel est le point d'entrée de Symfony ?

    A) /var/www/html/index.php
    B) /var/www/html/src/Kernel.php
    C) /var/www/html/public/index.php

    Réponse : [ Faites défiler pour voir ]








    ✅ C) /var/www/html/public/index.php

       C'est le "front controller" : toutes les requêtes passent par là.
       Apache redirige tout vers ce fichier.
```

---

## 🎯 AIDE-MÉMOIRE : À IMPRIMER

```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    🚀 COMMANDES ESSENTIELLES                     ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

  DÉMARRAGE
  ─────────
  docker-compose up -d              Démarrer les containers
  docker-compose ps                 Voir les containers actifs
  docker-compose logs -f php        Voir les logs en temps réel

  ARRÊT
  ─────
  docker-compose stop               Arrêter (peut redémarrer)
  docker-compose down               Arrêter + supprimer
  docker-compose down -v            ⚠️ Supprimer + volumes (données !)

  SYMFONY
  ───────
  dc exec php composer require X    Installer un bundle
  dc exec php php bin/console make:entity    Créer une entité
  dc exec php php bin/console make:migration Créer une migration
  dc exec php php bin/console d:m:m          Exécuter les migrations
  dc exec php php bin/console cache:clear    Vider le cache

  DÉBOGAGE
  ────────
  dc exec php bash                  Shell dans le container
  dc logs php                       Logs PHP/Apache
  dc logs db                        Logs MySQL

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                         📍 URLS IMPORTANTES                      ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

  http://localhost:8000      → Application Symfony
  http://localhost:8080      → phpMyAdmin (root/root)
  http://localhost:8025      → MailHog (emails)

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    ⚙️ CONFIGURATION SYMFONY                      ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

  symfony/.env :

  DATABASE_URL="mysql://root:root@db:3306/symfony_db?serverVersion=8.0"
                                   ^^
                          ⭐ Nom du service !

  MAILER_DSN=smtp://mailhog:1025
                    ^^^^^^^
              ⭐ Nom du service !

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    ⚠️ PIÈGES À ÉVITER                            ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

  ❌ DATABASE_URL="...@localhost:3306..."
  ✅ DATABASE_URL="...@db:3306..."

  ❌ docker-compose down -v (supprime les données !)
  ✅ docker-compose down (garde les données)

  ❌ Oublier de reconstruire après modification du Dockerfile
  ✅ docker-compose up -d --build
```

---

## 🏗️ CRÉATION DES SERVICES : GUIDE COMPLET

### 📋 STRUCTURE DES FICHIERS

```
Sprint-MiniRSN/
├── docker/
│   └── php/
│       └── Dockerfile          ← Configuration de l'image PHP
├── docker-compose.yml          ← Orchestration des services
└── symfony/                    ← Votre projet Symfony (créé après)
```

---

### 📄 1. LE DOCKERFILE (docker/php/Dockerfile)

```dockerfile
# ════════════════════════════════════════════════════════════════════
# IMAGE DE BASE : PHP avec Apache
# ════════════════════════════════════════════════════════════════════

FROM php:8.3-apache
# │    └─────────┘ Version de PHP + serveur web intégré
# │
# └─► Télécharge l'image officielle PHP 8.3 avec Apache depuis Docker Hub
#     Cette image contient :
#     • PHP 8.3 (interpréteur)
#     • Apache 2.4 (serveur web)
#     • Debian Linux (système d'exploitation de base)


# ════════════════════════════════════════════════════════════════════
# INSTALLATION DES DÉPENDANCES SYSTÈME
# ════════════════════════════════════════════════════════════════════

RUN apt-get update && apt-get install -y \
# │   └──────────┘    └───────────────┘
# │        │                 │
# │        │                 └─► Installe les paquets suivants
# │        └───────────────────► Met à jour la liste des paquets disponibles
# │
# └─► RUN = Exécute une commande PENDANT la construction de l'image

    git \
    # Système de versionnement de code
    # Nécessaire pour : Composer (téléchargement de dépendances depuis GitHub)

    unzip \
    # Utilitaire pour décompresser des archives .zip
    # Nécessaire pour : Composer (extraction des paquets PHP)

    libicu-dev \
    # Bibliothèque de développement pour l'internationalisation (i18n)
    # Nécessaire pour : Extension PHP "intl"
    # Utilisée par Symfony pour : traductions, formatage dates/nombres selon la locale

    libzip-dev \
    # Bibliothèque de développement pour manipuler les archives ZIP
    # Nécessaire pour : Extension PHP "zip"

    && rm -rf /var/lib/apt/lists/*
    # └─► Nettoie le cache apt pour réduire la taille de l'image
    #     (bonne pratique Docker : garder les images légères)


# ════════════════════════════════════════════════════════════════════
# INSTALLATION DES EXTENSIONS PHP
# ════════════════════════════════════════════════════════════════════

RUN docker-php-ext-install pdo pdo_mysql intl zip opcache
# │  └──────────────────┘ └────────────────────────────────┘
# │           │                        │
# │           │                        └─► Liste des extensions à installer
# │           │
# │           └─► Script fourni par l'image officielle PHP
# │               pour compiler et installer des extensions
# │
# └─► RUN = Exécute pendant la construction

# Détail des extensions :
#
# • pdo (PHP Data Objects)
#   → Interface abstraite pour accéder aux bases de données
#   → Utilisée par : Doctrine ORM
#
# • pdo_mysql
#   → Driver MySQL pour PDO
#   → Permet la connexion à MySQL/MariaDB
#
# • intl (Internationalization)
#   → Fonctions d'internationalisation (traductions, formats)
#   → Requise par : Symfony (gestion des locales)
#
# • zip
#   → Manipulation d'archives ZIP depuis PHP
#   → Utilisée par : Composer
#
# • opcache
#   → Cache de bytecode PHP pour améliorer les performances
#   → Stocke le code PHP compilé en mémoire


# ════════════════════════════════════════════════════════════════════
# INSTALLATION DE COMPOSER (gestionnaire de dépendances PHP)
# ════════════════════════════════════════════════════════════════════

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# │    └──────────────────┘ └──────────────────┘ └──────────────────┘
# │            │                      │                   │
# │            │                      │                   └─► Destination dans notre image
# │            │                      │
# │            │                      └─► Fichier source à copier
# │            │
# │            └─► Image source (officielle Composer)
# │
# └─► COPY --from = Copie un fichier depuis une autre image Docker
#                   (multi-stage build)

# Explication :
# Au lieu de télécharger et installer Composer manuellement,
# on le copie directement depuis l'image officielle "composer:latest".
# C'est plus rapide et plus propre !


# ════════════════════════════════════════════════════════════════════
# ACTIVATION DU MODULE APACHE REWRITE
# ════════════════════════════════════════════════════════════════════

RUN a2enmod rewrite
# │  └──────┘
# │      │
# │      └─► Apache2 Enable Module (commande Apache)
# │
# └─► Active le module "mod_rewrite" d'Apache

# Pourquoi ?
# Le module "rewrite" permet à Apache de réécrire les URLs.
# Symfony utilise cela pour :
# • Rediriger toutes les requêtes vers public/index.php
# • Créer des URLs "propres" : /users au lieu de /index.php?page=users
#
# Exemple :
# GET /users  ──(mod_rewrite)──► /public/index.php


# ════════════════════════════════════════════════════════════════════
# DÉFINITION DU RÉPERTOIRE DE TRAVAIL
# ════════════════════════════════════════════════════════════════════

WORKDIR /var/www/html
# │       └───────────┘
# │            │
# │            └─► Chemin absolu dans le container
# │
# └─► Change le répertoire de travail pour les commandes suivantes

# Conséquences :
# • Toutes les commandes suivantes (RUN, CMD, COPY) seront exécutées depuis ce répertoire
# • C'est là que le volume sera monté (code Symfony)
# • Apache est configuré pour servir depuis ce répertoire par défaut


# ════════════════════════════════════════════════════════════════════
# CONFIGURATION DES PERMISSIONS
# ════════════════════════════════════════════════════════════════════

RUN chown -R www-data:www-data /var/www/html
# │  └─────┘    └──────────────┘ └───────────┘
# │     │              │              │
# │     │              │              └─► Répertoire cible
# │     │              │
# │     │              └─► Propriétaire:Groupe
# │     │                  (utilisateur Apache par défaut)
# │     │
# │     └─► Change Owner (commande Linux)
# │         -R = Récursif (tous les sous-dossiers)
# │
# └─► Change le propriétaire du répertoire

# Pourquoi ?
# • Apache s'exécute sous l'utilisateur "www-data" pour la sécurité
# • Symfony a besoin d'écrire dans var/cache/ et var/log/
# • Sans cette ligne, erreurs "Permission denied"


# ════════════════════════════════════════════════════════════════════
# EXPOSITION DU PORT
# ════════════════════════════════════════════════════════════════════

EXPOSE 80
# │    └─┘
# │     │
# │     └─► Numéro de port
# │
# └─► Indique que le container écoute sur le port 80

# Important :
# • C'est une DOCUMENTATION, pas une action
# • Le port n'est pas réellement ouvert automatiquement
# • Il faut le mapper dans docker-compose.yml : "8000:80"
#
# Schéma :
# localhost:8000 ──(mapping)──► Container:80 ──► Apache


# ════════════════════════════════════════════════════════════════════
# COMMANDE PAR DÉFAUT
# ════════════════════════════════════════════════════════════════════

CMD ["apache2-foreground"]
# │  └───────────────────┘
# │           │
# │           └─► Commande à exécuter au démarrage du container
# │
# └─► CMD = Commande par défaut (peut être surchargée)

# Explication :
# • "apache2-foreground" démarre Apache en mode foreground (premier plan)
# • En mode foreground, Apache reste actif et garde le container en vie
# • Si Apache s'arrête, le container s'arrête aussi
#
# Différence CMD vs RUN :
# • RUN  : Exécuté pendant la CONSTRUCTION de l'image
# • CMD  : Exécuté au DÉMARRAGE du container
```

---

### 🐳 2. LE DOCKER-COMPOSE.YML

```yaml
# ════════════════════════════════════════════════════════════════════
# VERSION DU FORMAT DOCKER COMPOSE
# ════════════════════════════════════════════════════════════════════

version: '3.8'
# └───┘   └───┘
#   │       │
#   │       └─► Numéro de version du format de fichier
#   │
#   └─► Clé de configuration

# Version 3.8 supporte :
# • Les dépendances entre services (depends_on)
# • Les healthchecks
# • Les secrets
# • La plupart des fonctionnalités modernes


# ════════════════════════════════════════════════════════════════════
# DÉFINITION DES SERVICES
# ════════════════════════════════════════════════════════════════════

services:
# └──────┘
#    │
#    └─► Section contenant tous les containers à créer


  # ┌──────────────────────────────────────────────────────────────┐
  # │                    SERVICE 1 : PHP + APACHE                  │
  # └──────────────────────────────────────────────────────────────┘

  php:
  # └─┘
  #  │
  #  └─► Nom du service (libre, mais doit être unique)
  #      • Utilisé pour référencer le service dans docker-compose
  #      • Devient le hostname dans le réseau Docker

    container_name: symfony_php
    # └────────────┘  └──────────┘
    #       │              │
    #       │              └─► Nom du container (visible avec docker ps)
    #       │
    #       └─► Clé de configuration
    #
    # Si omis, Docker Compose génère un nom automatique :
    # projet_service_1 (ex: sprint-minirsn_php_1)

    build:
    # └───┘
    #   │
    #   └─► Indique qu'on va CONSTRUIRE une image (au lieu d'en télécharger une)

      context: ./docker/php
      # └─────┘  └──────────┘
      #    │          │
      #    │          └─► Chemin du répertoire de construction (relatif au docker-compose.yml)
      #    │
      #    └─► Clé : Définit le contexte de construction
      #
      # Le contexte est le répertoire envoyé au daemon Docker.
      # Tous les chemins dans le Dockerfile sont relatifs à ce contexte.

      dockerfile: Dockerfile
      # └────────┘  └────────┘
      #     │           │
      #     │           └─► Nom du fichier Dockerfile à utiliser
      #     │
      #     └─► Clé : Spécifie le fichier de construction
      #
      # Par défaut : "Dockerfile"
      # Utile si vous avez plusieurs Dockerfiles (Dockerfile.dev, Dockerfile.prod)

    ports:
    # └───┘
    #   │
    #   └─► Section de mapping des ports

      - "8000:80"
      # └──┘ └─┘ └┘
      #   │   │   │
      #   │   │   └─► Port DANS le container (où Apache écoute)
      #   │   │
      #   │   └─────► Séparateur
      #   │
      #   └─────────► Port sur VOTRE machine (localhost)
      #
      # Schéma :
      # Navigateur → localhost:8000 → Docker → Container:80 → Apache

    volumes:
    # └─────┘
    #    │
    #    └─► Section de montage des volumes

      - ./symfony:/var/www/html
      # └────────┘ └─────────────┘
      #      │            │
      #      │            └─► Chemin DANS le container
      #      │
      #      └─► Chemin sur VOTRE machine (relatif au docker-compose.yml)
      #
      # Type : Bind mount (synchronisation en temps réel)
      # • Vous modifiez : ./symfony/src/Controller/UserController.php
      # • Visible dans le container : /var/www/html/src/Controller/UserController.php
      # • Instantanément !

    networks:
    # └──────┘
    #    │
    #    └─► Section des réseaux auxquels ce service appartient

      - symfony_network
      # └───────────────┘
      #        │
      #        └─► Nom du réseau (défini en bas du fichier)
      #
      # Tous les services sur ce réseau peuvent communiquer entre eux
      # via leur nom de service (ex: php, db, mailhog)

    depends_on:
    # └────────┘
    #     │
    #     └─► Définit les dépendances de démarrage

      - db
      # └┘
      #  │
      #  └─► Attend que le service "db" soit démarré avant de démarrer
      #
      # ⚠️ Important :
      # "démarré" ≠ "prêt à accepter des connexions"
      # Le service PHP peut démarrer avant que MySQL soit complètement initialisé.
      # Solution : Utiliser healthcheck (avancé) ou attendre manuellement.

    environment:
    # └─────────┘
    #      │
    #      └─► Définit des variables d'environnement dans le container

      - PHP_MEMORY_LIMIT=512M
      # └───────────────┘ └────┘
      #         │            │
      #         │            └─► Valeur
      #         │
      #         └─► Nom de la variable
      #
      # Ces variables sont accessibles dans PHP via $_ENV ou getenv()
      # Exemple : getenv('PHP_MEMORY_LIMIT') retourne "512M"


  # ┌──────────────────────────────────────────────────────────────┐
  # │                       SERVICE 2 : MYSQL                      │
  # └──────────────────────────────────────────────────────────────┘

  db:
  # └┘
  #  │
  #  └─► Nom du service = Hostname dans le réseau Docker
  #      ⚠️ C'est ce nom qu'on utilise dans DATABASE_URL !

    container_name: symfony_db
    image: mysql:8.0
    # └───┘  └────────┘
    #   │        │
    #   │        └─► Image à télécharger depuis Docker Hub
    #   │
    #   └─► Clé : Utilise une image existante (pas de build)
    #
    # Format : repository:tag
    # • mysql = repository officiel sur Docker Hub
    # • 8.0 = version (tag)

    environment:
    # Variables d'environnement spécifiques à MySQL

      MYSQL_ROOT_PASSWORD: root
      # └──────────────────┘ └──┘
      #          │             │
      #          │             └─► Mot de passe du super-utilisateur MySQL
      #          │
      #          └─► Variable lue par le script d'initialisation de MySQL
      #
      # Au premier démarrage, MySQL :
      # • Crée l'utilisateur "root"
      # • Définit son mot de passe à "root"

      MYSQL_DATABASE: symfony_db
      # └───────────┘  └─────────┘
      #       │             │
      #       │             └─► Nom de la base de données à créer
      #       │
      #       └─► Variable lue par le script d'initialisation
      #
      # Au premier démarrage, MySQL crée automatiquement cette base.

    ports:
      - "3306:3306"
      # └───┘  └───┘
      #   │      │
      #   │      └─► Port MySQL dans le container (port par défaut MySQL)
      #   │
      #   └─► Port sur votre machine
      #
      # Ce mapping est OPTIONNEL.
      # Utile si vous voulez vous connecter à MySQL depuis votre machine
      # avec un client comme MySQL Workbench ou DBeaver.
      #
      # ⚠️ Depuis les autres containers, utilisez : db:3306 (PAS localhost:3306)

    volumes:
      - db_data:/var/lib/mysql
      # └──────┘ └─────────────┘
      #    │            │
      #    │            └─► Répertoire DANS le container où MySQL stocke ses données
      #    │
      #    └─► Nom du volume (volume nommé, défini en bas du fichier)
      #
      # Type : Volume nommé (géré par Docker)
      # • Les données survivent à la suppression du container
      # • Stockées dans un endroit géré par Docker (pas directement accessible)
      #
      # Cycle de vie :
      # 1. Container créé → Volume attaché
      # 2. MySQL écrit des données → Stockées dans le volume
      # 3. Container supprimé → Volume persiste
      # 4. Nouveau container créé → Volume réattaché → Données récupérées

    networks:
      - symfony_network
      # Connecté au même réseau que les autres services


  # ┌──────────────────────────────────────────────────────────────┐
  # │                    SERVICE 3 : PHPMYADMIN                    │
  # └──────────────────────────────────────────────────────────────┘

  phpmyadmin:
    container_name: symfony_phpmyadmin
    image: phpmyadmin/phpmyadmin
    # └────────────────────────┘
    #            │
    #            └─► Image officielle phpMyAdmin

    environment:
      PMA_HOST: db
      # └──────┘ └┘
      #    │      │
      #    │      └─► Valeur : nom du service MySQL (hostname Docker)
      #    │
      #    └─► Variable lue par phpMyAdmin pour savoir où se connecter
      #
      # phpMyAdmin va se connecter à "db:3306" (service MySQL)

      PMA_USER: root
      # └──────┘ └──┘
      #    │      │
      #    │      └─► Nom d'utilisateur MySQL (auto-rempli dans l'interface)
      #    │
      #    └─► Variable pour la connexion automatique

      PMA_PASSWORD: root
      # └──────────┘ └──┘
      #      │         │
      #      │         └─► Mot de passe MySQL
      #      │
      #      └─► Variable pour la connexion automatique
      #
      # ⚠️ En production, ne PAS mettre le mot de passe ici !
      #    Utiliser des secrets Docker ou des variables d'environnement externes.

    ports:
      - "8080:80"
      # └───┘  └─┘
      #   │     │
      #   │     └─► phpMyAdmin écoute sur le port 80 (Apache)
      #   │
      #   └─► Accessible sur votre machine via http://localhost:8080

    depends_on:
      - db
      # Attend que MySQL soit démarré avant de démarrer phpMyAdmin

    networks:
      - symfony_network


  # ┌──────────────────────────────────────────────────────────────┐
  # │                     SERVICE 4 : MAILHOG                      │
  # └──────────────────────────────────────────────────────────────┘

  mailhog:
    container_name: symfony_mailhog
    image: mailhog/mailhog
    # └────────────────┘
    #        │
    #        └─► Image officielle MailHog
    #
    # MailHog est un serveur SMTP de test :
    # • Intercepte tous les emails
    # • Ne les envoie PAS réellement
    # • Les affiche dans une interface web

    ports:
      - "1025:1025"
      # └───┘  └───┘
      #   │      │
      #   │      └─► Port SMTP dans le container (serveur d'envoi d'emails)
      #   │
      #   └─► Port SMTP accessible depuis votre machine
      #
      # Symfony se connecte à : smtp://mailhog:1025

      - "8025:8025"
      # └───┘  └───┘
      #   │      │
      #   │      └─► Port de l'interface web dans le container
      #   │
      #   └─► Interface accessible via http://localhost:8025
      #
      # Vous pouvez voir tous les emails interceptés ici !

    networks:
      - symfony_network


# ════════════════════════════════════════════════════════════════════
# DÉFINITION DES RÉSEAUX
# ════════════════════════════════════════════════════════════════════

networks:
# └──────┘
#    │
#    └─► Section de définition des réseaux

  symfony_network:
  # └───────────────┘
  #        │
  #        └─► Nom du réseau (référencé dans chaque service)

    driver: bridge
    # └────┘ └────┘
    #   │      │
    #   │      └─► Type de réseau
    #   │
    #   └─► Clé : Spécifie le driver réseau
    #
    # Driver "bridge" :
    # • Crée un réseau privé virtuel pour les containers
    # • Les containers peuvent communiquer entre eux via leur nom de service
    # • Isolation : les containers sur des réseaux différents ne peuvent pas communiquer
    #
    # Schéma :
    # ┌─────────────────────────────────┐
    # │   Réseau : symfony_network      │
    # │                                 │
    # │   ┌────┐  ┌────┐  ┌────────┐   │
    # │   │php │  │ db │  │mailhog │   │
    # │   └────┘  └────┘  └────────┘   │
    # │     ▲       ▲         ▲        │
    # │     └───────┴─────────┘        │
    # │    Peuvent communiquer         │
    # │    via leurs noms              │
    # └─────────────────────────────────┘


# ════════════════════════════════════════════════════════════════════
# DÉFINITION DES VOLUMES
# ════════════════════════════════════════════════════════════════════

volumes:
# └─────┘
#    │
#    └─► Section de définition des volumes nommés

  db_data:
  # └──────┘
  #    │
  #    └─► Nom du volume (référencé dans le service "db")
  #
  # Volume nommé géré par Docker :
  # • Docker crée et gère ce volume automatiquement
  # • Stocké dans : C:\ProgramData\docker\volumes\db_data\ (Windows)
  #                 /var/lib/docker/volumes/db_data/ (Linux)
  # • Persiste même si le container est supprimé
  # • Supprimé uniquement avec : docker-compose down -v
  #
  # Schéma du cycle de vie :
  #
  # 1. Premier docker-compose up
  #    ┌──────────┐       ┌─────────┐
  #    │Container │◄──────│ Volume  │  (créé vide)
  #    │  MySQL   │       │ db_data │
  #    └────┬─────┘       └─────────┘
  #         │
  #         └─► Écrit des données
  #
  # 2. docker-compose down
  #    ┌──────────┐       ┌─────────┐
  #    │    💀    │       │ Volume  │  (données conservées)
  #    │ Container│   X   │ db_data │
  #    │ supprimé │       └─────────┘
  #    └──────────┘
  #
  # 3. Nouveau docker-compose up
  #    ┌──────────┐       ┌─────────┐
  #    │Container │◄──────│ Volume  │  (données récupérées !)
  #    │  MySQL   │       │ db_data │
  #    └──────────┘       └─────────┘
```

---

### 🎯 RÉSUMÉ : ORDRE DE CRÉATION

```
┌────────────────────────────────────────────────────────────────────┐
│                    ÉTAPES DE MISE EN PLACE                         │
└────────────────────────────────────────────────────────────────────┘

1️⃣ CRÉER LA STRUCTURE

   Sprint-MiniRSN/
   ├── docker/
   │   └── php/
   │       └── Dockerfile        ← Créer ce fichier
   └── docker-compose.yml        ← Créer ce fichier


2️⃣ CONSTRUIRE ET DÉMARRER

   Terminal : docker-compose up -d --build

   Ce qui se passe :
   ┌──────────────────────────────────────────────────────┐
   │ 1. Lecture du docker-compose.yml                    │
   │ 2. Construction de l'image PHP (Dockerfile)         │
   │ 3. Téléchargement des images MySQL, phpMyAdmin...   │
   │ 4. Création du réseau "symfony_network"             │
   │ 5. Création du volume "db_data"                     │
   │ 6. Démarrage des 4 containers                       │
   │    ├── symfony_php                                  │
   │    ├── symfony_db                                   │
   │    ├── symfony_phpmyadmin                           │
   │    └── symfony_mailhog                              │
   │ 7. Connexion des containers au réseau               │
   │ 8. Montage des volumes                              │
   └──────────────────────────────────────────────────────┘


3️⃣ CRÉER LE PROJET SYMFONY

   Terminal : docker-compose exec php composer create-project symfony/skeleton symfony

   Ce qui se passe :
   ┌──────────────────────────────────────────────────────┐
   │ • Composer s'exécute DANS le container PHP          │
   │ • Télécharge Symfony depuis Packagist               │
   │ • Crée le projet dans /var/www/html/symfony          │
   │ • Grâce au bind mount, visible dans ./symfony/       │
   └──────────────────────────────────────────────────────┘


4️⃣ CONFIGURER SYMFONY

   Éditer : symfony/.env

   DATABASE_URL="mysql://root:root@db:3306/symfony_db?serverVersion=8.0"
                                    ^^
                            Nom du service Docker !


5️⃣ TESTER

   Navigateur : http://localhost:8000

   ✅ Vous devriez voir la page d'accueil Symfony !
```

---

### 🔍 COMPARAISON : Build vs Image

```
╔════════════════════════════════════════════════════════════════════╗
║                         build: (PHP)                               ║
╚════════════════════════════════════════════════════════════════════╝

services:
  php:
    build:
      context: ./docker/php
      dockerfile: Dockerfile

Utilisation : Quand vous avez des besoins spécifiques

┌──────────────────────────────────────────────────────────────────┐
│ Avantages :                                                      │
│ ✅ Personnalisation totale (extensions PHP, config...)           │
│ ✅ Optimisation pour votre projet                                │
│ ✅ Contrôle sur chaque couche                                    │
│                                                                  │
│ Inconvénients :                                                  │
│ ⚠️  Temps de construction initial                                │
│ ⚠️  Nécessite de comprendre le Dockerfile                        │
└──────────────────────────────────────────────────────────────────┘


╔════════════════════════════════════════════════════════════════════╗
║                      image: (MySQL, phpMyAdmin)                    ║
╚════════════════════════════════════════════════════════════════════╝

services:
  db:
    image: mysql:8.0

Utilisation : Quand une image officielle suffit

┌──────────────────────────────────────────────────────────────────┐
│ Avantages :                                                      │
│ ✅ Démarrage immédiat (image déjà construite)                    │
│ ✅ Maintenu par la communauté officielle                         │
│ ✅ Pas de Dockerfile à gérer                                     │
│                                                                  │
│ Inconvénients :                                                  │
│ ⚠️  Moins de personnalisation                                    │
│ ⚠️  Configuration via variables d'environnement uniquement       │
└──────────────────────────────────────────────────────────────────┘
```

---

### 💡 CONCEPTS CLÉS À RETENIR

```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                   DOCKERFILE vs DOCKER-COMPOSE                  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

╔═══════════════════════════════════════════════════════════════╗
║ DOCKERFILE                                                    ║
║ • Définit UNE image (recette de cuisine)                      ║
║ • Utilisé avec : docker build                                 ║
║ • Instructions : FROM, RUN, COPY, CMD...                      ║
║ • Exemple : "Comment construire mon image PHP personnalisée"  ║
╚═══════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════╗
║ DOCKER-COMPOSE.YML                                            ║
║ • Orchestre PLUSIEURS services (menu complet)                 ║
║ • Utilisé avec : docker-compose up                            ║
║ • Clés : services, networks, volumes                          ║
║ • Exemple : "Comment faire fonctionner PHP + MySQL ensemble"  ║
╚═══════════════════════════════════════════════════════════════╝


┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                        TYPES DE VOLUMES                         ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

╔═══════════════════════════════════════════════════════════════╗
║ BIND MOUNT : ./symfony:/var/www/html                          ║
║ • Synchronisation en temps réel                               ║
║ • Vous contrôlez l'emplacement (dossier sur votre machine)    ║
║ • Utilisé pour : Code source, configuration                   ║
║ • Modifiable depuis votre IDE                                 ║
╚═══════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════╗
║ VOLUME NOMMÉ : db_data:/var/lib/mysql                         ║
║ • Géré par Docker                                             ║
║ • Persist après suppression du container                      ║
║ • Utilisé pour : Données de base de données                   ║
║ • Non directement modifiable (géré par Docker)                ║
╚═══════════════════════════════════════════════════════════════╝


┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                        RÉSEAU DOCKER                            ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

DNS Automatique :
┌──────────────────────────────────────────────────────────────┐
│ Nom du service = Hostname                                    │
│                                                              │
│ Service "db" → Accessible via "db" depuis les autres        │
│                containers sur le même réseau                 │
│                                                              │
│ Service "php" → Accessible via "php"                         │
│ Service "mailhog" → Accessible via "mailhog"                 │
└──────────────────────────────────────────────────────────────┘

Isolation :
┌──────────────────────────────────────────────────────────────┐
│ Containers sur des réseaux différents = Pas de communication│
│ Containers sur le même réseau = Communication possible      │
└──────────────────────────────────────────────────────────────┘
```

---

**🎨 Version visuelle créée - Bonne révision ! 📚**

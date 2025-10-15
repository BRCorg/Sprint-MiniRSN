# Gestion des dumps de base de données

Ce dossier contient les scripts et fichiers pour partager la base de données entre les membres de l'équipe.

## Pourquoi utiliser des dumps ?

Les volumes Docker sont **locaux à chaque machine**. Quand vous clonez le projet, vous avez un volume vide.
Les dumps SQL permettent de partager les données de la base de données via Git.

## Scripts disponibles

### 📤 Export (Sauvegarde)

**Windows :**
```bash
scripts\db-export.bat
```

Cela crée un fichier `dumps/dump_YYYYMMDD_HHMMSS.sql` avec toutes les données actuelles.

### 📥 Import (Restauration)

**Windows :**
```bash
scripts\db-import.bat
```

Cela restaure les données depuis le fichier `dumps/dump.sql` partagé dans Git.

## Workflow de partage des données

### Pour partager vos données (votre ami) :

1. Exportez la base de données :
   ```bash
   scripts\db-export.bat
   ```

2. Copiez le fichier créé vers `dumps/dump.sql` :
   ```bash
   copy dumps\dump_20251015_143000.sql dumps\dump.sql
   ```

3. Commitez et partagez :
   ```bash
   git add dumps/dump.sql
   git commit -m "Update database dump with latest data"
   git push
   ```

### Pour récupérer les données (vous) :

1. Récupérez les dernières modifications :
   ```bash
   git pull
   ```

2. Importez les données :
   ```bash
   scripts\db-import.bat
   ```

3. C'est tout ! Vous avez maintenant les mêmes données que votre ami.

## Notes importantes

- ✅ Le fichier `dump.sql` est versionné dans Git (à partager)
- ❌ Les fichiers `dump_*.sql` (avec timestamp) sont ignorés par Git (locaux uniquement)
- ⚠️ L'import **remplace toutes les données actuelles**
- 💡 Exportez régulièrement pour ne pas perdre vos modifications

## En cas de problème

Si l'import échoue, vérifiez que :
1. Docker est bien démarré
2. Les conteneurs sont lancés : `docker-compose ps`
3. Le fichier `dumps/dump.sql` existe
4. Vous êtes bien à la racine du projet

## Commandes manuelles (avancé)

Export manuel :
```bash
docker-compose exec -T db mysqldump -u root -proot minirsn_db > dumps/dump.sql
```

Import manuel :
```bash
docker-compose exec -T db mysql -u root -proot minirsn_db < dumps/dump.sql
```

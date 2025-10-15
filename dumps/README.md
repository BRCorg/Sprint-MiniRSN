# Partage de la base de données

## 📤 Exporter (pour partager vos données)

```bash
docker-compose exec -T db mysqldump -u root -proot minirsn_db > dumps/dump.sql
```

Puis :
```bash
git add dumps/dump.sql
git commit -m "Update database"
git push
```

## 📥 Importer (pour récupérer les données)

```bash
git pull
docker-compose exec -T db mysql -u root -proot minirsn_db < dumps/dump.sql
```

---

**Note :** L'import remplace toutes les données actuelles.

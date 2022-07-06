Load fixtures for tests

```bash
docker-compose exec php bin/console --env=test doctrine:schema:update --force
docker-compose exec php bin/console --env=test doctrine:fixtures:load
```

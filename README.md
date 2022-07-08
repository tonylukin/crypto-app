Load fixtures for tests

```bash
docker-compose exec php bin/console --env=test doctrine:schema:update --force
docker-compose exec php bin/console --env=test doctrine:fixtures:load
```

Run test
```bash
docker-compose exec php bin/phpunit --filter=testGetBestPriceForOrder tests/Service/BestPriceAnalyzerTest.php
```
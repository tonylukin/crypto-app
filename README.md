Run cron console
```bash
docker-compose exec php bin/console cron:start --blocking
```

Load fixtures for tests
```bash
docker-compose exec php bin/console --env=test doctrine:schema:update --force
docker-compose exec php bin/console --env=test doctrine:fixtures:load
```

Run test
```bash
docker-compose exec php bin/phpunit --filter=testGetBestPriceForOrder tests/Service/BestPriceAnalyzerTest.php
docker-compose exec php bin/phpunit tests/Service/OrderManagerTest.php
```

Add init data to PROD!!!! DB through fixture
```bash
docker-compose exec php bin/console doctrine:fixtures:load --group=AppFixtures --append
```

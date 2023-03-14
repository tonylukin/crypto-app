<?php

declare(strict_types=1);

namespace App\Service\Buff;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

class ParserFetcher
{
    public function __construct(
        private ManagerRegistry $doctrine,
    ) {}

    /**
     * @return array<{ hash_name: array<{ source:string, price: float }> }>
     * @throws \Doctrine\DBAL\Exception
     */
    public function getPricesByNames(array $itemNames): array
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection('parser');
        $sql = <<<SQL
            SELECT ip.`source`, ip.`price`, i.hash_name
            FROM items_price ip
            INNER JOIN items i ON i.id = ip.item_id
            WHERE i.hash_name IN (:names) AND ip.currency = 'USD' AND ip.source != 'OnMoon';
        SQL;
        $result = $connection
            ->executeQuery($sql, ['names' => $itemNames], ['names' => Connection::PARAM_STR_ARRAY])
            ->fetchAllAssociative()
        ;
        $output = [];
        foreach ($result as $row) {
            $output[$row['hash_name']][] = [
                'source' => $row['source'],
                'price' => $row['price'],
            ];
        }

        return $output;
    }
}

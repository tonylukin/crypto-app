<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Buff\BuffApi;
use App\Service\Buff\ParserFetcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BuffController extends AbstractController
{
    public function __construct(
        private BuffApi $buffApi,
    ) {
        throw new AccessDeniedHttpException('Denied');
    }

    #[Route('/buff', name: 'buff_buy_list')]
    public function buyList(ParserFetcher $parserFetcher): Response
    {
        $items = $this->buffApi
//            ->setAuthSession($session)
            ->getBuyItems()
        ;
        $names = array_map(fn (array $item) => $item['market_hash_name'], $items);
        $parserData = $parserFetcher->getPricesByNames($names);

        return $this->render('buff/index.html.twig', [
            'items' => $items,
            'parserData' => $parserData,
        ]);
    }

    #[Route('/buff-sale', name: 'buff_sale_list')]
    public function saleList(): Response
    {
        $items = $this->buffApi
//            ->setAuthSession($session)
            ->getSaleItems()
        ;
        return $this->render('buff/index.html.twig', [
            'items' => $items,
        ]);
    }
}

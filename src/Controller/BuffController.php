<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Buff\BuffApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BuffController extends AbstractController
{
    public function __construct(
        private BuffApi $buffApi,
    ) {}

    #[Route('/buff', name: 'buff_buy_list')]
    public function buyList(): Response
    {
        $items = $this->buffApi
//            ->setAuthSession($session)
            ->getBuyItems()
        ;
        return $this->render('buff/index.html.twig', [
            'items' => $items,
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

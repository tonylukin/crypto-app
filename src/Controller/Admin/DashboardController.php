<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Symbol;
use App\Repository\OrderRepository;
use App\Repository\PriceRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DashboardController extends AbstractController
{
    #[Route(path: '/admin/dashboard/prices/{symbol}', name: 'admin_dashboard_prices', defaults: ['symbol' => 'BTCBUSD'])]
    #[ParamConverter('symbol', options: ['mapping' => ['symbol' => 'name']])]
    public function prices(
        Request $request,
        Symbol $symbol,
        NormalizerInterface $normalizer,
        PriceRepository $priceRepository,
        OrderRepository $orderRepository,
    ): Response {
        $daysAgo = $request->get('days-ago', 30);
        $prices = $priceRepository->getLastItemsForInterval(new \DateInterval("P{$daysAgo}D"), $symbol);
        $orders = $orderRepository->getLastItemsForInterval(new \DateInterval("P{$daysAgo}D"), $symbol);

        return $this->render('admin/dashboard/prices.html.twig', [
            'symbol' => $symbol,
            'prices' => $normalizer->normalize($prices, 'json', ['groups' => 'price_details']),
            'orders' => $normalizer->normalize($orders, 'json', ['groups' => 'order_price_details']),
            'daysAgo' => $daysAgo,
        ]);
    }
}

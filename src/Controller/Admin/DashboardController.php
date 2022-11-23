<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Form\Admin\DateIntervalType;
use App\Model\Admin\DateIntervalModel;
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
        $form = $this->createForm(DateIntervalType::class, options: [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        /** @var DateIntervalModel $model */
        $model = $form->getData() ?? new DateIntervalModel();

        if ($model->dateStart || $model->dateEnd) {
            $prices = $priceRepository->getLastItemsForDates($model->dateStart, $model->dateEnd, $symbol);
            $allOrders = $orderRepository->getLastItemsForDates($model->dateStart, $model->dateEnd, $symbol);
        } else {
            $prices = $priceRepository->getLastItemsForInterval(new \DateInterval("P{$model->daysAgo}D"), $symbol);
            $allOrders = $orderRepository->getLastItemsForInterval(new \DateInterval("P{$model->daysAgo}D"), $symbol);
        }
        $orders = array_filter($allOrders, fn (Order $order) => $order->getSellDate() !== null);

        return $this->render('admin/dashboard/prices.html.twig', [
            'form' => $form->createView(),
            'symbol' => $symbol,
            'prices' => $normalizer->normalize($prices, 'json', ['groups' => 'price_details']),
            'orders' => $normalizer->normalize($orders, 'json', ['groups' => 'order_price_details']),
            'allOrders' => $normalizer->normalize($allOrders, 'json', ['groups' => 'order_price_details']),
        ]);
    }

    #[Route(path: '/admin/dashboard/orders', name: 'admin_dashboard_orders')]
    public function orders(Request $request, NormalizerInterface $normalizer, OrderRepository $orderRepository): Response
    {
        $form = $this->createForm(DateIntervalType::class, options: [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        /** @var DateIntervalModel $model */
        $model = $form->getData() ?? new DateIntervalModel();
        $orders = $orderRepository->getLastItemsForDates($model->dateStart, $model->dateEnd, onlyCompleted: true);

        return $this->render('admin/dashboard/orders.html.twig', [
            'form' => $form->createView(),
            'orders' => $normalizer->normalize($orders, 'json', ['groups' => 'order_price_details']),
        ]);
    }
}

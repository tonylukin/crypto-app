<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Form\Admin\DateIntervalType;
use App\Model\Admin\DateIntervalModel;
use App\Repository\OrderRepository;
use App\Repository\PriceRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $model = new DateIntervalModel();
        $form = $this->createForm(DateIntervalType::class, $model, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);

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
        $model = new DateIntervalModel();
        $form = $this->createForm(DateIntervalType::class, $model, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        $orders = $orderRepository->getLastItemsForDates($model->dateStart, $model->dateEnd);
        $counts = $orderRepository->getSymbolCountsForDates($model->dateStart, $model->dateEnd, true);

        return $this->render('admin/dashboard/orders.html.twig', [
            'form' => $form->createView(),
            'orders' => $normalizer->normalize($orders, 'json', ['groups' => 'order_price_details']),
            'counts' => $counts,
        ]);
    }

    #[Route(path: '/admin/dashboard/cron-report', name: 'admin_dashboard_cron_report')]
    public function cronReport(EntityManagerInterface $entityManager): Response
    {
        $connection = $entityManager->getConnection();
        $sql = <<<SQL
            SELECT * FROM cron_report WHERE job_id = 1 ORDER BY id DESC LIMIT 50;
        SQL;
        $data = $connection->fetchAllAssociative($sql);

        return $this->render('admin/dashboard/cron_report.html.twig', [
            'data' => $data,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Symbol;
use App\Entity\User;
use App\Form\Admin\DateIntervalType;
use App\Form\Admin\UserSettingType;
use App\Model\Admin\DateIntervalModel;
use App\Model\FlashBagTypes;
use App\Repository\OrderRepository;
use App\Repository\PriceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DashboardController extends AbstractController
{
    #[Route(path: '/admin', name: 'admin_dashboard')]
    public function adminHomepage(): RedirectResponse
    {
        return $this->redirectToRoute('admin_order_list');
    }

    #[Route(path: '/admin/dashboard/prices/{symbol}', name: 'admin_dashboard_prices', defaults: ['symbol' => 'BTCBUSD'])]
    #[ParamConverter('symbol', options: ['mapping' => ['symbol' => 'name']])]
    public function prices(
        Request $request,
        Symbol $symbol,
        NormalizerInterface $normalizer,
        PriceRepository $priceRepository,
        OrderRepository $orderRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $model = new DateIntervalModel();
        $form = $this->createForm(DateIntervalType::class, $model, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);

        if ($model->dateStart || $model->dateEnd) {
            $prices = $priceRepository->getLastItemsForDates($model->dateStart, $model->dateEnd, $symbol);
            $allOrders = $orderRepository->getLastItemsForDates($user, $model->dateStart, $model->dateEnd, $symbol);
        } else {
            $prices = $priceRepository->getLastItemsForInterval(new \DateInterval("P{$model->daysAgo}D"), $symbol);
            $allOrders = $orderRepository->getLastItemsForInterval($user, new \DateInterval("P{$model->daysAgo}D"), $symbol);
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

    #[IsGranted(User::ROLE_ADMIN)]
    #[Route(path: '/admin/dashboard/cron-report', name: 'admin_dashboard_cron_report')]
    public function cronReport(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sqlShowAll = $request->query->getBoolean('all') ? '' : 'LIMIT 30';
        $sqlHideAccountNoMoney = $request->query->getBoolean('hide-no-money-logs') ? "AND (error NOT LIKE '%insufficient balance%' OR error NOT LIKE '%account-frozen-balance-insufficient-error%')" : '';

        $connection = $entityManager->getConnection();
        $sql = <<<SQL
            SELECT * FROM cron_report WHERE 1 = 1 {$sqlHideAccountNoMoney} ORDER BY id DESC {$sqlShowAll};
        SQL;
        $data = $connection->fetchAllAssociative($sql);

        return $this->render('admin/dashboard/cron_report.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route(path: '/admin/dashboard/settings', name: 'admin_dashboard_settings')]
    public function settings(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserSettingType::class, $user->getUserSetting());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(FlashBagTypes::SUCCESS, 'Settings saved successfully');
        }

        return $this->render('admin/dashboard/settings.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

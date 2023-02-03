<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\OrderManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route(path: '/admin/order/{id}/unsold', name: 'admin_order_unsold')]
    public function unsold(Order $order, OrderManager $orderManager): RedirectResponse
    {
        $orderManager->unsold($order);
        return $this->redirectToRoute('admin_dashboard_orders');
    }
}

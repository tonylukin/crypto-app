<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route(path: '/admin/order/{id}/delete', name: 'admin_order_delete')]
    public function delete(Order $order, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($order);
        $entityManager->flush();

        return $this->redirectToRoute('admin_dashboard_orders');
    }
}

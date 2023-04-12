<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Traits\ReturnUrlTrait;
use App\Entity\Order;
use App\Entity\User;
use App\Form\Admin\DateIntervalType;
use App\Form\Admin\OrderType;
use App\Model\Admin\DateIntervalModel;
use App\Model\FlashBagTypes;
use App\Repository\OrderRepository;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OrderController extends AbstractController
{
    use ReturnUrlTrait;

    #[Route(path: '/admin/orders', name: 'admin_order_list')]
    public function list(Request $request, NormalizerInterface $normalizer, OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $model = new DateIntervalModel();
        $form = $this->createForm(DateIntervalType::class, $model, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);
        $orders = $orderRepository->getLastItemsForDates($user, $model->dateStart, $model->dateEnd);
        $counts = $orderRepository->getSymbolCountsForDates($user, $model->dateStart, $model->dateEnd, true);

        return $this->render('admin/order/list.html.twig', [
            'form' => $form->createView(),
            'orders' => $normalizer->normalize($orders, 'json', ['groups' => 'order_price_details']),
            'counts' => $counts,
        ]);
    }
    
    #[Route(path: '/admin/order/{id}/unsold', name: 'admin_order_unsold')]
    public function unsold(Order $order, Request $request, OrderManager $orderManager, EntityManagerInterface $entityManager): RedirectResponse
    {
        $orderManager->unsold($order);
        $entityManager->flush();
        $this->addFlash(FlashBagTypes::INFO, 'Order unsold successfully');

        return $this->redirectToReturnUrl($request, 'admin_order_list');
    }

    #[Route(path: '/admin/order/{id}/delete', name: 'admin_order_delete')]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash(FlashBagTypes::INFO, 'Order deleted successfully');

        return $this->redirectToReturnUrl($request, 'admin_order_list');
    }

    #[Route(path: '/admin/order/{id}/edit', name: 'admin_order_edit')]
    public function edit(Order $order, Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(FlashBagTypes::SUCCESS, 'Order saved successfully');

            return $this->redirectToReturnUrl($request, 'admin_order_list');
        }

        return $this->render('admin/order/edit.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/order/{id}', name: 'admin_order_view')]
    public function view(Order $order): Response
    {
        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Symbol;
use App\Entity\User;
use App\Entity\UserSymbol;
use App\Form\Admin\SymbolType;
use App\Model\FlashBagTypes;
use App\Repository\UserSymbolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SymbolController extends AbstractController
{
    #[Route(path: '/admin/symbols', name: 'admin_symbol_list')]
    public function list(UserSymbolRepository $userSymbolRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userSymbols = $userSymbolRepository->findAllByUser($user);

        return $this->render('admin/symbol/list.html.twig', [
            'userSymbols' => $userSymbols,
        ]);
    }

    #[Route(path: '/admin/symbols/create', name: 'admin_symbol_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $userSymbol = new UserSymbol();
        $userSymbol->setUser($user);
        $form = $this->createForm(SymbolType::class, $userSymbol);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userSymbol);
            $entityManager->flush();
            $this->addFlash(FlashBagTypes::SUCCESS, 'Symbol created successfully');

            return $this->redirectToRoute('admin_symbol_list');
        }

        return $this->render('admin/symbol/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/symbols/batch-edit', name: 'admin_symbol_batch_edit')]
    public function batchEdit(Request $request, UserSymbolRepository $userSymbolRepository): RedirectResponse
    {
        $symbolIds = $request->get('symbolIds', []);
        $totalPrice = (float) $request->get('totalPrice');
        $toggleActive = (int) $request->get('toggleActive');
        $count = null;
        if ($totalPrice > 0) {
            $count = $userSymbolRepository->batchChangeTotalPrice($symbolIds, $this->getUser(), $totalPrice);
        }
        if ($toggleActive === 1) {
            $count = $userSymbolRepository->batchToggleActive($symbolIds, $this->getUser());
        }
        if ($count !== null) {
            $this->addFlash(FlashBagTypes::SUCCESS, "{$count} symbols updated successfully");
        }

        return $this->redirectToRoute('admin_symbol_list');
    }

    #[Route(path: '/admin/symbols/{symbol}', name: 'admin_symbol_edit')]
    #[ParamConverter('symbol', options: ['mapping' => ['symbol' => 'name']])]
    public function edit(
        Request $request,
        Symbol $symbol,
        EntityManagerInterface $entityManager,
        UserSymbolRepository $userSymbolRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $userSymbol = $userSymbolRepository->findOneBySymbolAndUser($user, $symbol);
        $form = $this->createForm(SymbolType::class, $userSymbol);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(FlashBagTypes::SUCCESS, 'Symbol updated successfully');

            return $this->redirectToRoute('admin_symbol_list');
        }

        return $this->render('admin/symbol/edit.html.twig', [
            'symbol' => $symbol,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/symbols/{id}/{symbol}/delete', name: 'admin_symbol_delete')]
    #[ParamConverter('symbol', options: ['mapping' => ['symbol' => 'name']])]
    public function delete(User $user, Symbol $symbol, EntityManagerInterface $entityManager): RedirectResponse
    {
        $userSymbol = $entityManager->getRepository(UserSymbol::class)->findOneBy([
            'user' => $user,
            'symbol' => $symbol,
        ]);
        $entityManager->remove($userSymbol);
        $entityManager->flush();
        $this->addFlash(FlashBagTypes::INFO, 'Symbol deleted successfully');

        return $this->redirectToRoute('admin_symbol_list');
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Symbol;
use App\Form\Admin\SymbolType;
use App\Repository\SymbolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SymbolController extends AbstractController
{
    #[Route(path: '/admin/symbols', name: 'admin_symbol_list')]
    public function list(SymbolRepository $symbolRepository): Response
    {
        $symbols = $symbolRepository->findAll();

        return $this->render('admin/symbol/list.html.twig', [
            'symbols' => $symbols,
        ]);
    }

    #[Route(path: '/admin/symbols/create', name: 'admin_symbol_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $symbol = new Symbol();
        $form = $this->createForm(SymbolType::class, $symbol);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($symbol);
            $entityManager->flush();

            return $this->redirectToRoute('admin_symbol_list');
        }

        return $this->render('admin/symbol/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/symbols/{symbol}', name: 'admin_symbol_edit')]
    #[ParamConverter('symbol', options: ['mapping' => ['symbol' => 'name']])]
    public function edit(
        Request $request,
        Symbol $symbol,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(SymbolType::class, $symbol);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_symbol_list');
        }

        return $this->render('admin/symbol/edit.html.twig', [
            'symbol' => $symbol,
            'form' => $form->createView(),
        ]);
    }
}

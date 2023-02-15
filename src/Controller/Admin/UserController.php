<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\Admin\UserType;
use App\Model\FlashBagTypes;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted(User::ROLE_ADMIN)]
class UserController extends AbstractController
{
    #[Route(path: '/admin/users', name: 'admin_user_list')]
    public function list(Request $request, UserRepository $userRepository): Response
    {
        $users = $userRepository->getListPaginated($request->query->getInt('page', 1));

        return $this->render('admin/user/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route(path: '/admin/users/create', name: 'admin_user_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(FlashBagTypes::SUCCESS, 'User created successfully');

            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/users/{id}', name: 'admin_user_edit')]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash(FlashBagTypes::SUCCESS, 'User updated successfully');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/admin/users/{id}/delete', name: 'admin_user_delete')]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash(FlashBagTypes::INFO, 'User deleted successfully');

        return $this->redirectToRoute('admin_user_list');
    }
}

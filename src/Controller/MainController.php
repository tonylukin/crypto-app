<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    public function __construct(
        private ApiInterface $api
    ) {}

    #[Route('/', name: 'app_main_index')]
    public function index(): JsonResponse
    {
        return $this->json([]);
    }
}

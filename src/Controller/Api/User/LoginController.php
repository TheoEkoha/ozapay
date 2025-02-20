<?php

namespace App\Controller\Api\User;

use App\Security\AuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LoginController extends AbstractController
{
    public function __construct(private readonly AuthenticationService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->login($request);
    }

}

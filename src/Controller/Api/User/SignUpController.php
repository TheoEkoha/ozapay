<?php

namespace App\Controller\Api\User;

use App\Entity\User\User;
use App\Service\Api\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SignUpController extends AbstractController
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function __invoke(Request $request): User
    {
        return $this->userService->signUp($request);
    }

}

<?php

namespace App\Controller\Api\User;

use App\Entity\User\User;
use App\Service\Api\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ForgotPassController extends AbstractController
{
    public function __construct(
        protected UserService $userService
    ) {
    }

    public function __invoke(Request $request)
    {
        return $this->userService->forgotPassword($request);
    }

}

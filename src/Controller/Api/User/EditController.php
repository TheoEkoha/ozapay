<?php

namespace App\Controller\Api\User;

use App\Entity\User\User;
use App\Service\Api\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class EditController extends AbstractController
{
    public function __construct(
        protected UserService $userService
    ) {
    }


    /**
     * @param User $user
     * @param Request $request
     * @return User
     * @throws \Exception
     */
    public function __invoke(User $user, Request $request)
    {
        return $this->userService->edit($user, $request);
    }

}

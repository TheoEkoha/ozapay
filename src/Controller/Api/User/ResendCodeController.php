<?php

namespace App\Controller\Api\User;

use App\Entity\User\User;
use App\Service\Api\User\UserService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ResendCodeController extends AbstractController
{
    public function __construct(private readonly UserService $service)
    {
    }

    /**
     * @throws NonUniqueResultException
     * @throws JsonException
     */
    public function __invoke(User $user, Request $request): JsonResponse
    {
        return $this->service->resendCode($user, $request);
    }

}

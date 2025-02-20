<?php

namespace App\Controller\Api\User;

use App\Service\Api\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RenewPasswordController extends AbstractController
{
    public function __construct(private readonly UserService $service)
    {
    }

    /**
     * @throws JsonException
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->renewPassword($request);
    }

}

<?php

namespace App\Controller\Api\User\LoginSMS;

use App\Service\Api\User\SignInService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PrepareSmsLoginController extends AbstractController
{
    public function __construct(
        private readonly SigninService $service
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->initiateLoginSms($request);
    }

}

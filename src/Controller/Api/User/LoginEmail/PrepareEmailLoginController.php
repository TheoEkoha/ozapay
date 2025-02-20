<?php

namespace App\Controller\Api\User\LoginEmail;

use App\Service\Api\User\SignInService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PrepareEmailLoginController extends AbstractController
{
    public function __construct(
        private readonly SignInService $service
    ) {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws NonUniqueResultException
     */
    public function __invoke(Request $request): JsonResponse
    {
        return $this->service->initiateLoginEmail($request);
    }

}

<?php

namespace App\Controller\Api\User\Linkcy;

use App\Entity\User\Bank;
use App\Entity\User\User;
use App\Service\Api\Linkcy\CreateService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class CreateCardController extends AbstractController
{
    public function __construct(
        protected readonly CreateService $service
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     * @throws GuzzleException
     */
    public function __invoke(User $user, Request $request): Bank
    {
        return $this->service->createBankLocal($user);
    }
}

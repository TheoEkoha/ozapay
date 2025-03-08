<?php

namespace App\Controller\Api\User;

use App\Entity\User\User;
use App\Service\AuthenticationService;
use App\Service\Api\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class EditController extends AbstractController
{
    public function __construct(
        protected UserService $userService,
        protected AuthenticationService $authService
    ) {
    }


    /**
     * @param User $user
     * @param Request $request
     * @return User
     * @throws \Exception
     */
    public function __invoke(User $user, Request $request): JsonResponse
    {
        // Mettre à jour l'utilisateur
        $updatedUser = $this->userService->edit($user, $request);

        // Générer un token temporaire
        $sessionId = bin2hex(random_bytes(32)); // Génère un ID de session unique
        $tempToken = $this->authService->storeAuthenticationSession($sessionId, $updatedUser->getId());

        // Retourner une réponse JSON avec le token
        return new JsonResponse([
            'message' => 'User updated successfully',
            'tempToken' => $tempToken,
        ]);
    }

}

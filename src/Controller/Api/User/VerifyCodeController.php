<?php

namespace App\Controller\Api\User;

use App\Common\Constants\Response\VerificationConstant;
use App\Entity\User\User;
use App\Entity\User\VerificationCode;
use App\Service\Api\User\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\AuthenticationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class VerifyCodeController extends AbstractController
{
    public function __construct(
        protected UserService         $service,
        protected SerializerInterface $serializer,
        protected AuthenticationService $authService
    ) {
    }

    /**
     * @throws RandomException
     * @throws JWTEncodeFailureException
     */
    public function __invoke(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifie le code
        $dataVerified = $this->service->verifyCode($user, (int)$data['code'], $data['type'], $data['for']);

        // Si la vérification réussit
        if ($dataVerified instanceof VerificationCode) {
            // Sérialise les données vérifiées
            $dataSerialized = json_decode($this->serializer->serialize($dataVerified, 'jsonld', ['groups' => ['verification:read','user:read']]));

            // Génère un ID de session unique
            $sessionId = bin2hex(random_bytes(32));

            // Stocke la session d'authentification et récupère le tempToken
            $tempToken = $this->authService->storeAuthenticationSession($sessionId, $user->getId());

            // Ajoute le tempToken à la réponse
            $responseData = [
                'data' => $dataSerialized,
                'tempToken' => $tempToken,
                'message' => VerificationConstant::VERIFICATION_SUCCESS,
            ];

            if ($data['type'] != "MAIL") {
// Ajoute le tempToken à la réponse
                $responseData = [
                    'data' => $dataSerialized,
                    'tempToken' => $tempToken,
                    'message' => VerificationConstant::VERIFICATION_SUCCESS,
                    'hasPin' => true
                ];
            }
            

            return $this->json($responseData, Response::HTTP_OK, ['Content-Type' => 'application/ld+json']);
        }

        // Si la vérification échoue
        return $this->json(null, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/ld+json', 'message' => VerificationConstant::VERIFICATION_FAILED]);
    }

}

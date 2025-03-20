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
use Psr\Log\LoggerInterface;

class VerifyCodeController extends AbstractController
{
    public function __construct(
        protected UserService         $service,
        protected SerializerInterface $serializer,
        protected AuthenticationService $authService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @throws RandomException
     * @throws JWTEncodeFailureException
     */
    public function __invoke(User $user, Request $request): JsonResponse
{
    $this->logger->info("VerifyCodeController INITIALISATION");
    $data = json_decode($request->getContent(), true);

    // Log des données reçues
    $this->logger->info("VerifyCodeController Données reçues : " . print_r($data, true));
    $this->logger->info("VerifyCodeController Test log: Vérification du code", [
        'code' => $data['code'],
        'type' => $data['type'],
        'for' => $data['for'],
    ]);

    // Vérifie le code
    $dataVerified = $this->service->verifyCode($user, (int)$data['code'], $data['type'], $data['for']);

    // Si la vérification réussit et que le retour est une instance de VerificationCode
    if ($dataVerified instanceof VerificationCode) {
        // Log des données vérifiées
        $this->logger->info("VerifyCodeController Données vérifiées : " . print_r($dataVerified, true));

        // Sérialise les données vérifiées
        $dataSerialized = json_decode($this->serializer->serialize($dataVerified, 'jsonld', ['groups' => ['verification:read','user:read']]));

        // Log des données sérialisées
        $this->logger->info("VerifyCodeController Données sérialisées : " . print_r($dataSerialized, true));

        // Génère un ID de session unique
        $sessionId = bin2hex(random_bytes(32));

        // Log de l'ID de session
        $this->logger->info("VerifyCodeController ID de session généré : " . $sessionId);

        // Stocke la session d'authentification et récupère le tempToken
        $tempToken = $this->authService->storeAuthenticationSession($sessionId, $user->getId());

        // Log du tempToken
        $this->logger->info("VerifyCodeController TempToken généré : " . $tempToken);

        // Ajoute le tempToken à la réponse
        $responseData = [
            'data' => $dataSerialized,
            'tempToken' => $tempToken,
            'message' => VerificationConstant::VERIFICATION_SUCCESS,
            'hasPin' => false
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

        // Log de la réponse finale
        $this->logger->info("VerifyCodeController Réponse finale : " . print_r($responseData, true));

        return $this->json($responseData, Response::HTTP_OK, ['Content-Type' => 'application/ld+json']);
    }

    // Si le retour est un tableau (par exemple un tempToken)
    if (is_array($dataVerified) && isset($dataVerified['tempToken'])) {
        $responseData = [
            'tempToken' => $dataVerified['tempToken'],
            'message' => VerificationConstant::VERIFICATION_SUCCESS,
            'hasPin' => false
        ];

        // Log de la réponse finale
        $this->logger->info("VerifyCodeController Réponse finale : " . print_r($responseData, true));

        return $this->json($responseData, Response::HTTP_OK, ['Content-Type' => 'application/ld+json']);
    }

    // Si la vérification échoue
    $this->logger->error("VerifyCodeController Échec de la vérification : code invalide ou expiré");
    $this->logger->info("VerifyCodeController Échec de la vérification : code invalide ou expiré");
    return $this->json(null, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/ld+json', 'message' => VerificationConstant::VERIFICATION_FAILED]);
}
}
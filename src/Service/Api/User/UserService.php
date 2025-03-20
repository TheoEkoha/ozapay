<?php

namespace App\Service\Api\User;

use App\Common\Constants\Response\ErrorsConstant;
use App\Common\Constants\Response\SuccessConstants;
use App\Common\Constants\Response\VerificationConstant;
use App\Common\Constants\VerificationTypeConstant;
use App\Entity\Enum\Status;
use App\Entity\Enum\Step;
use App\Entity\User\Particular;
use App\Entity\User\Professional;
use App\Entity\User\Relation;
use App\Entity\User\User;
use App\Entity\User\VerificationCode;
use App\Repository\User\UserRepository;
use App\Security\AuthenticationService;
use App\Service\Mail\MailerService;
use App\Service\SmsService;
use App\Utils\DataEncryption;
use App\Utils\Tools;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use PHPUnit\Framework\Exception;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\BrowserKit\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class UserService extends UserCommonService
{
    private LoggerInterface $logger;

    public function __construct(
        SerializerInterface                   $serializer,
        private UserRepository                $repository,
        protected TokenGeneratorInterface     $tokenGenerator,
        MailerService                         $mailerService,
        private EntityManagerInterface        $em,
        protected UserPasswordHasherInterface $passwordHasher,
        LoggerInterface                       $logger,
        SmsService                            $sms,
        private AuthenticationService         $authService,
        private Tools                         $tools,
        private Security                      $security,
        private DataEncryption                $dataEncryption
    ) {
        parent::__construct($serializer, $sms, $em, $mailerService);
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws JsonException
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $this->getPostedData($request);

        try {
            $user = $this->repository->findOneBy(['email' => $data['email']]);
            if (!$user) {
                throw new JsonException(ErrorsConstant::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }
            $resetToken = $this->tokenGenerator->generateToken();
            $user->setResetToken($resetToken);

            $this->em->persist($user);
            $this->em->flush();

            $url = $data['url'] . '?token=' . $resetToken;
            //send mail to reset pass
            $this->mailerService->sendMailToResetPass($user, $url);

            return new JsonResponse(['resetToken' => $resetToken], Response::HTTP_OK);

        } catch (Exception $e) {
            throw new JsonException(ErrorsConstant::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }


    }

    /**
     * @throws JsonException
     */
    public function reinitializePassword(Request $request)
    {
        $data = $this->getPostedData($request);

        try {
            $user = $this->repository->findOneBy(['resetToken' => $data['token']]);
            if (!$user) {
                throw new JsonException(ErrorsConstant::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
            }

            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']))
                ->setResetToken(null)
                ->setGeneratedPassUpdated(true);
            $this->em->persist($user);
            $this->em->flush();

            return $user;

        } catch (Exception $e) {
            throw new JsonException(ErrorsConstant::USER_NOT_FOUND, Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @throws JsonException
     */
    public function renewPassword(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new JsonException(ErrorsConstant::USER_NOT_CONNECTED, Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->getPostedData($request);
        if (!array_key_exists('oldPassword', $data) || !array_key_exists('newPassword', $data)) {
            throw new JsonException(ErrorsConstant::INVALID_REQUEST, Response::HTTP_BAD_REQUEST);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data['oldPassword'])) {
            throw new JsonException(ErrorsConstant::INVALID_CREDENTIALS, Response::HTTP_UNAUTHORIZED);
        }

        // Add check for same password
        if ($data['oldPassword'] === $data['newPassword']) {
            throw new JsonException(ErrorsConstant::PASSWORD_NOT_CHANGED, Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $data['newPassword']))
            ->setGeneratedPassUpdated(true);
        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([], Response::HTTP_OK, ['message' => SuccessConstants::PASSWORD_CHANGED]);
    }

    public function signUp(Request $request): User
    {
        $data = $this->getPostedData($request);

        /** @var ?User $user */
        $user = null;
        if (array_key_exists('role', $data)) {
            if ($data['role'] === 'professional') {
                $user = $this->deserialize($data, Professional::class, 'json', ['groups' => ['user:write', 'user:pro:write']]);
                $user->setAddress($data['denomination']);
            }
            if ($data['role'] === 'particular') {
                $user = $this->deserialize($data, Particular::class, 'json', ['groups' => ['user:write', 'user:part:write']]);
            }
        }

        if (!is_null($data['code']) && $data['code'] != '') {
            $existedUser = $this->repository->findOneBy(['code' => $data['code'], 'status' => Status::Published]);
            if (!is_null($existedUser)) {
                $this->createRelation($user, $existedUser, $data['code']);
            }
            $user->setCode($data['code']);
        } else {
            $user->setCode(strtoupper($this->tools->generateRandomString(6)));

        }

           // ->setStep('info');
            //->setStep(Step::Info);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

/**
 * @throws \Exception
 */
public function edit(User $user, Request $request): User
{
    try {
        // Récupérer les données de la requête
        $data = $this->getPostedData($request);
        $this->logger->info('Données de requête:', $data);

        // Vérification et mise à jour du numéro de téléphone
        if (isset($data['phone'])) {
            $this->handlePhoneUpdate($user, $data['phone'], $data['appSignature'] ?? null);
        }

        // Vérification et mise à jour de l'email
        if (isset($data['email'])) {
            $this->handleEmailUpdate($user, $data['email']);
        }

        // Gérer le code PIN
        if (isset($data['pin']) && $data['_step'] === 'pin') {
            $this->handlePinUpdate($user, $data['pin']);
            $user->setCode(strtoupper($this->tools->generateRandomString(6)));
        }

        $dataArray = $request->toArray(); // Récupère les données de la requête

        $fieldsToUpdate = [
            'firstName' => 'setFirstName',
            'lastName' => 'setLastName',
            'code' => 'setCode',
            'address' => 'setAddress',
            'postalCode' => 'setPostalCode',
            'city' => 'setCity',
            'roles' => 'setRoles',
            'conditionAccepted' => 'setConditionAccepted',
            'marketingAccepted' => 'setMarketingAccepted',
        ];
    
        foreach ($fieldsToUpdate as $field => $method) {
            if ($field === 'role') {
                // Cas spécifique pour 'role'
                if (isset($dataArray[$field]) && $dataArray[$field] !== $user->getRoles()) {
                    $user->{$method}($dataArray[$field]);
                }
            } else {
                // Cas général pour les autres champs
                if (isset($dataArray[$field]) && $dataArray[$field] !== $user->{'get' . ucfirst($field)}()) {
                    $user->{$method}($dataArray[$field]);
                }
            }
        }

        // Gérer l'étape si spécifiée
        if (isset($data['_step'])) {
            $this->setUserStep($user, $data['_step']);
        }

        $this->em->persist($user);
        $this->em->flush();
        $this->repository->save($user);


        return $user;
    } catch (Exception $e) {
        // Gérer l'exception de manière appropriée
        if ($this->em->isOpen()) {
            $this->em->close(); // Ferme l'EntityManager
        }
        throw new Exception('Erreur lors de la mise à jour de l\'utilisateur: ' . $e->getMessage(), $e->getCode());
    }
}

private function handlePhoneUpdate(User $user, string $phone, ?string $signature): void
{
    // if (preg_match('/^06/', $phone)) {
    //     $phone = preg_replace('/^06/', '+33 6', $phone);
    // } elseif (!preg_match('/^\+33 6/', $phone)) {
    //     throw new Exception("Invalid phone number format. Please use a valid French number starting with '06'.", Response::HTTP_BAD_REQUEST);
    // }

    if ($user->getPhone() !== $phone) {
        $user->setPhone($phone);
        $this->repository->save($user);

        // Appelle sendSMSCode sans avoir besoin de vérifier $signature ici
        $this->sendSMSCode($user, $phone, VerificationConstant::SIGN_UP_VER, $signature);
    }
}

private function handleEmailUpdate(User $user, string $newEmail): void
{
    if ($newEmail !== $user->getEmail()) {
        $existingUser = $this->repository->findOneBy(['email' => $newEmail]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            throw new Exception(ErrorsConstant::EMAIL_ALREADY_EXIST, Response::HTTP_ALREADY_REPORTED);
        }

        $user->setEmail($newEmail);

        // Envoyer le code de validation par email seulement si l'utilisateur est nouveau
        //if ($user->getId() === null) {
        $this->sendMailCode($user, $newEmail, VerificationConstant::SIGN_UP_VER);
        //}
    }
}

private function handlePinUpdate(User $user, string $pin): void
{
    $generatedPassword = $this->tools->generateRandomString();
    $user->setPassword($this->passwordHasher->hashPassword($user, $generatedPassword));
    $hashedPin = $this->dataEncryption->encrypt($pin);
    $dateFinal = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
        ->add(new \DateInterval('PT30M'));

    $user->setPin((string)$hashedPin)
        ->setGeneratedPassUpdated(false)
        ->setGeneratedPassExpired($dateFinal);

    $this->mailerService->sendWelcomeAfterRegistration($user, $generatedPassword);
}

private function updateUserFields(User $user, array $data): void
{
    $user->setCity($data['city'] ?? null)
         ->setCountry($data['country'] ?? null)
         ->setPostalCode($data['postalCode'] ?? null)
         ->setDenomination($data['denomination'] ?? null)
         ->setSiret($data['siret'] ?? null)
         ->setHasWallet($data['hasWallet'] ?? false);
}

private function setUserStep(User $user, string $step): void
{
    try {
        $stepValue = Step::from($step);
        $user->setStep($stepValue);
    } catch (\ValueError $e) {
        throw new Exception(ErrorsConstant::STEP_INVALID, Response::HTTP_BAD_REQUEST);
    }
}

    /**
     * @throws RandomException
     * @throws JWTEncodeFailureException
     */
    public function verifyCode(User $user, int $code, string $type, string $for): array|VerificationCode|null
{
    // Déterminer le type de vérification en fonction du paramètre 'for'
    $mailVerify = match ($for) {
        VerificationConstant::SIGN_UP_VER => VerificationConstant::VERIFICATION_FOR_SIGN_UP,
        VerificationConstant::SIGN_IN_VER => VerificationConstant::VERIFICATION_FOR_SIGN_IN
    };

    // Log avant la recherche dans la base de données
    $this->logger->info("VerifyCodeController Test log: avant le find", [
        'code' => (string) $code,
        'type' => $type,
        'verificationFor' => $mailVerify,
    ]);

    try {
        // Effectuer la recherche pour récupérer le code de vérification
        $verification = $this->em->getRepository(VerificationCode::class)->findOneBy([
            //'code' => $code,  // Assure-toi que le code est bien converti en string
            'phone' => '+33665723525',  // Assure-toi que le code est bien converti en string
            //'type' => $type,
           // 'verificationFor' => $mailVerify,
        ]);

        // Log après la recherche dans la base de données
        $this->logger->info("VerifyCodeController Test log: verification", [
            'verification' => $verification,
        ]);
        
        // Vérifier si la vérification a échoué ou si le code est expiré
        $dateRef = new \DateTimeImmutable();
        $dateTimezone = $dateRef->setTimezone(new \DateTimeZone('UTC'));
        
        if (!$verification || $verification->getExpiredAt() < $dateTimezone) {
            return null;  // Le code n'est pas valide ou a expiré
        } else {
            // Si le code est valide, le marquer comme vérifié
            $verification->setVerified(true);
            $this->em->persist($verification);
            $this->em->flush();

            // Log du code vérifié
            $this->logger->info('VerifyCodeController code marked as verified', [
                'code' => $code,
                'user_id' => $verification->getResponsible()->getId()
            ]);

            // Récupérer l'ID de l'utilisateur responsable
            $user_id = $verification->getResponsible()->getId();

            // Vérification si c'est pour la connexion
            if ($verification->getVerificationFor() === VerificationConstant::VERIFICATION_FOR_SIGN_IN) {
                // Générer un ID de session pour cette tentative d'authentification
                $sessionId = bin2hex(random_bytes(32));

                $this->logger->info('VerifyCodeController sessionId', [
                    'sessionId' => $sessionId,
                    'tempToken' => $this->authService->storeAuthenticationSession($sessionId, $user_id)
                ]);
    
                
                // Stocker le token temporaire
                return ['tempToken' => $this->authService->storeAuthenticationSession($sessionId, $user_id)];
               
            }
            $this->logger->info('VerifyCodeController verificationverificationverification', [
                'verification' => $verification
            ]);

            // Si c'est pour l'inscription, renvoyer la vérification
            return $verification;
        }
    } catch (\Doctrine\DBAL\Exception $e) {
        // Log l'erreur spécifique à la base de données
        $this->logger->info('VerifyCodeController Database error while verifying code', [
            'exception' => $e->getMessage(),
            'code' => (string) $code,
            'type' => $type,
            'verification_for' => $mailVerify,
        ]);
        return null;  // Retourner null en cas d'erreur de base de données
    } catch (\Exception $e) {
        // Log d'autres erreurs générales
        $this->logger->info('VerifyCodeController Unexpected error while verifying code', [
            'exception' => $e->getMessage(),
            'code' => (string) $code,
            'type' => $type,
            'verification_for' => $mailVerify,
        ]);
        return null;  // Retourner null en cas d'erreur générale
    }
}

    /**
     * @param User $user
     * @param User $existedUser
     * @param string $code
     * @return void
     */
    public function createRelation(User $user, User $existedUser, string $code): void
    {
        $user->setCode(null);
        $relation = (new Relation())
            ->setUserInvited($user)
            ->setUserParent($existedUser)
            ->setCode($code);
        $this->em->persist($relation);

        $user->setCode(strtoupper($this->tools->generateRandomString(6)));
        $this->em->persist($user);
    }

    public function deleteUserByEmail(string $email): void
    {
        // Rechercher l'utilisateur par son e-mail
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            // Supprimer l'utilisateur
            $this->em->remove($user);
            $this->em->flush(); // Valider les changements
        } else {
            throw new \Exception("Utilisateur non trouvé avec l'e-mail : $email");
        }
    }

    public function deleteUserByPhoneNumber(string $phone): void
    {
        // Rechercher l'utilisateur par numéro de téléphone
        $user = $this->em->getRepository(User::class)->findOneBy(['phone' => $phone]);

        if (!$user) {
            throw new \Exception('Utilisateur non trouvé avec le numéro de téléphone : ' . $phone);
        }

        // Supprimer l'utilisateur
        $this->em->remove($user);
        $this->em->flush(); // Appliquer les changements à la base de données
    }


    /**
     * @throws JsonException
     * @throws NonUniqueResultException
     */
    public function resendCode(User $user, Request $request): JsonResponse
    {
        $data = $this->getPostedData($request);

        // $data must contains : type, for to continue
        if (!array_key_exists('type', $data) && !array_key_exists('for', $data)) {
            throw new JsonException(ErrorsConstant::INVALID_REQUEST, Response::HTTP_BAD_REQUEST);
        }

        if ($data['type'] === VerificationTypeConstant::TYPE_SMS) {
            // $data must contains :  appSignature
            if (!array_key_exists('appSignature', $data)) {
                throw new JsonException(ErrorsConstant::INVALID_REQUEST, Response::HTTP_BAD_REQUEST);
            }
            $this->sendSMSCode($user, $user->getPhone(), $data['for'], $data['appSignature']);
        }

        if ($data['type'] === VerificationTypeConstant::TYPE_MAIL) {
            $this->sendMailCode($user, $user->getEmail(), $data['for']);
        }

        return new JsonResponse([], Response::HTTP_OK, ['message' => SuccessConstants::CODE_SENT_OTP]);
    }


    /**
     * @throws JsonException
     */
    public function updateUser(User $user, array $data): User
    {
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        if (isset($data['phone'])) {
            $user->setPhone($data['phone']);
        }

        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function getCurrentUser()
    {
        return $this->security->getUser();
    }

    public function getUserById(int $id): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $id]);
        
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé.');
        }

        return $user;
    }
    
    public function getWalletPublicAddress(): ?string
    {
        return $this->walletPublicAddress;
    }

    public function setWalletPublicAddress(?string $walletPublicAddress): self
    {
        $this->walletPublicAddress = $walletPublicAddress;

        return $this;
    }
}

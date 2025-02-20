<?php

namespace App\Service\Api\Linkcy;

use App\Common\Constants\Response\ErrorsConstant;
use App\Entity\Enum\TypeLedger;
use App\Entity\User\Bank;
use App\Entity\User\User;
use App\Service\Api\Linkcy\Partner\LoginService;
use App\Service\Base\BaseService;
use Brick\PhoneNumber\PhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

readonly class CreateService extends BaseService
{
    public const ENDPOINTS = [
        'createConsumer' => '/api/partner/consumers',
        'createLedger' => '/api/partner/ledgers',
        'createCard' => '/api/partner/cards',
        'createCardProfile' => '/api/partner/card-profiles',
    ];

    public function __construct(
        SerializerInterface                     $serializer,
        private readonly ParameterBagInterface  $bag,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct($serializer);
    }

    private function buildHttpClient(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client([
            'base_uri' => $this->bag->get('linkcy')['cloud'],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function createConsumer(User $user)
    {
        $httpClient = $this->buildHttpClient();

        if (is_null($user->getPhone())) {
            throw new \Exception(ErrorsConstant::CONSUMER_MUST_HAVE_PHONE);
        }
        $number = PhoneNumber::parse($user->getPhone());
        $phoneObject = [
            'countryCode' => $number->getCountryCode(),
            'number' => $number->getNationalNumber(),
        ];

        // Implement the logic to create a consumer in Linkcy API with GuzzleHttpClient
        $response = $httpClient->post(self::ENDPOINTS['createConsumer'], [
            'json' => [
                'partnerName' => $this->bag->get('linkcy')['partner_name'],
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'emailAddress' => $user->getEmail(),
                'phone' => $phoneObject,
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception(ErrorsConstant::LINKCY_API_ERROR_CREATE_CONSUMER);
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function createLedger(User $user)
    {
        $httpClient = $this->buildHttpClient();

        $consumer = $this->createConsumer($user);

        $response = $httpClient->post(self::ENDPOINTS['createLedger'], [
            'json' => [
                'endUserId' => $consumer['id'],
                'type' => TypeLedger::FRANCE,
                'autoUpgrade' => true,
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception(ErrorsConstant::LINKCY_API_ERROR_CREATE_LEDGER);
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function createCard(User $user)
    {
        $httpClient = $this->buildHttpClient();


        $ledger = $this->createLedger($user);
        $cardProfile = $this->createCardProfile($user);

        $response = $httpClient->post(self::ENDPOINTS['createCard'], [
            'headers' => [
                'Linkcy-SCA-Strategy' => 'BY_PASS',
                'Linkcy-SCA-Factor' => 'BIOMETRY',
            ],
            'json' => [
                "deliveryMethod" => "FRENCH_MAIL",
                "ledgerId" => $ledger['id'],
                "profileId" => $cardProfile['id'],
                "nameOnCard" => strtoupper($user->getFullName()),
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception(ErrorsConstant::LINKCY_API_ERROR_CREATE_CARD);
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function createCardProfile(User $user)
    {
        $httpClient = $this->buildHttpClient();

        $response = $httpClient->post(self::ENDPOINTS['createCardProfile'], [
            'json' => [
                "name" => "CARD_" . strtoupper($user->getFirstName()) . strtoupper($user->getLastName()),
                "cardDesignId" => $this->bag->get('newman')['consumer_virtual']
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception(ErrorsConstant::LINKCY_API_ERROR_CREATE_CARD_PROFILES);
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function getCardInformation(string $cardId)
    {
        $httpClient = $this->buildHttpClient();

        $cardInfoPath = '/api/partner/cards/' . $cardId;
        $response = $httpClient->get($cardInfoPath);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception(ErrorsConstant::LINKCY_API_ERROR_GET_CARD_INFO);
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws GuzzleException
     * @throws \DateMalformedStringException
     */
    public function createBankLocal(User $user): Bank
    {
        $bank = new Bank();
        $card = $this->createCard($user);
        $bank->setUid($card['id']);

        $cardInfos = $this->getCardInformation($card['id']);

        $bank
            ->setLinkcyProfileId($cardInfos['endUserId'])
            ->setLinkcyLedgerId($cardInfos['ledgerId'])
            ->setName($cardInfos['nameOnCard'])
            ->setExpiration(new \DateTime($cardInfos['nameOnCard']));

        $this->em->persist($bank);
        $this->em->flush();

        return $bank;
    }

}

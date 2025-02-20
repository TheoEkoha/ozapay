<?php

namespace App\Service\Api\Linkcy\Partner;

use App\Common\Constants\Response\ErrorsConstant;
use App\Service\Base\BaseService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class LoginService extends BaseService
{
    public function __construct(
        SerializerInterface                    $serializer,
        private readonly ParameterBagInterface $bag
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function loginPartner()
    {
        $loginPath = '/api/partner/login';

        $httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->bag->get('linkcy')['cloud']
        ]);

        $response = $httpClient->post($loginPath, [
            'json' => [
                'username' => $this->bag->get('linkcy')['partner_username'],
                'partnerName' => $this->bag->get('linkcy')['partner_name'],
                'password' => $this->bag->get('linkcy')['partner_password']
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception(ErrorsConstant::LINKCY_API_PARTNER_LOGIN_ERROR);
        }

        return json_decode($response->getBody()->getContents(), true);
    }

}

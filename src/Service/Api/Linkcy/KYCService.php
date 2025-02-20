<?php

namespace App\Service\Api\Linkcy;

use App\Service\Base\BaseService;
use Symfony\Component\Serializer\SerializerInterface;

readonly class KYCService extends BaseService
{
    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);
    }

}

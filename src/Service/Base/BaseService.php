<?php

namespace App\Service\Base;

use App\Service\Interfaces\BaseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

abstract readonly class BaseService implements BaseInterface
{
    public function __construct(
        protected SerializerInterface $serializer
    ) {
    }

    /**
     * @param array $data
     * @param string $type
     * @param string $format
     * @param array $options
     * @return mixed
     */
    public function deserialize(array $data, string $type, string $format = 'json', array $options = []): mixed
    {
        return $this->serializer->deserialize(
            json_encode($data),
            $type,
            $format,
            $options
        );
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param array $context
     * @return string
     */
    public function serialize(mixed $data, string $type, array $context = []): string
    {
        return $this->serializer->serialize($data, $type, $context);
    }

    /**
     * @param Request $request
     * @return array|mixed
     */
    public static function getPostedData(Request $request): mixed
    {
        $data = json_decode($request->getContent(), true);

        $request->request->replace(is_array($data) ? $data : $request->request->all());

        $data = $data ?: $request->request->all();

        if ($request->getContentTypeFormat() === "form") {
            $dataRequests = [];
            foreach ($data as $key => $requestData) {
                $requestData = $requestData === "true" ? true : $requestData;
                $requestData = $requestData === "false" ? false : $requestData;

                if (is_numeric($requestData)) {
                    $requestData = strpos($requestData, '.') ? (float)$requestData : (int)$requestData;
                }

                $dataRequests[$key] = $requestData;
            }
        } else {
            return $data;
        }

        return $dataRequests;
    }

}

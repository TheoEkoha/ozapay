<?php

namespace App\Service\Interfaces;

use Symfony\Component\HttpFoundation\Request;

interface BaseInterface
{
    public function deserialize(array $data, string $type, string $format = 'json', array $options = []): mixed;

    public function serialize(mixed $data, string $type, array $context = []): string;

    public static function getPostedData(Request $request): mixed;
}

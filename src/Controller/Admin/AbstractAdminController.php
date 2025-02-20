<?php

namespace App\Controller\Admin;

use App\JsResponse\JsResponseBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            JsResponseBuilder::class => JsResponseBuilder::class
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function js(): JsResponseBuilder
    {
        return $this->container->get(JsResponseBuilder::class);
    }
}

<?php
// src/EventListener/RequestLoggerListener.php
namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestLoggerListener
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->logger->info('API Call', [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
            'body' => json_decode($request->getContent(), true), // Si le corps est en JSON
        ]);
    }
}
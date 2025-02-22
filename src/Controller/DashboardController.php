<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Service\Api\User\UserService;

class DashboardController extends AbstractController
{
    private readonly UserService $userService;

    public function __construct(UserService $userService, private readonly TranslatorInterface $translator, private LoggerInterface $logger, private Security $security)
    {
        $this->userService = $userService;
    }

    #[Route('/admin', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->userService->getCurrentUser();

        if ($user) {
            $this->logger->info('Utilisateur connecté : ' . $user->getUsername());
        } else {
            $this->logger->warning('Aucun utilisateur connecté.');
        }

        return $this->render('pages/dashboard/dashboard-sales.html.twig', [
            'page_title' => $this->translator->trans('Dashboard'),
            'breadcrumbs' => [
                [
                    'title' => 'Dashboard',
                    'url' => 'dashboard',
                ]
            ]
        ]);
    }
}
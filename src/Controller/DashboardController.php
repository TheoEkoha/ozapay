<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;


class DashboardController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator, private LoggerInterface $logger, private readonly Security $security)
    {
    }

    #[Route('/admin', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->security->getUser(); // Récupère l'utilisateur connecté

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
            ],
            'user' => $user,
        ]);
    }
}

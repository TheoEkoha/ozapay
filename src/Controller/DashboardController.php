<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User\User; // Assurez-vous que le bon namespace est utilisé pour votre entité User

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    #[Route('/admin', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->security->getUser(); // Récupère l'utilisateur connecté

        if ($user instanceof User) {
            $this->logger->info('Utilisateur connecté : ' . $user->getUsername());
            // Mettez à jour la locale de l'application ici si nécessaire
            // Utilisez la locale de l'utilisateur pour ajuster le contenu affiché
            // Vous pourriez vouloir changer la locale ici si nécessaire
            // $this->get('translator')->setLocale($user->getLocal());
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
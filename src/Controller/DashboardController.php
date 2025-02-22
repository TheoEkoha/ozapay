<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Route('/admin', name: 'dashboard')]
    public function index(): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur connecté

        dump($user); // Vérifier si l'utilisateur est récupéré

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

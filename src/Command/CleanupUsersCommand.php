<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\User;
use DateTime;

#[AsCommand(
    name: 'app:cleanup-users',
    description: 'Supprime les utilisateurs avec une adresse mail spécifique et une date de création expirée',
)]
class CleanupUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $expiryDate = new DateTime();
        $expiryDate->sub(new \DateInterval('PT30M')); // Soustrait 30 minutes à l'heure actuelle

        $query = $this->entityManager->createQuery(
            'SELECT u FROM App\Entity\User u WHERE u.email LIKE :email AND u.createdAt < :expiryDate'
        )->setParameter('email', '%@ozapay@mailinator.com')
         ->setParameter('expiryDate', $expiryDate);

        $users = $query->getResult();

        if (empty($users)) {
            $output->writeln('Aucun utilisateur à supprimer.');
            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            $this->entityManager->remove($user);
            $output->writeln('Utilisateur supprimé : ' . $user->getEmail());
        }

        $this->entityManager->flush();
        $output->writeln('Nettoyage terminé.');

        return Command::SUCCESS;
    }
}

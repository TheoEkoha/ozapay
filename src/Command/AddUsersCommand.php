<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

class AddUsersCommand extends Command
{
    protected static $defaultName = 'app:import-users';
    private const BATCH_SIZE = 20;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Import users from CSV file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = 'chemin/vers/le/fichier.csv'; // Remplace par ton chemin réel

        if (!file_exists($filePath)) {
            $io->error('Le fichier CSV est introuvable.');
            return Command::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error("Impossible d'ouvrir le fichier.");
            return Command::FAILURE;
        }

        $headers = fgetcsv($handle, 0, ";");
        if (!$headers) {
            $io->error('Fichier CSV vide ou mal formaté.');
            return Command::FAILURE;
        }

        $importCount = 0;
        $skippedCount = 0;
        $i = 0;
        $existingPhones = [];
        
        $progressBar = new ProgressBar($output);
        $progressBar->start();

        while (($data = fgetcsv($handle, 0, ";")) !== false) {
            $rowData = array_combine($headers, $data);

            // Vérification des doublons en base
            if ($this->em->getRepository(User::class)->findOneBy(['email' => $rowData['email']])) {
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            // Vérification des doublons dans l'import en cours
            if (isset($existingPhones[$rowData['telephone']])) {
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            // Enregistrer ce téléphone comme utilisé
            $existingPhones[$rowData['telephone']] = true;

            $user = new User();
            $user->setEmail($rowData['email']);
            $user->setPhone($rowData['telephone']);
            // Ajoute les autres champs nécessaires ici
            
            $this->em->persist($user);
            $importCount++;
            $i++;

            if (($i % self::BATCH_SIZE) === 0) {
                $this->em->flush();
                $this->em->clear();
            }

            $progressBar->advance();
        }

        fclose($handle);
        $this->em->flush();

        $progressBar->finish();
        $io->success("Import terminé: $importCount utilisateurs importés, $skippedCount ignorés.");

        return Command::SUCCESS;
    }
}

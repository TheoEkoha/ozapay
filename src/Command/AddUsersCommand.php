<?php

namespace App\Command;

use App\Entity\Enum\Step;
use App\Entity\User\User;
use App\Entity\User\Admin;
use App\Entity\Enum\Status;
use App\Entity\User\Particular;
use App\Entity\User\Professional;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:add-users',
    description: 'Import users from CSV files',
)]
class AddUsersCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $parameterBag,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'delete-file',
                'd',
                InputOption::VALUE_NONE,
                'Delete CSV file after import'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $this->parameterBag->get('csv_user').'/';
        $deleteFile = $input->getOption('delete-file');

        $io->section("Adding users from $path");

        if (!is_dir($path)) {
            mkdir($path);
        }

        $files = array_diff(scandir($path), ['..', '.']);

        if (count($files) === 0) {
            $io->warning('No CSV files found in the directory.');
            return Command::SUCCESS;
        }

        foreach ($files as $file) {
            if (!str_ends_with(strtolower($file), '.csv')) {
                continue;
            }

            $io->info("Processing file: $file");

            try {
                $this->processFile($path . $file, $io);

                if ($deleteFile) {
                    unlink($path . $file);
                    $io->info("Deleted file: $file");
                }
            } catch (\Exception $e) {
                $io->error("Error processing file $file: " . $e->getMessage());
                continue;
            }
        }

        return Command::SUCCESS;
    }



private function processFile(string $filePath, SymfonyStyle $io): void
{
    // Début du traitement du fichier CSV
    $io->info("Processing file: " . basename($filePath));

    // Ouvrir le fichier CSV
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        $io->error("Failed to open file: " . basename($filePath));
        return;
    }

    // Lire les en-têtes du fichier CSV
    $headers = fgetcsv($handle, 0, ',');
    if (!$headers) {
        $io->error("Failed to read headers from file.");
        fclose($handle);
        return;
    }
    
    $io->info("Headers: " . json_encode($headers));

    // Préparer le tableau d'utilisateurs
    $userArray = [];

    // Progression de l'importation
    $progressBar = $io->createProgressBar(count(file($filePath)) - 1);  // -1 pour ignorer les en-têtes
    $progressBar->start();

    // Compteur de doublons
    $skippedCount = 0;

    // Traitement des lignes du fichier CSV
    while (($rowData = fgetcsv($handle, 0, ',')) !== false) {
        // Combinaison des données avec les en-têtes
        $rowData = array_combine($headers, $rowData);

        // Affichage des données de l'utilisateur en cours pour débogage
        $io->info("User data: " . json_encode($rowData));

        // Vérification de l'existence de l'email ou du téléphone dans la base de données
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $rowData['email']]);
        $existingUserByPhone = $this->em->getRepository(User::class)->findOneBy(['phone' => $rowData['telephone']]);

        if ($existingUser || $existingUserByPhone) {
            // Si un doublon est trouvé dans la base de données
            $skippedCount++;
            $progressBar->advance();
            continue;
        }

        // Vérification des doublons dans le tableau d'utilisateurs importés
        $isDuplicate = false;
        foreach ($userArray as $existingUser) {
            if ($existingUser['email'] === $rowData['email'] || $existingUser['telephone'] === $rowData['telephone']) {
                $isDuplicate = true;
                $skippedCount++;
                break;
            }
        }

        if ($isDuplicate) {
            $progressBar->advance();
            continue;
        }

        // Ajouter l'utilisateur au tableau pour éviter les doublons dans ce fichier
        $userArray[] = $rowData;

        // Créer l'utilisateur dans la base de données
        $user = new User();
        $user->setEmail($rowData['email']);
        $user->setPhone($rowData['telephone']);
        // Ajoutez d'autres informations utilisateur selon votre modèle

        // Persister l'utilisateur dans la base de données
        $this->em->persist($user);

        $progressBar->advance();
    }

    // Finaliser l'importation
    $this->em->flush();
    fclose($handle);

    // Affichage des résultats
    $progressBar->finish();
    $io->success("Import completed. Imported " . (count($userArray) - $skippedCount) . " users. Skipped $skippedCount duplicate entries.");
}

    private function createUser(array $rowData): User
    {
        $user = new User();
        $user = match($rowData['roles']) {
            'Professionnel' => new Professional(),
            'Particulier' => new Particular(),
            'Administrateur' => new Admin(),
            default => new Particular()
        };

        $roles = match($rowData['roles']) {
            'Professionnel' => ['ROLE_PRO'],
            'Particulier' => ['ROLE_USER'],
            'Administrateur' => ['ROLE_ADMIN'],
            default => ['ROLE_USER']
        };

        $user
            ->setRoles($roles)
            ->setFirstName($rowData['prenom'])
            ->setLastName($rowData['nom'])
            ->setEmail($rowData['email'] ?? null)
            ->setPhone($rowData['telephone'] ?? null)
            ->setPostalCode($rowData['codepostal'] ?? null)
            ->setCity($rowData['ville'] ?? null)
            ->setCountry($rowData['pays'] ?? null)
            ->setAddress($rowData['ville'] . ' ' . $rowData['codepostal'] . ' '. $rowData['pays'])
            ->setPassword($this->passwordHasher->hashPassword($user, '123456'))
            //->setStep(Step::Pin)
            ->setConditionAccepted(true)
            ->setMarketingAccepted(true)
            ->setLocal('en')
            ->setHasWallet(false)
            ->setStatus(Status::Published)
            ->setCreatedValue();

        return $user;
    }
}

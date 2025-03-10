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
        if (($handle = fopen($filePath, "r")) === false) {
            throw new \RuntimeException("Could not open file: $filePath");
        }

        // Count total rows (excluding header)
        $totalRows = -1;
        while (!feof($handle)) {
            if (fgetcsv($handle) !== false) {
                $totalRows++;
            }
        }
        rewind($handle);

        $headers = array_map('strtolower', fgetcsv($handle));
        $importCount = 0;
        $skippedCount = 0;
        $i = 0;
        $userArray = [];

        $progressBar = $io->createProgressBar($totalRows);
        $progressBar->start();

        $this->em->getConnection()->beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $headerString = $headers[0];
                $dataString = $data[0];

                $headerArray = explode(';', $headerString);
                $dataArray = explode(';', $dataString);

                $rowData = array_combine($headerArray, $dataArray);

                // Check if email already exists in database
                $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $rowData['email']]);
                $existingUserByPhone = $this->em->getRepository(User::class)->findOneBy(['phone' => $rowData['telephone']]);
                if ($existingUser || $existingUserByPhone) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Check for duplicates in current import
                $isDuplicate = false;
                foreach ($userArray as $existingUser) {
                    if ($existingUser['email'] === $rowData['email'] ||
                        $existingUser['telephone'] === $rowData['telephone']) {
                        $isDuplicate = true;
                        $skippedCount++;
                        break;
                    }
                }

                if (!$isDuplicate) {
                    $userArray[] = $rowData;
                    $user = $this->createUser($rowData);
                    $this->em->persist($user);
                    $importCount++;
                    $i++;

                    if (($i % self::BATCH_SIZE) === 0) {
                        $this->em->flush();
                        $this->em->clear();
                    }
                }

                $progressBar->advance();
            }

            // Final flush for remaining records
            $this->em->flush();
            $this->em->getConnection()->commit();

            $progressBar->finish();
            $io->newLine(2);

            fclose($handle);

            $io->success(sprintf(
                "Imported %d users. Skipped %d duplicate entries.",
                $importCount,
                $skippedCount
            ));
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            fclose($handle);
            throw $e;
        }
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

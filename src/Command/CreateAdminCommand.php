<?php

namespace App\Command;

use App\Entity\Enum\Status;
use App\Entity\Enum\Step;
use App\Entity\User\Admin;
use App\Entity\User\User;
use App\Utils\DataEncryption;
use App\Utils\Tools;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Add a short description for your command',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hash,
        private readonly EntityManagerInterface      $em,
        private readonly Tools                       $tools,
        private readonly DataEncryption              $dataEncryption,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The user\'s username.')
            ->addArgument('password', InputArgument::REQUIRED, 'The user\'s password.')
            ->addArgument('pin', InputArgument::REQUIRED, 'The user\'s security pin.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();

        $username = $input->getArgument('username');
        $plainPassword = $input->getArgument('password');
        $pin = $input->getArgument('pin');

        $io = new SymfonyStyle($input, $output);
        $io->section("Creating the user $username");

        $user = new Admin();
        $user->setEmail($username);
        $user->setPassword($this->hash->hashPassword($user, $plainPassword));

        $user->setFirstName($faker->firstName);
        $user->setLastName($faker->lastName);
        $user->setPhone($faker->phoneNumber);
        $user->setAddress($faker->address);
        $user->setCode(strtoupper($this->tools->generateRandomString(6)));

        $user->setConditionAccepted(true);
        $user->setMarketingAccepted(true);
        $user->setStatus(Status::Published);
        //$user->setStep(Step::Pin);
        $user->setPin($this->dataEncryption->encrypt($pin));

        $user->setCreatedValue();
        $user->setUpdatedValue();

        $this->em->persist($user);
        $this->em->flush();

        $io->success('New user created!');
        $io->text("Username : $username");
        $io->text("Password : $plainPassword");
        $io->text("Pin : $pin");

        return Command::SUCCESS;
    }
}

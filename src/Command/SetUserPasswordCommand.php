<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Compatible con PHP 7.4+ y Symfony 4.4/5.x (sin atributos PHP8).
 */
class SetUserPasswordCommand extends Command
{
    protected static $defaultName = 'app:user:set-password';
    protected static $defaultDescription = 'Set or reset a user password (hashing with configured password hasher).';

    /** @var EntityManagerInterface */
    private $em;

    /** @var UserPasswordHasherInterface */
    private $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'New plain password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $plain = (string) $input->getArgument('password');

        /** @var User|null $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $hashed = $this->passwordHasher->hashPassword($user, $plain);
        $user->setPassword($hashed);

        if (method_exists($user, 'setIsActive') && method_exists($user, 'getIsActive') && !$user->getIsActive()) {
            $user->setIsActive(true);
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Password updated for %s', $email));
        return Command::SUCCESS;
    }
}

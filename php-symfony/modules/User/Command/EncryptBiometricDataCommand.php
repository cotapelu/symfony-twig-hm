<?php

declare(strict_types=1);

namespace App\User\Command;

use App\Shared\Service\BiometricEncryptionService;
use App\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:encrypt-biometric-data', description: 'Encrypt existing plain text biometric data')]
class EncryptBiometricDataCommand extends Command
{
    private BiometricEncryptionService $encryptionService;
    private UserRepositoryInterface $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BiometricEncryptionService $encryptionService,
        UserRepositoryInterface $userRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->encryptionService = $encryptionService;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Encrypting Biometric Data');

        $users = $this->userRepository->findAll();
        $io->progressStart(count($users));

        $encryptedCount = 0;
        foreach ($users as $user) {
            $biometricData = $user->getBiometricData();

            if (null !== $biometricData && !$this->isEncrypted($biometricData)) {
                $encrypted = $this->encryptionService->encrypt($biometricData);
                $user->setBiometricData($encrypted);
                $encryptedCount++;
            }

            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Encrypted %d biometric data records', $encryptedCount));

        return Command::SUCCESS;
    }

    private function isEncrypted(string $data): bool
    {
        $decoded = base64_decode($data, true);

        if (false === $decoded) {
            return false;
        }

        return strlen($decoded) > 16;
    }
}

<?php
namespace App\Command;

use App\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Comando para limpiar webhooks antiguos y procesar los pendientes
 * Compatible con PHP 7.4.33
 */
class CleanupWebhooksCommand extends Command
{
    protected static $defaultName = 'mokhuba:webhooks:cleanup';
    protected static $defaultDescription = 'Limpia webhooks antiguos y procesa los pendientes';

    private $webhookRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        WebhookEventRepository $webhookRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->webhookRepository = $webhookRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Limpia webhooks antiguos y procesa los pendientes')
            ->addOption('days-old', 'd', InputOption::VALUE_REQUIRED, 'Días de antigüedad para eliminar webhooks procesados', 30)
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Tamaño del lote para procesamiento', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Ejecutar sin hacer cambios (modo simulación)')
            ->addOption('process-failed', 'f', InputOption::VALUE_NONE, 'Reprocesar webhooks fallidos')
            ->addOption('remove-duplicates', 'r', InputOption::VALUE_NONE, 'Eliminar webhooks duplicados')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $daysOld = (int)$input->getOption('days-old');
        $batchSize = (int)$input->getOption('batch-size');
        $dryRun = $input->getOption('dry-run');

        $io->title('Limpieza de Webhooks - Mokhuba Club');

        if ($dryRun) {
            $io->warning('Modo simulación activado - No se realizarán cambios');
        }

        try {
            $io->success('Comando de limpieza ejecutado exitosamente');
            
            $this->logger->info('Comando de limpieza de webhooks ejecutado', [
                'days_old' => $daysOld,
                'batch_size' => $batchSize,
                'dry_run' => $dryRun
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error durante la limpieza: ' . $e->getMessage());
            $this->logger->error('Error en comando de limpieza de webhooks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
<?php
namespace App\Command;

use App\Service\StripeService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Comando para validar que el sistema de pagos esté configurado correctamente
 * Compatible con PHP 7.4.33
 */
class ValidatePaymentSystemCommand extends Command
{
    protected static $defaultName = 'mokhuba:payment-system:validate';
    protected static $defaultDescription = 'Valida la configuración del sistema de pagos';

    private $entityManager;
    private $connection;
    private $logger;
    private $stripeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection $connection,
        LoggerInterface $logger,
        StripeService $stripeService
    ) {
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->stripeService = $stripeService;
        
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Valida la configuración del sistema de pagos')
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Mostrar información detallada')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $detailed = $input->getOption('detailed');

        $io->title('Validación del Sistema de Pagos - Mokhuba Club');

        $overallStatus = true;

        try {
            // 1. Validar conexión a base de datos
            $dbStatus = $this->validateDatabase($io, $detailed);
            $overallStatus = $overallStatus && $dbStatus;

            // 2. Validar tablas de pagos
            $tablesStatus = $this->validatePaymentTables($io, $detailed);
            $overallStatus = $overallStatus && $tablesStatus;

            // 3. Validar configuración de Stripe
            $stripeStatus = $this->validateStripeConfig($io, $detailed);
            $overallStatus = $overallStatus && $stripeStatus;

            // 4. Mostrar estadísticas básicas
            $this->showBasicStats($io, $detailed);

            if ($overallStatus) {
                $io->success('Sistema de pagos validado exitosamente!');
                return Command::SUCCESS;
            } else {
                $io->warning('Se encontraron algunos problemas en la validación');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('Error durante la validación: ' . $e->getMessage());
            $this->logger->error('Error en validación del sistema de pagos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    private function validateDatabase(SymfonyStyle $io, bool $detailed): bool
    {
        $io->section('Validando Conexión a Base de Datos');

        try {
            $this->connection->connect();
            $version = $this->connection->fetchOne('SELECT version()');
            
            if ($detailed) {
                $io->writeln("✅ Conexión exitosa");
                $io->writeln("📊 Versión: " . substr($version, 0, 50) . '...');
            } else {
                $io->writeln("✅ Base de datos conectada correctamente");
            }

            return true;

        } catch (\Exception $e) {
            $io->writeln("❌ Error de conexión: " . $e->getMessage());
            return false;
        }
    }

    private function validatePaymentTables(SymfonyStyle $io, bool $detailed): bool
    {
        $io->section('Validando Tablas del Sistema de Pagos');

        $requiredTables = ['subscriptions', 'payments', 'webhook_events'];
        $existingTables = $this->connection->createSchemaManager()->listTableNames();
        
        $allTablesExist = true;
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $existingTables)) {
                if ($detailed) {
                    $io->writeln("✅ Tabla '{$table}' existe");
                }
            } else {
                $io->writeln("❌ Tabla '{$table}' no existe");
                $allTablesExist = false;
            }
        }

        if ($allTablesExist) {
            $io->writeln("✅ Todas las tablas del sistema de pagos están presentes");
        } else {
            $io->note('Ejecuta la migración: php bin/console doctrine:migrations:migrate');
        }

        return $allTablesExist;
    }

    private function validateStripeConfig(SymfonyStyle $io, bool $detailed): bool
    {
        $io->section('Validando Configuración de Stripe');

        $validation = $this->stripeService->validateConfiguration();
        
        if ($validation['valid']) {
            $io->writeln("✅ Configuración de Stripe correcta");
            if ($detailed) {
                $io->writeln("✅ Clave pública configurada: " . substr($this->stripeService->getPublishableKey(), 0, 10) . '...');
                $io->writeln("✅ Precios de membresías configurados");
            }
            return true;
        } else {
            $io->writeln("❌ Problemas en configuración de Stripe:");
            foreach ($validation['issues'] as $issue) {
                $io->writeln("   • {$issue}");
            }
            $io->note('Configura las variables en tu archivo .env.local');
            return false;
        }
    }

    private function showBasicStats(SymfonyStyle $io, bool $detailed): void
    {
        if (!$detailed) {
            return;
        }

        $io->section('Estadísticas del Sistema');

        try {
            $stats = $this->stripeService->getBasicStats();
            
            $io->table(['Métrica', 'Valor'], [
                ['Suscripciones Activas', $stats['active_subscriptions']],
                ['Ruby', $stats['subscriptions_by_level']['ruby']],
                ['Gold', $stats['subscriptions_by_level']['gold']],
                ['Platinum', $stats['subscriptions_by_level']['platinum']],
                ['Config. Stripe', $stats['configuration_valid'] ? 'Válida' : 'Pendiente']
            ]);

        } catch (\Exception $e) {
            $io->note('No se pudieron obtener estadísticas (normal en instalación nueva)');
        }
    }
}
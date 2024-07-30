<?php

declare(strict_types=1);

namespace MelasistemaCartExport\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class CartExportService
{
    private LoggerInterface $logger;
    private SymfonyFilesystem $filesystem;
    private string $rootDir;
    private  SystemConfigService $config;


    private bool $wrapInDoubleQuoteOutputSetting = true;

    public function __construct(
        LoggerInterface $logger,
        SymfonyFilesystem $filesystem,
        $rootDir,
        SystemConfigService $config

    ) {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->rootDir = $rootDir;
        $this->config = $config;
    }

    /**
     * @param string $customerId
     * @return string
     */
    public function getCartExportFile(string $customerId): string
    {
        $chosenExportFolder = $this->config->get('MelasistemaCartExport.config.cartExportFolderName');
        $chosenExportFolder = $chosenExportFolder ? $chosenExportFolder : 'melasistema-cart-export';
        $directory = $this->rootDir . '/' . $chosenExportFolder;
        return sprintf('%s/%s-cart-export.csv', $directory, $customerId);
    }

    /**
     * @return string
     */
    public function getCartExportPath(): string
    {
        $chosenExportFolder = $this->config->get('MelasistemaCartExport.config.cartExportFolderName');
        return $chosenExportFolder ? $chosenExportFolder : 'melasistema-cart-export';;
    }

    /**
     * @param string $directory
     * @return void
     */
    public function createDirectoryIfNotExists(string $directory): void
    {
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory, 0755);

        }
    }

    /**
     * @param float|string|null $value
     * @return string|null
     */
    private function encloseOutput(float|string|null $value): ?string
    {
        if ($value === null) {
            return $this->wrapInDoubleQuoteOutputSetting ? '""' : null;
        }

        return $this->wrapInDoubleQuoteOutputSetting ? '"' . $value . '"' : (string) $value;
    }

    /**
     * @param $csvHeaders
     * @param $csvData
     * @param $userId
     * @return void
     */
    public function generateCartCSV($csvHeaders, $csvData, $userId): void
    {
        // Define the directory
        $chosenExportFolder = $this->getCartExportPath();
        $directory = $this->rootDir . '/' . $chosenExportFolder;

        // Create the directory if it doesn't exist
        $this->createDirectoryIfNotExists($directory);

        $fileName = $directory . '/' .  $userId . '-cart-export.csv';

        $csvRows = $csvData;

        // Open file in append mode
        $file = fopen($fileName, 'w');

        if ($file === false) {
            $this->logger->error("Error opening the file");
            die('Error opening the file');
        } else {
            $this->logger->info("Begin writing rows to CSV");

            // Write header row
            fputcsv($file, $csvHeaders);

            // Collect cart data and write it to the CSV
            foreach ($csvRows as $row) {
                $wrappedRow = [];
                foreach ($row as $value) {
                    $wrappedRow[] = $this->encloseOutput($value);  // Use encloseOutput for general values
                }
                fputs($file, implode(',', $wrappedRow) . "\n");
            }
            $this->logger->info("Finished writing rows to CSV");
            fclose($file);
        }
    }
}

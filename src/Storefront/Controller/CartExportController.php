<?php

namespace MelasistemaCartExport\Storefront\Controller;

use Exception;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use MelasistemaCartExport\Service\CartExportService;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 *
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Will be internal
 */
class CartExportController extends StorefrontController
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var string
     */
    private string $rootDir;

    /**
     * @var SystemConfigService
     */
    private  SystemConfigService $config;

    /**
     * @var CartExportService
     */
    private CartExportService $cartExportService;

    /**
     * @param LoggerInterface $logger
     * @param string $rootDir
     * @param SystemConfigService $config
     * @param CartExportService $cartExportService
     */
    public function __construct(
        LoggerInterface $logger,
        string $rootDir,
        SystemConfigService $config,
        CartExportService $cartExportService,
    )
    {
        $this->logger = $logger;
        $this->rootDir = $rootDir;
        $this->config = $config;
        $this->cartExportService = $cartExportService;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return Response|null
     */
    #[Route(path: '/checkout/cart-export', name: 'frontend.cart.export', options: ['seo' => false], defaults: ['XmlHttpRequest' => true, '_loginRequired' => true, '_loginRequiredAllowGuest' => false, '_noStore' => true], methods: ['GET'])]
    public function exportCart(SalesChannelContext $salesChannelContext): ?Response
    {
        try {

            $customerId = $salesChannelContext->getCustomerId();
            $downloadFileName = 'cart-export.csv'; // Fixed download filename

            if (!$customerId){
                throw new \Exception('We have problem to validate your request.');
            }

            $file = $this->cartExportService->getCartExportFile($customerId);

            if (!file_exists($file)) {
                throw new \Exception('Sorry but we have problems to generate your cart export, please go back and update you cart to let the system init your export!');
            }

            $response = new StreamedResponse();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setCharset('UTF-8');
            $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $downloadFileName . '"');

            $response->setCallback(function () use ($file) {
                $handle = fopen($file, 'r');
                while (!feof($handle)) {
                    echo fgets($handle);
                }
                fclose($handle);
            });

            return $response;

        } catch (Exception $exception) {
            // Handle the exception and return an appropriate error response
            return new Response($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

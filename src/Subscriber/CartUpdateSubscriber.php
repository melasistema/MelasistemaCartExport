<?php

declare(strict_types=1);

namespace MelasistemaCartExport\Subscriber;

use MelasistemaCartExport\Service\CartExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Event\CartChangedEvent;

class  CartUpdateSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;
    private CartExportService $cartExportService;

    /**
     * @param LoggerInterface $logger
     * @param CartExportService $cartExportService
     */
    public function __construct(
        LoggerInterface $logger,
        CartExportService $cartExportService
    )
    {
        $this->logger = $logger;
        $this->cartExportService = $cartExportService;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CartChangedEvent::class => 'exportCart',
        ];
    }

    /**
     * @param CartChangedEvent $event
     * @return void
     */
    public function exportCart(CartChangedEvent $event): void
    {
        $customer = $event->getContext()->getCustomer();
        if (!$customer){
            return;
        }

        $customerId = $event->getContext()->getCustomer()->getId();
        if ($customerId)
        {
            // Get the cart
            $cart = $event->getCart();
            // Access cart data:
            $cartItems = $cart->getLineItems();
            // Define CSV headers
            $csvHeaders = [
                'sku',
                'name',
                'quantity',
                'unit_price',
                'total_price'
            ];
            // Extract relevant information for CSV
            $csvData = [];

            foreach ($cartItems as $item) {

                $sku = $item->getPayload()['productNumber']; // Access SKU directly from CartItem

                $csvData[] = [
                    $sku,
                    $item->getLabel(), // Access product name from CartItem
                    $item->getQuantity(),
                    $item->getPrice()->getUnitPrice(),
                    $item->getPrice()->getTotalPrice(),
                ];
            }

            $this->cartExportService->generateCartCSV($csvHeaders, $csvData, $customerId);
        }
    }
}

<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Observer;

use Hmh\WishlistPriceWatchMessaging\Model\Config\ConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

class ProductPriceDropLoggerObserver implements ObserverInterface
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly PublisherInterface $publisher,
        private readonly LoggerInterface $logger
    ) {
    }

    private const TOPIC_PRICE_DROP = 'hmh.wishlist.price.drop';

    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        if (!$product) {
            return;
        }

        $storeId = (int)$product->getStoreId();
        if (!$this->configProvider->isEnabled($storeId > 0 ? $storeId : null)) {
            return;
        }

        $oldPrice = $product->getOrigData('price');
        $newPrice = $product->getData('price');

        if (!is_numeric($oldPrice) || !is_numeric($newPrice)) {
            return;
        }

        $oldPrice = (float)$oldPrice;
        $newPrice = (float)$newPrice;

        if ($newPrice >= $oldPrice) {
            return;
        }

        $message = [
            'product_id' => (int)$product->getId(),
            'sku' => (string)$product->getSku(),
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
        ];

        try {
            $this->publisher->publish(self::TOPIC_PRICE_DROP, json_encode($message, JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to publish price drop message.',
                [
                    'product_id' => (int)$product->getId(),
                    'sku' => (string)$product->getSku(),
                    'exception' => $exception->getMessage(),
                ]
            );
        }
    }
}

<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\MessageQueue;

use Hmh\WishlistPriceWatch\Api\WishlistPriceWatchRepositoryInterface;
use Hmh\WishlistPriceWatch\Model\WishlistPriceWatch as WishlistPriceWatchModel;
use Hmh\WishlistPriceWatch\Model\ResourceModel\WishlistPriceWatch\CollectionFactory as WishlistPriceWatchCollectionFactory;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationDispatcher;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationDataFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class PriceDropMessageProcessor
{
    public function __construct(
        private readonly WishlistPriceWatchCollectionFactory $wishlistPriceWatchCollectionFactory,
        private readonly WishlistPriceWatchRepositoryInterface $wishlistPriceWatchRepository,
        private readonly NotificationDispatcher $notificationDispatcher,
        private readonly NotificationDataFactory $notificationDataFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function process(array $payload): void
    {
        $productId = isset($payload['product_id']) ? (int)$payload['product_id'] : 0;
        $newPrice = isset($payload['new_price']) ? (float)$payload['new_price'] : 0.0;
        if ($productId <= 0 || $newPrice <= 0.0) {
            return;
        }

        $collection = $this->wishlistPriceWatchCollectionFactory->create();
        $collection->getSelect()
            ->join(
                ['wi' => $collection->getTable('wishlist_item')],
                'main_table.wishlist_item_id = wi.wishlist_item_id',
                ['wishlist_item_store_id' => 'store_id']
            )
            ->join(
                ['w' => $collection->getTable('wishlist')],
                'wi.wishlist_id = w.wishlist_id',
                ['customer_id' => 'customer_id']
            )
            ->where('wi.product_id = ?', $productId);

        $priceWatchItems = array_values($collection->getItems());
        array_walk(
            $priceWatchItems,
            fn (WishlistPriceWatchModel $priceWatch) => $this->processPriceWatchItem($priceWatch, $productId, $newPrice)
        );
    }

    private function processPriceWatchItem(WishlistPriceWatchModel $priceWatch, int $productId, float $newPrice): void
    {
        $wishlistItemId = (int)$priceWatch->getData('wishlist_item_id');
        $storeId = (int)$priceWatch->getData('wishlist_item_store_id');
        if ($storeId <= 0) {
            return;
        }

        $updatedPrice = (float)$priceWatch->getData('updated_price');
        $basePrice = (float)$priceWatch->getData('price');
        $comparePrice = $updatedPrice > 0.0 ? $updatedPrice : $basePrice;
        $productData = $this->getProductDataByStore($productId, $storeId);
        $currentFinalPrice = $productData['final_price'];
        $productName = $productData['name'];
        $dropPercentage = $this->calculateDropPercentage($comparePrice, $currentFinalPrice);

        if ($currentFinalPrice <= 0.0 || !$this->isPriceDropped($priceWatch, $currentFinalPrice)) {
            return;
        }

        $customerId = (int)$priceWatch->getData('customer_id');
        if ($customerId <= 0 || $storeId <= 0) {
            return;
        }

        try {
            $notificationData = $this->notificationDataFactory->create([
                'customerId' => $customerId,
                'storeId' => $storeId,
                'productName' => $productName,
                'productUrl' => $productData['url'],
                'dropPercentage' => $dropPercentage,
            ]);
            $this->notificationDispatcher->dispatch($notificationData);

            $priceWatch->setData('updated_price', $currentFinalPrice);
            $this->wishlistPriceWatchRepository->save($priceWatch);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to process price drop message entry.',
                [
                    'product_id' => $productId,
                    'wishlist_item_id' => $wishlistItemId,
                    'new_price' => $newPrice,
                    'current_final_price' => $currentFinalPrice,
                    'exception' => $exception->getMessage()
                ]
            );
        }
    }

    private function isPriceDropped(WishlistPriceWatchModel $priceWatch, float $currentFinalPrice): bool
    {
        $updatedPrice = (float)$priceWatch->getData('updated_price');
        $basePrice = (float)$priceWatch->getData('price');
        $comparePrice = $updatedPrice > 0.0 ? $updatedPrice : $basePrice;

        return $comparePrice > 0.0 && $currentFinalPrice < $comparePrice;
    }

    /**
     * @return array{name: string, final_price: float, url: string}
     */
    private function getProductDataByStore(int $productId, int $storeId): array
    {
        try {
            $product = $this->productRepository->getById($productId, false, $storeId, true);
            return [
                'name' => (string)$product->getName(),
                'final_price' => (float)$product->getFinalPrice(),
                'url' => (string)$product->getProductUrl(),
            ];
        } catch (\Throwable $exception) {
            return [
                'name' => '',
                'final_price' => 0.0,
                'url' => '',
            ];
        }
    }

    private function calculateDropPercentage(float $comparePrice, float $currentFinalPrice): int
    {
        if ($comparePrice <= 0.0 || $currentFinalPrice >= $comparePrice) {
            return 0;
        }

        return (int)round((($comparePrice - $currentFinalPrice) / $comparePrice) * 100);
    }
}

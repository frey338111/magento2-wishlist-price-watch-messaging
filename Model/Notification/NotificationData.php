<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification;

class NotificationData
{
    public function __construct(
        private readonly int $customerId,
        private readonly int $storeId,
        private readonly string $productName,
        private readonly string $productUrl,
        private readonly int $dropPercentage
    ) {
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getProductUrl(): string
    {
        return $this->productUrl;
    }

    public function getDropPercentage(): int
    {
        return $this->dropPercentage;
    }
}


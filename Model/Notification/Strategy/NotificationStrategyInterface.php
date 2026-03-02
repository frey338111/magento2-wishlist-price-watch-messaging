<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy;

use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationData;

interface NotificationStrategyInterface
{
    public function notify(NotificationData $notificationData): void;
}


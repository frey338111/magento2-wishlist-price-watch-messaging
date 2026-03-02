<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy;

use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationData;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\Service\InternalMessageSender;

class InternalMessageNotificationStrategy implements NotificationStrategyInterface
{
    public function __construct(
        private readonly InternalMessageSender $internalMessageSender
    ) {
    }

    public function notify(NotificationData $notificationData): void
    {
        $this->internalMessageSender->send($notificationData);
    }
}

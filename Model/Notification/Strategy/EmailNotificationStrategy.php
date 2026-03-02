<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy;

use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationData;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\Service\EmailSender;

class EmailNotificationStrategy implements NotificationStrategyInterface
{
    public function __construct(
        private readonly EmailSender $emailSender
    ) {
    }

    public function notify(NotificationData $notificationData): void
    {
        $this->emailSender->send($notificationData);
    }
}


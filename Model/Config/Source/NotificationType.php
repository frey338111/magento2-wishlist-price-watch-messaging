<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Config\Source;

use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationDispatcher;
use Magento\Framework\Data\OptionSourceInterface;

class NotificationType implements OptionSourceInterface
{
    public const INTERNAL_MESSAGE = 'internal_message';
    public const EMAIL_NOTIFICATION = 'email_notification';

    public function __construct(
        private readonly NotificationDispatcher $notificationDispatcher
    ) {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->notificationDispatcher->getNotificationStrategiesCode() as $code) {
            $options[] = [
                'value' => $code,
                'label' => ucwords(str_replace('_', ' ', $code)),
            ];
        }

        return $options;
    }
}

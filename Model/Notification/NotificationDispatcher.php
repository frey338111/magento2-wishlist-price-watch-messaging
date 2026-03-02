<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification;

use Hmh\WishlistPriceWatchMessaging\Model\Config\ConfigProvider;
use Hmh\WishlistPriceWatchMessaging\Model\Config\Source\NotificationType;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\EmailNotificationStrategy;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\InternalMessageNotificationStrategy;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\NotificationStrategyInterface;
use Psr\Log\LoggerInterface;

class NotificationDispatcher
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly InternalMessageNotificationStrategy $internalMessageStrategy,
        private readonly EmailNotificationStrategy $emailStrategy,
        private readonly LoggerInterface $logger
    ) {
    }

    public function dispatch(NotificationData $notificationData): void
    {
        $storeId = $notificationData->getStoreId();
        if (!$this->configProvider->isEnabled($storeId)) {
            return;
        }

        $notificationType = $this->configProvider->getNotificationType($storeId);
        foreach ($this->resolveStrategies($notificationType) as $strategy) {
            try {
                $strategy->notify($notificationData);
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Failed to execute notification strategy.',
                    [
                        'notification_type' => $notificationType,
                        'customer_id' => $notificationData->getCustomerId(),
                        'store_id' => $storeId,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }
    }

    /**
     * @return NotificationStrategyInterface[]
     */
    private function resolveStrategies(string $notificationType): array
    {
        return match ($notificationType) {
            NotificationType::INTERNAL_MESSAGE => [$this->internalMessageStrategy],
            NotificationType::EMAIL => [$this->emailStrategy],
            NotificationType::BOTH => [$this->internalMessageStrategy, $this->emailStrategy],
            default => [$this->internalMessageStrategy],
        };
    }
}

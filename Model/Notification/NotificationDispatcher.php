<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification;

use Hmh\WishlistPriceWatchMessaging\Model\Config\ConfigProvider;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\Strategy\NotificationStrategyInterface;
use Psr\Log\LoggerInterface;

class NotificationDispatcher
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly LoggerInterface $logger,
        private readonly array $notificationStrategies
    ) {
    }

    public function dispatch(NotificationData $notificationData): void
    {
        $storeId = $notificationData->getStoreId();
        if (!$this->configProvider->isEnabled($storeId)) {
            return;
        }

        $notificationTypes = $this->configProvider->getNotificationTypes($storeId);
        $strategies = $this->resolveStrategies($notificationTypes);

        foreach ($strategies as $strategyCode => $strategy) {
            try {
                $strategy->notify($notificationData);
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Failed to execute notification strategy.',
                    [
                        'notification_type' => $notificationTypes,
                        'strategy_code' => $strategyCode,
                        'customer_id' => $notificationData->getCustomerId(),
                        'store_id' => $storeId,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }
    }

    public function getNotificationStrategiesCode(): array
    {
        return array_keys($this->notificationStrategies);
    }

    /**
     * @param string[] $notificationTypes
     * @return array<string, NotificationStrategyInterface>
     */
    private function resolveStrategies(array $notificationTypes): array
    {
        $strategies = [];
        foreach ($notificationTypes as $notificationType) {
            if (
                !isset($this->notificationStrategies[$notificationType])
                || !$this->notificationStrategies[$notificationType] instanceof NotificationStrategyInterface
            ) {
                continue;
            }
            $strategy = $this->notificationStrategies[$notificationType];
            $strategies[$notificationType] = $strategy;
        }

        return $strategies;
    }
}

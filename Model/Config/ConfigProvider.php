<?php

declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Config;

use Hmh\WishlistPriceWatchMessaging\Model\Config\Source\NotificationType;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    public const XML_PATH_ENABLED = 'hmh_wishlistpricewatchmessaging/general/enabled';
    public const XML_PATH_NOTIFICATION_TYPE = 'hmh_wishlistpricewatchmessaging/general/notification_type';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return string[]
     */
    public function getNotificationTypes(?int $storeId = null): array
    {
        $value = (string)$this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === '') {
            return [NotificationType::INTERNAL_MESSAGE];
        }

        $notificationTypes = array_values(array_filter(array_map('trim', explode(',', $value))));
        return $notificationTypes !== [] ? $notificationTypes : [NotificationType::INTERNAL_MESSAGE];
    }
}

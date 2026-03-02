<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class NotificationType implements OptionSourceInterface
{
    public const INTERNAL_MESSAGE = 'internal_message';
    public const EMAIL = 'email';
    public const BOTH = 'both';

    /**
     * @return array<int, array<string, string>>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::INTERNAL_MESSAGE, 'label' => (string)__('Internal Message')],
            ['value' => self::EMAIL, 'label' => (string)__('Email')],
            ['value' => self::BOTH, 'label' => (string)__('Both')],
        ];
    }
}

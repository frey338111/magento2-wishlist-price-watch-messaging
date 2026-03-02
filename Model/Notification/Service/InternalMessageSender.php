<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification\Service;

use Hmh\InternalMessage\Api\InternalMessageManagementInterface;
use Hmh\InternalMessage\Model\Data\InternalMessageDtoFactory;
use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationData;
use Magento\Framework\Escaper;
use Magento\Framework\Url\Validator as UrlValidator;

class InternalMessageSender
{
    public function __construct(
        private readonly InternalMessageManagementInterface $internalMessageManagement,
        private readonly InternalMessageDtoFactory $internalMessageDtoFactory,
        private readonly UrlValidator $urlValidator,
        private readonly Escaper $escaper
    ) {
    }

    public function send(NotificationData $notificationData): void
    {
        $messageDto = $this->internalMessageDtoFactory->create();
        $messageDto->setData([
            'title' => (string)__('Your Wishlist Item Just Dropped in Price'),
            'message_content' => $this->buildMessageContent($notificationData),
            'customer_id' => $notificationData->getCustomerId(),
            'store_id' => $notificationData->getStoreId(),
        ]);

        $this->internalMessageManagement->createMessage($messageDto);
    }

    private function buildMessageContent(NotificationData $notificationData): string
    {
        $name = $notificationData->getProductName() !== ''
            ? $notificationData->getProductName()
            : (string)__('A product');

        $message = (string)__(
            '%1 from your wishlist has dropped by %2%%.',
            $name,
            $notificationData->getDropPercentage()
        );

        $productUrl = $notificationData->getProductUrl();
        if (!$this->urlValidator->isValid($productUrl)) {
            return $message;
        }

        return $message . ' ' . sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            $this->escaper->escapeUrl($productUrl),
            (string)__('View product')
        );
    }
}

<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\Notification\Service;

use Hmh\WishlistPriceWatchMessaging\Model\Notification\NotificationData;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

class EmailSender
{
    private const EMAIL_TEMPLATE_ID = 'hmh_wishlist_price_drop_email_template';

    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation
    ) {
    }

    public function send(NotificationData $notificationData): void
    {
        try {
            $customer = $this->customerRepository->getById($notificationData->getCustomerId());
        } catch (NoSuchEntityException $exception) {
            return;
        }

        $customerEmail = (string)$customer->getEmail();
        if ($customerEmail === '') {
            return;
        }

        $customerName = trim((string)$customer->getFirstname() . ' ' . (string)$customer->getLastname());
        $customerName = $customerName !== '' ? $customerName : (string)__('Customer');

        $this->inlineTranslation->suspend();
        try {
            $productName = $notificationData->getProductName() !== ''
                ? $notificationData->getProductName()
                : (string)__('A product');

            $transport = $this->transportBuilder
                ->setTemplateIdentifier(self::EMAIL_TEMPLATE_ID)
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $notificationData->getStoreId(),
                ])
                ->setTemplateVars([
                    'customer_name' => $customerName,
                    'product_name' => $productName,
                    'drop_percentage' => $notificationData->getDropPercentage(),
                    'product_url' => $notificationData->getProductUrl(),
                ])
                ->setFromByScope('general', $notificationData->getStoreId())
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}

<?php
declare(strict_types=1);

namespace Hmh\WishlistPriceWatchMessaging\Model\MessageQueue;

use Psr\Log\LoggerInterface;

class PriceDropMessageConsumer
{
    public function __construct(
        private readonly PriceDropMessageProcessor $priceDropMessageProcessor,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(string $message): void
    {
        try {
            $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->logger->error(
                'Invalid price drop queue message payload.',
                ['message' => $message]
            );
            return;
        }

        $this->priceDropMessageProcessor->process($payload);
    }
}

<?php

namespace TWEvents;

use Aws\Kinesis\KinesisClient as AwsKinesisClient;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;

class KinesisEventBusDecorator implements EventBus
{
    const PRODUCT_ID = 17;

    const STREAM_NAME = 'tw_events-massagebook-production';

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var SessionManager
     */
    private $session;

    /**
     * @var AwsKinesisClient
     */
    private $kinesis;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * KinesisEventBusDecorator constructor.
     * @param EventBus $eventBus
     * @param AwsKinesisClient $kinesis
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventBus $eventBus, 
        SessionManager $session, 
        AwsKinesisClient $kinesis, 
        LoggerInterface $logger
    )
    {
        $this->eventBus = $eventBus;
        $this->session = $session;
        $this->kinesis = $kinesis;
        $this->logger = $logger;
    }
    
    /**
     * Fire an event and send it to Kinesis.
     *
     * @param Event $event
     * @return void
     */
    public function fire(Event $event): void
    {
        if ($event instanceof TogetherworkEvent) {
            $payload = $this->getPayload($event);

            try {
                $this->kinesis->putRecord([
                    'StreamName'   => self::STREAM_NAME,
                    'PartitionKey' => $this->getPartitionKey(),
                    'Data'         => json_encode($payload)
                ]);
            } catch (AwsException $e) {
                // Log error
                $this->logger->error('Failed to send event to Kinesis', [
                    'exception' => $e,
                    'event' => $event,
                ]);
            }
        }

        $this->eventBus->fire($event);
    }

    private function getPayload(TogetherworkEvent $event): array
    {
        return [
            'product_id' => self::PRODUCT_ID,
            'tw_event' => [
                'name' => $event->getName(),
                'attributes' => $event->getAttributes()
            ],
            'identity' => [
                'user_id' => $this->session->getUserId(),
                'customer_id' => $this->session->getCustomerId()
            ],
            'context' => [
                'unix_timestamp' => time(),
                'platform' => $this->session->getPlatform(),
                'environment' => $this->session->getEnvironment(),
                'session_id' => $this->session->getSessionId(),
                'request_id' => $this->session->getRequestId(),
            ]
        ];
    }

    private function getPartitionKey(): string
    {
        return self::PRODUCT_ID . '-' . $this->session->getCustomerId();
    }
}
<?php

namespace Tests\Unit;

use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Tests\Mocks\KinesisClientMock as KinesisClient;
use TWEvents\ {
    Event,
    TWEvent,
    EventBus,
    SessionManager,
    KinesisEventBusDecorator
};

class KinesisEventBusDecoratorTest extends TestCase
{
    private EventBus $eventBus;

    private KinesisEventBusDecorator $decorator;

    private SessionManager $session;

    private KinesisClient $kinesis;

    private LoggerInterface $logger;

    private TWEvent $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = $this->createMock(EventBus::class);
        $this->session = $this->createMock(SessionManager::class);
        $this->kinesis = $this->createMock(KinesisClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->decorator = new KinesisEventBusDecorator(
            $this->eventBus,
            $this->session,
            $this->kinesis,
            $this->logger
        );

        $this->event = $this->createMock(TWEvent::class);
        $this->event->method('getName')->willReturn('test.event');
        $this->event->method('getAttributes')->willReturn(['key' => 'value']);

        foreach ([
            'getUserId' => '12',
            'getCustomerId' => '34',
            'getPlatform' => 'web',
            'getEnvironment' => 'production',
            'getSessionId' => '1234',
            'getRequestId' => '5678',
        ] as $method => $value) {
            $this->session->method($method)->willReturn($value);
        }
    }

    protected function getPayload(): array
    {
        return [
            'product_id' => KinesisEventBusDecorator::PRODUCT_ID,
            'tw_event' => [
                'name' => $this->event->getName(),
                'attributes' => $this->event->getAttributes()
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

    public function testItPassesEventToEventBus(): void
    {
        // setup
        $this->eventBus->expects($this->once())->method('fire')->with($this->event);

        // execute
        $this->decorator->fire($this->event);
    }

    public function testItPassesNonTogetherworkEventToEventBus(): void
    {
        // setup
        $nonTogetherworkEvent = $this->createMock(Event::class);

        $this->eventBus->expects($this->once())->method('fire')->with($nonTogetherworkEvent);

        // execute
        $this->decorator->fire($nonTogetherworkEvent);
    }

    public function testItSendsEventToKinesis(): void
    {
        // setup
        $payload = $this->getPayload();

        $this->eventBus->expects($this->once())->method('fire')->with($this->event);
        $this->kinesis->expects($this->once())
            ->method('putRecord')
            ->with([
                'StreamName' => KinesisEventBusDecorator::STREAM_NAME,
                'Data' => json_encode($payload),
                'PartitionKey' => KinesisEventBusDecorator::PRODUCT_ID . '-' . $this->session->getCustomerId(),
            ]);

        // execute
        $this->decorator->fire($this->event);
    }

    public function testItLogsErrorWhenKinesisFails(): void
    {
        // setup
        $awsException = $this->createMock(AwsException::class);
        $this->kinesis->method('putRecord')->willThrowException($awsException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send event to Kinesis',
                [
                    'exception' => $awsException,
                    'event' => $this->event
                ]
            );

        $this->eventBus->expects($this->once())->method('fire')->with($this->event);

        // execute
        $this->decorator->fire($this->event);
    }
}
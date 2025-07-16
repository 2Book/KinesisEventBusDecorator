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
        $payload = [
            'product_id' => KinesisEventBusDecorator::PRODUCT_ID,
            'tw_event' => [
                'name' => $this->event->getName(),
                'attributes' => $this->event->getAttributes()
            ],
            'identity' => [
                'user_id' => '12',
                'customer_id' => '34'
            ],
            'context' => [
                'unix_timestamp' => time(),
                'platform' => 'web',
                'environment' => 'production',
                'session_id' => '1234',
                'request_id' => '5678',
            ]
        ];

        $this->session->method('getUserId')->willReturn('12');
        $this->session->method('getCustomerId')->willReturn('34');
        $this->session->method('getPlatform')->willReturn('web');
        $this->session->method('getEnvironment')->willReturn('production');
        $this->session->method('getSessionId')->willReturn('1234');
        $this->session->method('getRequestId')->willReturn('5678');

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
        $this->kinesis->method('putRecord')->willThrowException($this->createMock(AwsException::class));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send event to Kinesis',
                $this->callback(function ($context) {
                    return isset($context['exception']) && $context['exception'] instanceof AwsException &&
                           isset($context['event']) && $context['event'] instanceof TWEvent;
                })
            );

        $this->eventBus->expects($this->once())->method('fire')->with($this->event);

        // execute
        $this->decorator->fire($this->event);
    }
}
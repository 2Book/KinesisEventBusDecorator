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
use Mockery;

class MockeryKinesisEventBusDecoratorTest extends TestCase
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
        $this->eventBus = Mockery::mock(EventBus::class);
        $this->session = Mockery::mock(SessionManager::class);
        $this->kinesis = Mockery::mock(KinesisClient::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->session->allows([
            'getUserId' => '12',
            'getCustomerId' => '34',
            'getPlatform' => 'web',
            'getEnvironment' => 'production',
            'getSessionId' => '1234',
            'getRequestId' => '5678'
        ]);

        $this->decorator = new KinesisEventBusDecorator(
            $this->eventBus,
            $this->session,
            $this->kinesis,
            $this->logger
        );

        $this->event = Mockery::mock(TWEvent::class);
        $this->event->shouldReceive('getName')->andReturn('test.event');
        $this->event->shouldReceive('getAttributes')->andReturn(['key' => 'value']);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItPassesEventToEventBus(): void
    {
        // setup
        $this->eventBus->expects()->fire($this->event)->once();
        $this->kinesis->expects()->putRecord(Mockery::any())->once();

        // execute
        $this->decorator->fire($this->event);
    }

    public function testItPassesNonTogetherworkEventToEventBus(): void
    {
        // setup
        $nonTogetherworkEvent = Mockery::mock(Event::class);
        $this->eventBus->expects()->fire($nonTogetherworkEvent)->once();

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

        $this->eventBus->expects()->fire($this->event)->once();
        $this->kinesis->expects()->putRecord([
            'StreamName' => KinesisEventBusDecorator::STREAM_NAME,
            'Data' => json_encode($payload),
            'PartitionKey' => KinesisEventBusDecorator::PRODUCT_ID . '-' . $this->session->getCustomerId()
        ])->once();

        // execute
        $this->decorator->fire($this->event);
    }

    public function testItLogsErrorWhenKinesisFails(): void
    {
        // setup
        $awsException = Mockery::mock(AwsException::class);
        
        $this->kinesis->expects()->putRecord(Mockery::any())->once()->andThrow($awsException);

        $this->logger->expects()->error(
            'Failed to send event to Kinesis',
            [
                'exception' => $awsException,
                'event' => $this->event
            ]
        )->once();

        $this->eventBus->expects()->fire($this->event)->once();

        // execute
        $this->decorator->fire($this->event);
    }
}

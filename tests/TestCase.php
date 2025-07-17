<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use TWEvents\ {
    TWEvent,
    SessionManager,
    KinesisEventBusDecorator
};

class TestCase extends PHPUnitTestCase
{
    public function tearDown(): void
    {
        // Mark test as having assertion to avoid "risky" status when using Mockery
        $this->addToAssertionCount(1);
        parent::tearDown();
    }

    public static function provideEventScenarios(): array
    {
        return [
            'basic event' => [
                'event.created',
                ['id' => 1, 'status' => 'active']
            ],
            'event with nested attributes' => [
                'user.profile.updated',
                [
                    'user' => [
                        'id' => 123,
                        'profile' => ['name' => 'John', 'age' => 30]
                    ]
                ]
            ],
            'event with empty attributes' => [
                'cache.cleared',
                []
            ]
        ];
    }

    protected static function getPayload(TWEvent $event, SessionManager $session): array
    {
        return [
            'product_id' => KinesisEventBusDecorator::PRODUCT_ID,
            'tw_event' => [
                'name' => $event->getName(),
                'attributes' => $event->getAttributes()
            ],
            'identity' => [
                'user_id' => $session->getUserId(),
                'customer_id' => $session->getCustomerId()
            ],
            'context' => [
                'unix_timestamp' => time(),
                'platform' => $session->getPlatform(),
                'environment' => $session->getEnvironment(),
                'session_id' => $session->getSessionId(),
                'request_id' => $session->getRequestId(),
            ]
        ];
    }
}
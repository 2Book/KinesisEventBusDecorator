<?php

namespace Tests\Mocks;

use Aws\Result;
use Aws\Kinesis\KinesisClient;

/**
 * Mock implementation of KinesisClient for testing purposes.
 * 
 * This class extends the KinesisClient and overrides the putRecord method
 * because it is implemented in the KinesisClient by a magic method (__call)
 * which is not suitable for mocking in tests.
 */
class KinesisClientMock extends KinesisClient
{
    /**
     * Mock implementation of putRecord method.
     *
     * @param array $args
     * @return Result
     * @throws \BadMethodCallException
     */
    public function putRecord($args = []): Result
    {
        throw new \BadMethodCallException('putRecord is not implemented in KinesisClientMock');
        
        // Mock implementation for putRecord
        return new Result(['ShardId' => 'shardId-000000000000', 'SequenceNumber' => '1234567890']);
    }
}
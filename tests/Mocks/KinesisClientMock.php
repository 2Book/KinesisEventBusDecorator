<?php

namespace Tests\Mocks;

use Aws\Result;
use Aws\Kinesis\KinesisClient;

class KinesisClientMock extends KinesisClient
{
    // Implement any necessary mock methods or properties here
    public function putRecord($args = []): Result
    {
        throw new \BadMethodCallException('putRecord is not implemented in KinesisClientMock');
        
        // Mock implementation for putRecord
        return new Result(['ShardId' => 'shardId-000000000000', 'SequenceNumber' => '1234567890']);
    }
}
<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    public function tearDown(): void
    {
        // Mark test as having assertion to avoid "risky" status when using Mockery
        $this->addToAssertionCount(1);
        parent::tearDown();
    }
}
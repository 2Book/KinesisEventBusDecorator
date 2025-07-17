# TW Events - Unit Testing Guide

This guide explores unit testing concepts through practical examples from this codebase, demonstrating essential testing patterns and practices in PHP.

## Understanding Unit Testing with Dependencies

Modern PHP applications often use dependency injection to create loosely coupled, testable code. Let's examine how this works using the `KinesisEventBusDecorator` test as an example.

### Dependency Injection and Mocking

Consider this constructor from our tests:

```php
$this->decorator = new KinesisEventBusDecorator(
    $this->eventBus,
    $this->session,
    $this->kinesis,
    $this->logger
);
```

Rather than creating these dependencies inside the class, they're injected through the constructor. This pattern allows us to:
1. Substitute real implementations with test doubles (mocks)
2. Control the behavior of these dependencies during tests
3. Isolate the unit under test from external services

### Using PHPUnit vs Mockery

Our codebase demonstrates two approaches to mocking: PHPUnit's built-in mocking system and the Mockery library. Compare these two test classes:

- `KinesisEventBusDecoratorTest.php` uses PHPUnit's `createMock()`
- `MockeryKinesisEventBusDecoratorTest.php` uses Mockery's fluent interface

#### PHPUnit Style:
```php
$this->event = $this->createMock(TWEvent::class);
$this->event->method('getName')->willReturn('test.event');
```

#### Mockery Style:
```php
$this->event = Mockery::mock(TWEvent::class);
$this->event->shouldReceive('getName')->andReturn('test.event');
```

Both achieve the same goal but with different syntax. Mockery offers a more expressive, fluent interface that some developers find more readable.

### Handling Mockery Cleanup

When using Mockery, it's crucial to clean up mocks after each test. Our `TestCase` base class handles this in two ways:

1. The `tearDown()` method in `MockeryKinesisEventBusDecoratorTest`:
```php
public function tearDown(): void
{
    Mockery::close();
    parent::tearDown();
}
```

2. The base `TestCase` ensures tests aren't marked as "risky":
```php
public function tearDown(): void
{
    $this->addToAssertionCount(1);
    parent::tearDown();
}
```

### Mocking Challenging Interfaces: The KinesisClient Case

One particularly interesting challenge in this codebase is mocking the AWS KinesisClient. The real KinesisClient implements `putRecord` through PHP's magic `__call` method, which can be problematic for mocking. Our solution demonstrates an elegant approach:

1. Create a specialized mock class (`KinesisClientMock`):
```php
class KinesisClientMock extends KinesisClient
{
    public function putRecord($args = []): Result
    {
        throw new \BadMethodCallException('putRecord is not implemented in KinesisClientMock');
    }
}
```

This class:
- Extends the real KinesisClient
- Explicitly declares the `putRecord` method
- Makes the method mockable in tests

Then in our tests, we can easily mock this method:
```php
$this->kinesis->expects($this->once())
    ->method('putRecord')
    ->with([
        'StreamName' => KinesisEventBusDecorator::STREAM_NAME,
        'Data' => json_encode($payload),
        'PartitionKey' => KinesisEventBusDecorator::PRODUCT_ID . '-' . $this->session->getCustomerId(),
    ]);
```

## Best Practices Demonstrated

1. **Arrange-Act-Assert**: Tests follow this pattern clearly:
   - Setup/Arrange in `setUp()` or test setup section
   - Act by calling the method under test
   - Assert the expected outcomes

2. **Isolation**: Each test focuses on a single behavior:
   ```php
   public function testItPassesEventToEventBus(): void
   public function testItSendsEventToKinesis(): void
   public function testItLogsErrorWhenKinesisFails(): void
   ```

3. **Clear Naming**: Test methods clearly describe the behavior being tested

4. **Error Cases**: Tests cover both happy paths and error scenarios, as shown in `testItLogsErrorWhenKinesisFails()`

By following these patterns and practices, we create maintainable, reliable tests that serve as both documentation and quality assurance for our code.

## Using Data Providers

Data providers in PHPUnit allow you to run the same test multiple times with different input data. This is particularly useful when testing various scenarios that follow the same logic but with different inputs and expected outputs.

### Basic Data Provider Structure

Here's how a data provider could be implemented to test different event scenarios in our `KinesisEventBusDecorator`:

```php
/**
 * Note: the '@dataProvider' tag tells PHPUnit to call the 'provideEventScenarios'
 * method and run the test over each item in the array.
 * 
 * @dataProvider provideEventScenarios
 */
public function testEventAttributesAreCorrectlyFormatted(
    string $eventName, 
    array $attributes, 
    array $expectedPayload
): void {
    // Arrange
    $event = $this->createMock(TWEvent::class);
    $event->method('getName')->willReturn($eventName);
    $event->method('getAttributes')->willReturn($attributes);

    $this->kinesis->expects($this->once())
        ->method('putRecord')
        ->with($this->callback(function($args) use ($expectedPayload) {
            $data = json_decode($args['Data'], true);
            return $data['tw_event'] === $expectedPayload;
        }));

    // Act
    $this->decorator->fire($event);
}

public function provideEventScenarios(): array
{
    return [
        'basic event' => [
            'event.created',
            ['id' => 1, 'status' => 'active'],
            [
                'name' => 'event.created',
                'attributes' => ['id' => 1, 'status' => 'active']
            ]
        ],
        'event with nested attributes' => [
            'user.profile.updated',
            [
                'user' => [
                    'id' => 123,
                    'profile' => ['name' => 'John', 'age' => 30]
                ]
            ],
            [
                'name' => 'user.profile.updated',
                'attributes' => [
                    'user' => [
                        'id' => 123,
                        'profile' => ['name' => 'John', 'age' => 30]
                    ]
                ]
            ]
        ],
        'event with empty attributes' => [
            'cache.cleared',
            [],
            [
                'name' => 'cache.cleared',
                'attributes' => []
            ]
        ]
    ];
}
```

### Benefits of Data Providers

1. **Reduced Code Duplication**: Instead of writing separate test methods for each scenario, you write one test method that handles multiple cases.

2. **Better Maintainability**: When you need to change how the test works, you only need to update one method instead of multiple similar methods.

3. **Clear Documentation**: The data provider serves as documentation of the different scenarios your code handles.

4. **Easy to Extend**: Adding new test cases is as simple as adding new entries to the data provider array.

### Best Practices for Data Providers

1. **Meaningful Keys**: Use descriptive array keys in your data provider to make it clear what each test case represents:
   ```php
   'event with nested attributes' => [
       // test data...
   ]
   ```

2. **Focused Test Cases**: Each test case should test a specific variation of the input data.

3. **Readable Structure**: Structure your data provider to make it easy to see the relationship between inputs and expected outputs.

4. **Independent Cases**: Each test case should be independent and not rely on the state of other test cases.

### When to Use Data Providers

Data providers are particularly useful when:
- Testing input validation
- Testing data transformations
- Testing boundary conditions
- Testing different formatting scenarios
- Testing various combinations of valid/invalid inputs

In our example above, we're testing how different event structures are formatted before being sent to Kinesis. This approach makes it easy to verify that our event bus handles various event scenarios correctly while keeping the test code DRY and maintainable.
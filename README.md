# Azure Service Bus Client

## Setting up

All three properties are required.

```
$config = [
	'baseUri' => 'https://example-endpoint.servicebus.windows.net/',
	'sasKeyName' => 'QueueAccessKeyName',
	'sasKeyValue' => 'AccessKeyValue',
];

$serviceBusClient = new CloudSystems\AzureServiceBusClient\ServiceBusClient($config);
```

## Send a message

```
$data = [
	'key' => 'value',
	'foo' => 'bar',
];

$json = json_encode($data);

$result = $serviceBusClient->sendMessage('exampleQueue', $json);

echo $result ? "OK" : "Error";
```

## Peek

```
$result = $serviceBusClient->peek('exampleQueue');

print_r($result);

/*
$result:
Array
(
    [body] => Array
        (
            [key] => value
            [foo] => bar
        )

    [location] => https://example-endpoint.servicebus.windows.net/exampleQueue/messages/1234/51ba96d0-bce2-44a7-b21d-236a8bf0234e
    [properties] => Array
        (
            [DeliveryCount] => 1
            [EnqueuedSequenceNumber] => 0
            [EnqueuedTimeUtc] => Mon, 29 Mar 2021 10:41:53 GMT
            [LockToken] => b130ffc9-a58e-4ce5-b642-8f4e28e2c9ca
            [LockedUntilUtc] => Mon, 29 Mar 2021 10:42:27 GMT
            [MessageId] => 9f32405f549dabb5533bb875ad73e809
            [SequenceNumber] => 1234
            [State] => Active
            [TimeToLive] => 1209600
        )
)
*/
```

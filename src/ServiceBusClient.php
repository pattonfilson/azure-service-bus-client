<?php

namespace CloudSystems\AzureServiceBusClient;

use CloudSystems\AzureServiceBusClient\Exceptions\BaseException;


class ServiceBusClient
{


	use MakesHttpRequests;


	private $config;
	private $timeout;
	private $baseUri;
	private $sasKeyName;
	private $sasKeyValue;


	public function __construct(array $config = [])
	{
		return $this->init($config);
	}


	public function init(array $config)
	{
		$this->config = $config;

		$this->baseUri = $config['baseUri'] ?? null;
		if (empty($this->baseUri)) {
			throw new BaseException("No baseUri provided.");
		}

		$this->sasKeyName = $config['sasKeyName'] ?? null;
		if (empty($this->sasKeyName)) {
			throw new BaseException("No sasKeyName provided.");
		}

		$this->sasKeyValue = $config['sasKeyValue'] ?? null;
		if (empty($this->sasKeyValue)) {
			throw new BaseException("No sasKeyValue provided.");
		}

		$this->timeout = $config['timeout'] ?? 4;

		$this->initGuzzle();

		return $this;
	}


	/**
	 * Generate a SAS token (for authentication) for the given URI.
	 *
	 * @param string $uri URI for the token. This is appended to the configured baseUri value.
	 * @return string The header string. Starts with 'SharedAccessSignature sig=[...]'
	 *
	 */
	public function getAuthHeader($uri)
	{
		return $this->generateSasToken($this->baseUri . $uri, $this->sasKeyName, $this->sasKeyValue);
	}


	/**
	 * Set a new timeout value (in seconds) for receive requests.
	 *
	 * @param int $timeout Timeout in seconds to wait for receiving messages.
	 * @return this
	 *
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
		return $this;
	}


	/**
	 * Send a message to the specified queue.
	 *
	 * @param string $queueName Name of queue to send message to.
	 * @param string $body Encoded body to send. If sending JSON, this should be the result of `json_encode()` on your payload.
	 * @return bool
	 *
	 */
	public function sendMessage($queueName, $body)
	{
		$uri = sprintf('%s/messages', $queueName);
		$options = [ 'body' => $body ];
		$headers = [ 'Authorization' => $this->getAuthHeader($uri) ];

		$response = $this->post($uri, $options, $headers)->asResponse();
		return $response->getStatusCode() == 201 ? true : false;
	}


	/**
	 * Peek at the queue.
	 *
	 * By default it will return an array with the following keys:
	 * 	- body: The message itself.
	 * 	- location: URL of the message (for sending updates)
	 * 	- properties: array of broker properties
	 *
	 * @param string $queueName Name of queue to peek.
	 * @param bool $returnResponse Use `true` to return the raw HTTP response instead of the formatted results.
	 * @return mixed
	 *
	 */
	public function peek($queueName, $returnResponse = false)
	{
		$uri = sprintf('%s/messages/head?timeout=%d', $queueName, $this->timeout);
		$headers = [ 'Authorization' => $this->getAuthHeader($uri) ];
		$response = $this->post($uri, [], $headers)->asResponse();

		if ($returnResponse) return $response;

		if ($response->getStatusCode() == 204) return null;

		$body = (string) $response->getBody();
		$location = $response->getHeader('Location')[0];
		$brokerString = $response->getHeader('BrokerProperties')[0];
		$brokerData = json_decode($brokerString, true) ?: $brokerString;

		return [
			'body' => json_decode($body, true) ?: $body,
			'location' => $location,
			'properties' => $brokerData,
		];
	}


	/**
	 * Read a message from the queue and delete it (Destructive read).
	 *
	 * Return value is the same as @peek().
	 *
	 * @param string $queueName Name of queue to read+delete message from.
	 * @param bool $returnResponse Return the raw HTTP response instead of formatted data.
	 * @see peek();
	 *
	 */
	public function destructiveRead($queueName, $returnResponse = false)
	{
		$uri = sprintf('%s/messages/head', $queueName);
		$headers = [ 'Authorization' => $this->getAuthHeader($uri) ];
		$response = $this->delete($uri, [], $headers)->asResponse();

		if ($returnResponse) return $response;

		if ($response->getStatusCode() == 204) return null;

		$body = (string) $response->getBody();
		$location = $response->getHeader('Location')[0];
		$brokerString = $response->getHeader('BrokerProperties')[0];
		$brokerData = json_decode($brokerString, true) ?: $brokerString;

		return [
			'body' => json_decode($body, true) ?: $body,
			'location' => $location,
			'properties' => $brokerData,
		];
	}


	/**
	 * Unlock a message in the specified queue, for processing by other receivers.
	 *
	 * Message ID and Lock Token are returned in the 'properties' key of a call to `peek()`:
	 * 	['properties']['MessageId'] and ['properties']['LockToken'].
	 *
	 * @param string $queueName Name of queue
	 * @param string $messageId ID of message (retrieved by peek())
	 * @param string $lockToken Lock Token value from message
	 * @return bool
	 *
	 */
	public function unlockMessage($queueName, $messageId, $lockToken)
	{
		$uri = sprintf('%s/messages/%s/%s', $queueName, $messageId, $lockToken);
		$headers = [ 'Authorization' => $this->getAuthHeader($uri) ];
		$response = $this->put($uri, [], $headers)->asResponse();
		return $response->getStatusCode() == 200 ? true : false;
	}


	/**
	 * Delete a message from the specified queue.
	 *
	 * @param string $messageId ID of message (retrieved by peek())
	 * @param string $lockToken Lock Token value from message
	 * @return bool
	 *
	 */
	public function deleteMessage($queueName, $messageId, $lockToken)
	{
		$uri = sprintf('%s/messages/%s/%s', $queueName, $messageId, $lockToken);
		$headers = [ 'Authorization' => $this->getAuthHeader($uri) ];
		$response = $this->delete($uri, [], $headers)->asResponse();
		return $response->getStatusCode() == 200 ? true : false;
	}


}

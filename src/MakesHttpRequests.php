<?php

namespace CloudSystems\AzureServiceBusClient;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;

use CloudSystems\AzureServiceBusClient\Exceptions\ApiException;
use CloudSystems\AzureServiceBusClient\Exceptions\NotFoundException;
use CloudSystems\AzureServiceBusClient\Exceptions\TimeoutException;
use CloudSystems\AzureServiceBusClient\Exceptions\TooManyRequestsException;
use CloudSystems\AzureServiceBusClient\Exceptions\UnauthorisedException;
use CloudSystems\AzureServiceBusClient\Exceptions\ValidationException;


trait MakesHttpRequests
{


	private $lastResponse;


	public function initGuzzle(array $config = [])
	{
		$guzzleDefaults = [
			'base_uri' => $this->baseUri,
			'timeout' => 10,
			'allow_redirects' => false,
			'headers' => [
			  'Content-Type' => 'application/json',
			  // 'Content-Type' => 'application/atom+xml;type=entry;charset=utf-8',
			  'Accept' => 'application/json',
			],
		];

		$guzzleConfig = array_merge($guzzleDefaults, $config);

		$this->guzzle = new HttpClient($guzzleConfig);
		return $this;
	}


	public function generateSasToken($uri, $sasKeyName, $sasKeyValue)
	{
		$expires = time() + 3600;
		$targetUri = strtolower(rawurlencode(strtolower($uri)));
		$toSign = $targetUri . "\n" . $expires;
		$sig = rawurlencode(base64_encode(hash_hmac('sha256', $toSign, $sasKeyValue, true)));
		$sr = $targetUri;

		$format = 'SharedAccessSignature sig=%s&se=%d&skn=%s&sr=%s';
		return sprintf($format, $sig, $expires, $sasKeyName, $sr);
	}


	/**
	 * Make a GET request and return the response.
	 *
	 * @param  string $uri
	 * @return mixed
	 *
	 */
	private function get($uri, $params = [], array $headers = [])
	{
		return $this->request('GET', $uri, $params, $headers);
	}


	/**
	 * Make a POST request and return the response.
	 *
	 * @return mixed
	 *
	 */
	private function post(string $uri, array $payload = [], array $headers = [])
	{
		return $this->request('POST', $uri, $payload, $headers);
	}


	/**
	 * Make a PUT request and return the response.
	 *
	 * @return mixed
	 *
	 */
	private function put(string $uri, array $payload = [], array $headers = [])
	{
		return $this->request('PUT', $uri, $payload, $headers);
	}


	/**
	 * Make a PATCH request and return the response.
	 *
	 * @return mixed
	 *
	 */
	private function patch(string $uri, array $payload = [], array $headers = [])
	{
		return $this->request('PATCH', $uri, $payload, $headers);
	}


	/**
	 * Make a DELETE request and return the response.
	 *
	 * @return mixed
	 *
	 */
	private function delete(string $uri, array $payload = [], array $headers = [])
	{
		return $this->request('DELETE', $uri, $payload, $headers);
	}


	public function asArray()
	{
		$responseBody = (string) $this->lastResponse->getBody();
		return json_decode($responseBody, true) ?: false;
	}


	public function asResponse()
	{
		return $this->lastResponse;
	}


	/**
	 * Make request and return the response.
	 *
	 * @return mixed
	 *
	 */
	private function request(string $verb, string $uri, array $params = [], array $headers = [])
	{
		$options = [];

		if ( ! empty($params)) {
			switch (true) {
				case $verb === 'GET':
					$options['query'] = $params;
					break;
				case array_key_exists('body', $params):
					$options['body'] = $params['body'];
					break;
				default:
					$options['form_params'] = $params;
			}
		}

		if ( ! empty($headers)) {
			$options['headers'] = $headers;
		}

		try {
			$response = $this->guzzle->request($verb, $uri, $options);
		} catch (ClientException $e) {
			return $this->handleRequestError($e->getResponse());
		}

		$this->lastResponse = $response;
		return $this;
	}


	/**
	 * @param  \Psr\Http\Message\ResponseInterface $response
	 * @return void
	 *
	 */
	private function handleRequestError(ResponseInterface $response)
	{
		if ($response->getStatusCode() == 401) {
			throw new UnauthorisedException();
		}

		if ($response->getStatusCode() == 403) {
			throw new UnauthorisedException();
		}

		if ($response->getStatusCode() == 404) {
			throw new UnauthorisedException();
		}

		if ($response->getStatusCode() == 422) {
			throw new ValidationException(json_decode((string) $response->getBody(), true));
		}

		if ($response->getStatusCode() == 429) {
			throw new TooManyRequestsException();
		}

		throw new ApiException((string) $response->getBody());
	}


	/**
	 * Retry the callback or fail after x seconds.
	 *
	 * @return mixed
	 *
	 */
	public function retry(int $timeout, Callable $callback)
	{
		$start = time();

		beginning:

		if ($output = $callback()) {
			return $output;
		}

		if (time() - $start < $timeout) {
			sleep(5);

			goto beginning;
		}

		throw new TimeoutException($output);
	}


}

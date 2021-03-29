<?php

namespace CloudSystems\AzureServiceBusClient\Exceptions;


class TooManyRequestsException extends BaseException
{


	/**
	 * Create a new exception instance.
	 *
	 * @return void
	 *
	 */
	public function __construct()
	{
		parent::__construct('Too many requests. You have exceeded the rate limit.');
	}


}

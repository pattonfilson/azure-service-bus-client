<?php

namespace CloudSystems\AzureServiceBusClient\Exceptions;


class UnauthorisedException extends BaseException
{


    /**
     * Create a new exception instance.
     *
     * @return void
     *
     */
    public function __construct()
    {
        parent::__construct('Not authorised. Check your credentials?');
    }


}

<?php

declare(strict_types=1);

namespace InvoicePorter\Ezpay\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use InvoicePorter\Ezpay\EzpayConfig;

final class HttpClientFactory
{
    public static function create(EzpayConfig $config, ?HandlerStack $handler = null): Client
    {
        $options = [
            'base_uri' => $config->environment->baseUrl(),
            'timeout' => $config->timeoutSeconds,
            'connect_timeout' => $config->connectTimeoutSeconds,
            'http_errors' => false,
        ];

        if ($handler !== null) {
            $options['handler'] = $handler;
        }

        return new Client($options);
    }
}

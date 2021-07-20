<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Message\AbstractRequest as BaseAbstractRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Abstract Request
 */
abstract class AbstractRequest extends BaseAbstractRequest
{
    /**
     * @var string  Live endpoint URL
     */
    protected $endpointLive = 'https://api.gocardless.com';
    /**
     * @var string  Sandbox/testing endpoint URL
     */
    protected $endpointSandbox = 'https://api-sandbox.gocardless.com';

    /**
     * @var string  Action portion of the URL
     */
    protected $action = '/redirect_flows';

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->endpointSandbox : $this->endpointLive;
    }

    public function getAccessToken()
    {
        return $this->getParameter('access_token');
    }

    public function setAccessToken($value)
    {
        return $this->setParameter('access_token', $value);
    }

    /**
     * Send a HTTP request to the gateway
     *
     * @param array $data
     * @param string $method
     *
     * @return ResponseInterface
     */
    protected function sendRequest($data, $method = 'POST')
    {
        $response = $this->httpClient->request(
            $method,
            $this->getEndpoint().$this->action,
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'GoCardless-Version' => '2015-07-06',
            ),
            json_encode($data)
        );

        return $response;
    }
}

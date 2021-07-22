<?php

namespace Omnipay\GoCardlessTests;

use Omnipay\GoCardless\RedirectFlowGateway;
use Omnipay\GoCardless\Message\PurchaseRequest;
use Omnipay\GoCardless\Message\RedirectCompleteFlowRequest;
use Omnipay\GoCardless\Message\RedirectFlowRequest;
use Omnipay\Tests\GatewayTestCase;

class RedirectFlowGatewayTest extends GatewayTestCase
{
    /**
     * @var RedirectFlowGateway
     */
    protected $gateway;

    /**
      * @var array
      */
    protected $options;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new RedirectFlowGateway(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->options = array(
            'sessionToken' => 'session_ca853718-99ea-4cfd-86fd-c533ef1d5a3b',
            'returnUrl' => 'http://localhost/success',
            'description'  => 'Just a unit test'
        );
    }

    public function testRedirectFlow()
    {
        $this->setMockHttpResponse('RedirectFlowResponse.txt');
        $response = $this->gateway->redirectFlow($this->options)->send();

        $this->assertSame('There was an error', $response->getMessage());
        $this->assertSame('RE123456', $response->getTransactionReference());
        $this->assertSame('RE123456', $response->getRedirectUrl());
    }


/*
    public function testCompleteRedirectFlow()
    {
        $request = $this->gateway->completeRedirectFlow();
        $this->assertInstanceOf(RedirectCompleteFlowRequest::class, $request);
    }

    public function testPurchase()
    {
        $request = $this->gateway->purchase();
        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }

    public function testPurchaseNoMandateId()
    {
        $parameters['mandateId'] = null;
        $request = $this->gateway->purchase();
        $this->assertInstanceOf(PurchaseRequest::class, $request);
    }
*/

}

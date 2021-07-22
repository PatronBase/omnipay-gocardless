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

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new RedirectFlowGateway(
            $this->getHttpClient(),
            $this->getHttpRequest()
        );

        $this->gateway->setRedirectFlowId('RE123456');
        $this->gateway->setSessionToken('sessionToken');
        $this->gateway->setMandateId('123456');
    }

    public function testRedirectFlow()
    {
        $request = $this->gateway->redirectFlow();
        $this->assertInstanceOf(RedirectFlowRequest::class, $request);
    }

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

}

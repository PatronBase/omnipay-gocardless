<?php

namespace Omnipay\GoCardlessTests;

use Exception;
use Omnipay\GoCardless\RedirectFlowGateway;
use Omnipay\Tests\GatewayTestCase;

class RedirectFlowGatewayTest extends GatewayTestCase
{
    /**
     * @var RedirectFlowGateway
     */
    protected $gateway;

    /**
      * @var mixed[]
      */
    protected $options;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new RedirectFlowGateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize(['accessToken' => 'IamAtestToken']);
    }

    public function testRedirectFlow()
    {
        $this->setMockHttpResponse('RedirectFlowResponseSuccess.txt');
        $options = [
            'card' => $this->getValidCard(),
            'description'  => 'Wine boxes',
            'returnUrl' => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
        ];

        $response = $this->gateway->redirectFlow($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('RE123', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertSame('http://pay.gocardless.com/flow/RE123', $response->getRedirectUrl());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
    }

    public function testRedirectFlowError()
    {
        $this->setMockHttpResponse('RedirectFlowResponseError.txt');
        $options = [
            'returnUrl' => 'https://example.com/pay/confirm',
            'sessionToken' => '',
        ];

        $response = $this->gateway->redirectFlow($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('Invalid document structure', $response->getMessage());
        $this->assertNull($response->getRedirectUrl());
    }

    public function testCompleteRedirectFlow()
    {
        $this->setMockHttpResponse('RedirectCompleteFlowResponseSuccess.txt');
        $options = [
            'redirectFlowId' => 'RE123',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
        ];

        $response = $this->gateway->completeRedirectFlow($options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('RE123', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertSame('https://pay.gocardless.com/flow/RE123/success', $response->getRedirectUrl());
        $this->assertSame('GET', $response->getRedirectMethod());
        $this->assertNull($response->getRedirectData());
        $this->assertSame('MD123', $response->getMandateId());
    }

    public function testCompleteRedirectFlowError()
    {
        throw new Exception('not implemented yet');
    }

    public function testPurchase()
    {
        throw new Exception('not implemented yet');
    }

    public function testPurchaseFailed()
    {
        // formed correctly, just failed
        throw new Exception('not implemented yet');
    }

    public function testPurchaseError()
    {
        // formed incorrectly
        throw new Exception('not implemented yet');
    }

    public function testPurchaseNoMandateId()
    {
        // successfully switch to redirect flow
        throw new Exception('not implemented yet');
    }

    public function testCompletePurchase()
    {
        // includes mandate, is the same as purchase
        throw new Exception('not implemented yet');
    }

    public function testCompletePurchaseNoMandateId()
    {
        // successfull run complete redirect flow and then run pruchase
        throw new Exception('not implemented yet');
    }

    public function testAcceptNotification()
    {
        // write this after the webhook notification parsing is done
        throw new Exception('not implemented yet');
    }
}

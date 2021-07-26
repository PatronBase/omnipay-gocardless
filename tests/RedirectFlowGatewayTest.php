<?php

namespace Omnipay\GoCardless;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\ServerRequest;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\GoCardless\Message\WebhookEventNotification;
use Omnipay\GoCardless\Message\WebhookNotification;
use Omnipay\GoCardless\RedirectFlowGateway;
use Omnipay\Tests\GatewayTestCase;
use ReflectionObject;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

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
        $this->setMockHttpResponse('RedirectCompleteFlowResponseError.txt');
        $options = [
            'redirectFlowId' => 'RE123',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
        ];

        $response = $this->gateway->completeRedirectFlow($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('Redirect flow incomplete', $response->getMessage());

    }

    public function testPurchase()
    {
        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');
        $options = [
            'card'         => $this->getValidCard(),
            'description'  => 'Wine boxes',
            'amount'       => 500,
            'currency'     => 'GBP',
            'returnUrl'    => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
            'links' => ['mandateId'    => 'MD123']
        ];

        $response = $this->gateway->purchase($options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
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
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch([]);

        $notifications = $request->getNotifications();
        $data = $notifications[0];
        $request = $gateway->acceptNotification(['notification' => $data]);
        $response = $request->send();

        $this->assertInstanceOf(WebhookEventNotification::class, $request);
        $this->assertInstanceOf(WebhookEventNotification::class, $response);
        $this->assertSame($request, $response);

        $this->assertSame($data, $response->getData());
        $this->assertSame('confirmed', $response->getAction());
        $this->assertSame('payment_confirmed', $response->getCode());
        $this->assertSame('gocardless', $request->getEventOrigin());
        $this->assertSame('Payment was confirmed as collected', $response->getMessage());
        $this->assertSame('EV123', $response->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_COMPLETED, $response->getTransactionStatus());
        $this->assertSame('payments', $response->getType());
    }

    public function testAcceptNotificationFailure()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch([]);

        $notifications = $request->getNotifications();
        $data = $notifications[1];
        $request = $gateway->acceptNotification(['notification' => $data]);

        $this->assertSame($data, $request->getData());
        $this->assertSame('failed', $request->getAction());
        $this->assertSame('mandate_cancelled', $request->getCode());
        $this->assertSame('bank', $request->getEventOrigin());
        $this->assertSame('Customer cancelled the mandate at their bank branch.', $request->getMessage());
        $this->assertSame('EV456', $request->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_FAILED, $request->getTransactionStatus());
        $this->assertSame('payments', $request->getType());
    }

    public function testAcceptNotificationBatch()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch([]);
        $response = $request->send();

        $events = [
            [
                'id' => 'EV123',
                'created_at' => '2014-08-03T12:00:00.000Z',
                'action' => 'confirmed',
                'resource_type' => 'payments',
                'links' => [
                    'payment' => 'PM123',
                ],
                'details' => [
                    'origin' => 'gocardless',
                    'cause' => 'payment_confirmed',
                    'description' => 'Payment was confirmed as collected',
                ],
            ],
            [
                'id' => 'EV456',
                'created_at' => '2014-08-03T12:00:00.000Z',
                'action' => 'failed',
                'resource_type' => 'payments',
                'links' => [
                    'payment' => 'PM456',
                ],
                'details' => [
                    'origin' => 'bank',
                    'cause' => 'mandate_cancelled',
                    'description' => 'Customer cancelled the mandate at their bank branch.',
                    'scheme' => 'bacs',
                    'reason_code' => 'ARUDD-1',
                ],
            ],
        ];

        $this->assertInstanceOf(WebhookNotification::class, $request);
        $this->assertInstanceOf(WebhookNotification::class, $response);
        $this->assertSame($request, $response);
        $this->assertTrue($request->hasValidSignature('123ABC456DEF'));
        $this->assertFalse($request->hasValidSignature('thewrongsecret'));
        $this->assertSame($events, $request->getNotifications());
        $this->assertSame("WB123", $request->getWebhookId());
        $this->assertSame(['events' => $events, "meta" => ["webhook_id" => "WB123"]], $request->getData());
        // @todo any other assertions?
        /*
        - match events to getNotifications [failure([]) and empty([]) will need own tests]
        - null webhook ID
        */
    }

    /**
     * Parses a saved raw request file into a new HTTP request object
     *
     * Initial file parsing adapted from TestCase::getMockHttpResponse()
     *
     * @param string $path  The request file
     *
     * @return HttpRequest  The new request
     */
    protected function setMockHttpRequest($path)
    {
        $ref = new ReflectionObject($this);
        $dir = dirname($ref->getFileName());
        // if mock file doesn't exist, check parent directory
        if (file_exists($dir.'/Mock/'.$path)) {
            $raw = file_get_contents($dir.'/Mock/'.$path);
        } elseif (file_exists($dir.'/../Mock/'.$path)) {
            $raw = file_get_contents($dir.'/../Mock/'.$path);
        } else {
            throw new Exception("Cannot open '{$path}'");
        }

        $guzzleRequest = Message::parseRequest($raw);
        // PSR-bridge requires a ServerRequestInterface
        $guzzleServerRequest = new ServerRequest(
            $guzzleRequest->getMethod(),
            $guzzleRequest->getUri(),
            $guzzleRequest->getHeaders(),
            $guzzleRequest->getBody(),
            $guzzleRequest->getProtocolVersion(),
            $_SERVER
        );

        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createRequest($guzzleServerRequest);
    }
}

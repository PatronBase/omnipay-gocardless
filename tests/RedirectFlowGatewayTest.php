<?php

namespace Omnipay\GoCardless;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\ServerRequest;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\GoCardless\Message\PurchaseResponse;
use Omnipay\GoCardless\Message\RedirectCompleteFlowRequest;
use Omnipay\GoCardless\Message\RedirectFlowRequest;
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
        $this->gateway->initialize(['accessToken' => 'IamAtestToken', 'testMode' => true]);
    }

    public function testRedirectFlow()
    {
        $this->setMockHttpResponse('RedirectFlowResponseSuccess.txt');
        $options = [
            'card'         => $this->getValidCard(),
            'description'  => 'Wine boxes',
            'returnUrl'    => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
        ];

        $response = $this->gateway->redirectFlow($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('RE123', $response->getTransactionReference());
        $this->assertSame('SESS_wSs0uGYMISxzqOBq', $response->getSessionToken());
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
        $this->assertNull($response->getSessionToken());
        $this->assertNull($response->getTransactionReference());
        $this->assertSame('Invalid document structure', $response->getMessage());
        $this->assertNull($response->getRedirectUrl());
    }

    public function testCompleteCreateCard()
    {
        $options = [
            'redirectFlowId' => 'RE123',
            'sessionToken'   => 'SESS_wSs0uGYMISxzqOBq',
        ];

        $request = $this->gateway->completeCreateCard($options);

        $this->assertInstanceOf(RedirectCompleteFlowRequest::class, $request);
    }

    public function testCompleteRedirectFlow()
    {
        $this->setMockHttpResponse('RedirectCompleteFlowResponseSuccess.txt');
        $options = [
            'redirectFlowId' => 'RE123',
            'sessionToken'   => 'SESS_wSs0uGYMISxzqOBq',
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
        $this->assertSame('MD123', $response->getCardReference());
    }

    public function testCompleteRedirectFlowError()
    {
        $this->setMockHttpResponse('RedirectCompleteFlowResponseError.txt');
        $options = [
            'redirectFlowId' => 'RE123',
            'sessionToken'   => 'SESS_wSs0uGYMISxzqOBq',
        ];

        $response = $this->gateway->completeRedirectFlow($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('Redirect flow incomplete', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getRedirectUrl());
        $this->assertNull($response->getMandateId());
        $this->assertNull($response->getCardReference());
    }

    public function testPurchase()
    {
        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');
        $options = [
            'amount'        => '1.00',
            'appFeeAmount'  => 10,
            'charge_date'   => "2014-05-19",
            'currency'      => 'GBP',
            'description'   => 'Wine boxes',
            'mandateId'     => 'MD123',
            'metadata'      => ["order_dispatch_date" => "2014-05-22"],
            'returnUrl'     => 'https://example.com/pay/confirm',
            'sessionToken'  => 'SESS_wSs0uGYMISxzqOBq',
            'transactionId' => 'WINEBOX001',
            "retry_if_possible" => false,
            "customPaymentReferencesEnabled" => false,
        ];

        $response = $this->gateway->purchase($options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isCancelled());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isError());
        $this->assertSame('PM123', $response->getTransactionReference());
        $this->assertSame('2014-05-21', $response->getChargeDate());
        $this->assertSame(100, $response->getAmount());
        $this->assertSame('GBP', $response->getCurrency());
        $this->assertNull($response->getDescription());
        $this->assertNull($response->getMessage());
        $this->assertSame('MD123', $response->getMandateId());
        $this->assertSame(["order_dispatch_date" => '2014-05-22'], $response->getMetaData());
        $this->assertSame(0, $response->getAmountRefunded());
        $this->assertSame(
            [
                "fx_currency"   => "EUR",
                "fx_amount"     => null,
                "exchange_rate" => null,
                "estimated_exchange_rate" => "1.1234567890"
            ],
            $response->getFx()
        );
        $this->assertSame('confirmed', $response->getCode());
        $this->assertSame('confirmed', $response->getStatus());
    }

    // formed correctly, just failed
    public function testPurchaseFailed()
    {
        $this->setMockHttpResponse('PurchaseResponseCancelled.txt');
        $options = [
            'amount' => '1.00',
            'charge_date' => "2014-05-19",
            'currency' => 'GBP',
            'description' => 'Wine boxes',
            'mandateId' => 'MD123',
            'metadata' => ["order_dispatch_date" => "2014-05-22"],
            'returnUrl' => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
            'transactionId' => 'WINEBOX001',
        ];

        $response = $this->gateway->purchase($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertTrue($response->isCancelled());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isError());
        $this->assertSame('PM123', $response->getTransactionReference());
        $this->assertSame('2014-05-21', $response->getChargeDate());
        $this->assertSame(100, $response->getAmount());
        $this->assertSame('GBP', $response->getCurrency());
        $this->assertSame('cancelled', $response->getStatus());
        $this->assertSame(["order_dispatch_date" => '2014-05-22'], $response->getMetaData());
        $this->assertSame(0, $response->getAmountRefunded());
        $this->assertSame(
            [
                "fx_currency" => "EUR",
                "fx_amount" => null,
                "exchange_rate" => null,
                "estimated_exchange_rate" => "1.1234567890"
            ],
            $response->getFx()
        );
        $this->assertNull($response->getDescription());
        $this->assertNull($response->getMessage());
        $this->assertNull($response->getErrors());
        $this->assertNull($response->getMandateId());
        $this->assertSame('cancelled', $response->getCode());
    }

    // formed incorrectly
    public function testPurchaseError()
    {
        $this->setMockHttpResponse('PurchaseResponseError.txt');
        $options = [
            'amount' => '1.00',
            'charge_date' => "2014-05-19",
            'currency' => 'GBP',
            'description' => 'Wine boxes',
            'mandateId' => 'MD123',
            // this is wrong, value must be a string
            'metadata' => ["order_dispatch_date" => 20140522],
            'returnUrl' => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
            'transactionId' => 'WINEBOX001',
        ];

        $response = $this->gateway->purchase($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isCancelled());
        $this->assertFalse($response->isRedirect());
        $this->assertTrue($response->isError());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getChargeDate());
        $this->assertNull($response->getAmount());
        $this->assertNull($response->getCurrency());
        $this->assertSame('One of your parameters was incorrectly typed', $response->getMessage());
        $this->assertNotNull($response->getErrors());
        $this->assertNull($response->getMandateId());
        $this->assertNull($response->getCardReference());
        $this->assertNull($response->getAmountRefunded());
        $this->assertNull($response->getFx());
        $this->assertNull($response->getMetaData());
        $this->assertSame('422', $response->getCode());
        $this->assertSame('invalid_api_usage', $response->getStatus());
    }

    public function testPurchaseNoMandateId()
    {
        $options = [
            'amount' => '1.00',
            'charge_date' => "2014-05-19",
            'currency' => 'GBP',
            'description' => 'Wine boxes',
            // 'mandateId' => 'MD123',
            'metadata' => ["order_dispatch_date" => "2014-05-22"],
            'returnUrl' => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
            'transactionId' => 'WINEBOX001',
        ];

        $request = $this->gateway->purchase($options);

        $this->assertInstanceOf(RedirectFlowRequest::class, $request);
    }

    public function testCompletePurchase()
    {
        // includes mandate, is the same as purchase
        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');
        $options = [
            'card'         => $this->getValidCard(),
            'amount'       => '1.00',
            'currency'     => 'GBP',
            'returnUrl'    => 'https://example.com/pay/confirm',
            'sessionToken' => 'SESS_wSs0uGYMISxzqOBq',
            'mandateId'    => 'MD123',
        ];

        $response = $this->gateway->completePurchase($options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isPending());
        $this->assertFalse($response->isCancelled());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isError());
        $this->assertSame('PM123', $response->getTransactionReference());
        $this->assertSame('2014-05-21', $response->getChargeDate());
        $this->assertSame(100, $response->getAmount());
        $this->assertSame('GBP', $response->getCurrency());
        $this->assertNull($response->getDescription());
        $this->assertNull($response->getMessage());
        $this->assertSame('MD123', $response->getMandateId());
        $this->assertSame(["order_dispatch_date" => '2014-05-22'], $response->getMetaData());
        $this->assertSame(0, $response->getAmountRefunded());
        $this->assertSame(
            [
                "fx_currency" => "EUR",
                "fx_amount" => null,
                "exchange_rate" => null,
                "estimated_exchange_rate" => "1.1234567890"
            ],
            $response->getFx()
        );
        $this->assertSame('confirmed', $response->getCode());
        $this->assertSame('confirmed', $response->getStatus());
    }

    public function testCompletePurchaseNoMandateId()
    {
        // successfully run complete redirect flow and then run purchase
        $this->setMockHttpResponse([
            'RedirectCompleteFlowResponseSuccess.txt',
            'PurchaseResponseSuccess.txt',
        ]);
        $options = [
            'card'           => $this->getValidCard(),
            'description'    => 'Wine boxes',
            'amount'         => 500,
            'currency'       => 'GBP',
            'returnUrl'      => 'https://example.com/pay/confirm',
            'redirectFlowId' => 'RE123',
            'sessionToken'   => 'SESS_wSs0uGYMISxzqOBq'
        ];

        $response = $this->gateway->completePurchase($options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('PM123', $response->getTransactionReference());
        $this->assertNull($response->getMessage());
    }

    public function testCompletePurchaseNoMandateIdFailedRedirectFlow()
    {
        // redirect flow throws error and then run purchase (twice to pick up extra send() call in test)
        $this->setMockHttpResponse([
            'RedirectCompleteFlowResponseFailedIncomplete.txt',
            'RedirectCompleteFlowResponseFailedIncomplete.txt',
        ]);
        $options = [
            'card'           => $this->getValidCard(),
            'description'    => 'Wine boxes',
            'amount'         => 500,
            'currency'       => 'GBP',
            'returnUrl'      => 'https://example.com/pay/confirm',
            'redirectFlowId' => 'RE123',
            'sessionToken'   => 'SESS_wSs0uGYMISxzqOBq'
        ];

        // could drop 'send' and end up with same results
        $response = $this->gateway->completePurchase($options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('Customer has not yet completed the payment pages', $response->getMessage());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getRedirectUrl());
        $this->assertNull($response->getMandateId());
    }

    public function testAcceptNotification()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch();

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
        $this->assertNull($response->getMetaData());
        $this->assertSame('payments', $response->getType());
        $this->assertSame('PM123', $response->getPaymentId());
    }

    public function testAcceptNotificationFailure()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch();

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
        $this->assertNull($request->getMetaData());
        $this->assertSame('payments', $request->getType());
        $this->assertSame('PM456', $request->getPaymentId());
    }

    public function testAcceptNotificationPending()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch();

        $notifications = $request->getNotifications();
        $data = $notifications[2];
        $request = $gateway->acceptNotification(['notification' => $data]);

        $this->assertSame($data, $request->getData());
        $this->assertSame('created', $request->getAction());
        $this->assertSame('payment_created', $request->getCode());
        $this->assertSame('api', $request->getEventOrigin());
        $this->assertSame('Payment created via the API.', $request->getMessage());
        $this->assertSame('EV789', $request->getTransactionReference());
        $this->assertSame(NotificationInterface::STATUS_PENDING, $request->getTransactionStatus());
        $this->assertEmpty($request->getMetaData());
        $this->assertSame('payments', $request->getType());
        $this->assertSame('PM789', $request->getPaymentId());
    }

    public function testAcceptNotificationNoStateChange()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch();

        $notifications = $request->getNotifications();
        $data = $notifications[3];
        $request = $gateway->acceptNotification(['notification' => $data]);

        $this->assertSame($data, $request->getData());
        $this->assertSame('surcharge_fee_debited', $request->getAction());
        $this->assertSame('payment_surcharge_fee_debited', $request->getCode());
        $this->assertSame('gocardless', $request->getEventOrigin());
        $this->assertSame('Surcharge fee confirmed as debited.', $request->getMessage());
        $this->assertSame('EV000', $request->getTransactionReference());
        $this->assertNull($request->getTransactionStatus());
        $this->assertNull($request->getMetaData());
        $this->assertSame('payments', $request->getType());
        $this->assertSame('PM000', $request->getPaymentId());
    }

    public function testAcceptNotificationPayouts()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayouts.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch();

        $notifications = $request->getNotifications();
        $data = $notifications[0];
        $request = $gateway->acceptNotification(['notification' => $data]);

        $this->assertSame($data, $request->getData());
        $this->assertSame('paid', $request->getAction());
        $this->assertNull($request->getCode());
        $this->assertNull($request->getEventOrigin());
        $this->assertNull($request->getMessage());
        $this->assertSame('EV123', $request->getTransactionReference());
        $this->assertNull($request->getTransactionStatus());
        $this->assertNull($request->getMetaData());
        $this->assertSame('payouts', $request->getType());
        $this->assertNull($request->getPaymentId());
    }

    public function testAcceptNotificationInvalid()
    {
        $data = ['invalid' => 'data provided'];

        $request = $this->gateway->acceptNotification(['notification' => $data]);

        $this->assertSame($data, $request->getData());
        $this->assertNull($request->getAction());
        $this->assertNull($request->getCode());
        $this->assertNull($request->getEventOrigin());
        $this->assertNull($request->getMessage());
        $this->assertNull($request->getTransactionReference());
        $this->assertNull($request->getTransactionStatus());
        $this->assertNull($request->getMetaData());
        $this->assertNull($request->getType());
        $this->assertNull($request->getPaymentId());
    }

    public function testAcceptNotificationBatch()
    {
        $httpRequest = $this->setMockHttpRequest('WebhookNotificationPayments.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $httpRequest);

        $request = $gateway->acceptNotificationBatch();
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
            [
                "id" => "EV789",
                "created_at" => "2014-08-03T12:00:00.000Z",
                "action" => "created",
                "resource_type" => "payments",
                "links" => [
                    "payment" => "PM789",
                ],
                "details" => [
                    "origin" => "api",
                    "cause" => "payment_created",
                    "description" => "Payment created via the API.",
                ],
                "metadata" => [],
            ],
            [
                "id" => "EV000",
                "created_at" => "2014-08-03T12:00:00.000Z",
                "action" => "surcharge_fee_debited",
                "resource_type" => "payments",
                "links" => [
                    "payment" => "PM000",
                ],
                "details" => [
                    "origin" => "gocardless",
                    "cause" => "payment_surcharge_fee_debited",
                    "description" => "Surcharge fee confirmed as debited.",
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
    }

    public function testAcceptNotificationBatchError()
    {
        $this->setMockHttpRequest('WebhookNotificationPaymentsError.txt');
        $gateway = new RedirectFlowGateway($this->getHttpClient(), $this->getHttpRequest());

        $request = $gateway->acceptNotificationBatch();

        $this->assertSame([], $request->getNotifications());
        $this->assertNull($request->getWebhookId());
    }

    public function testFetchPurchase()
    {
        $this->setMockHttpResponse('PurchaseResponseSuccess.txt');

        $request = $this->gateway->fetchPurchase(['paymentId' => 'PM123']);

        $this->assertSame('PM123', $request->getPaymentId());
        $this->assertNull($request->getData());

        $response = $request->send();

        $this->assertInstanceOf(PurchaseResponse::class, $response);
    }

    public function testFetchEvent()
    {
        $this->setMockHttpResponse('FetchEventSuccess.txt');

        $request = $this->gateway->fetchEvent(['eventId' => 'EV123']);

        $this->assertSame('EV123', $request->getEventId());
        $this->assertNull($request->getData());

        $response = $request->send();

        $this->assertInstanceOf(WebhookEventNotification::class, $response);
        $this->assertSame([], $response->getMetaData());
    }

    /**
     * Overrides the GatewayTestCase version due to the auto-processing
     */
    public function testSupportsCompletePurchase()
    {
        $this->assertTrue($this->gateway->supportsCompletePurchase());
    }

    /**
     * Overrides the GatewayTestCase version due to the auto-processing
     * @doesNotPerformAssertions
     */
    public function testCompletePurchaseParameters()
    {
        // empty
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

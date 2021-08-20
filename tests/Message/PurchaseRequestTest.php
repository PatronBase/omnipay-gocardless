<?php

namespace Omnipay\GoCardless\Message;

use ReflectionClass;
use Money\Currency;
use Money\Money;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    /** @var PurchaseRequest */
    private $request;

    /** @var mixed[]  Data to initialize the request with */
    private $options;

    public function setUp()
    {
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->options = array(
            'amount'        => '5.43',
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
        );
        $this->request->initialize($this->options);
    }

    public function testAccessors()
    {
        $this->assertSame('MD123', $this->request->getMandateId());
        $this->assertSame('MD123', $this->request->getCardReference());
        $this->assertNull($this->request->getAppFeeAmount());
        $this->assertNull($this->request->getAppFeeAmountInteger());
        $this->assertSame('2014-05-19', $this->request->getChargeDate());
        $this->assertSame(["order_dispatch_date" => "2014-05-22"], $this->request->getMetaData());
        $this->assertFalse($this->request->getRetryIfPossible());
        $this->assertFalse($this->request->getCustomPaymentReferencesEnabled());
        // @todo all permutations
        $this->assertNull($this->request->getTransactionId());
    }

    public function testCardReferenceInsteadOfMandateId()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('mandateId' => null, 'cardReference' => 'MD123')
        );
        $this->request->initialize($options);

        $this->assertSame('MD123', $this->request->getMandateId());
        $this->assertSame('MD123', $this->request->getCardReference());
    }

    public function testAmounts()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('amount' => '1.00', 'appFeeAmount' => '0.10')
        );
        $this->request->initialize($options);

        $this->assertSame('1.00', $this->request->getAmount());
        $this->assertSame(100, $this->request->getAmountInteger());
        $this->assertSame('0.10', $this->request->getAppFeeAmount());
        $this->assertSame(10, $this->request->getAppFeeAmountInteger());
    }

    public function testIntAmounts()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('amount' => 100, 'appFeeAmount' => 10)
        );
        $this->request->initialize($options);

        $this->assertSame('100.00', $this->request->getAmount());
        $this->assertSame(10000, $this->request->getAmountInteger());
        $this->assertSame('10.00', $this->request->getAppFeeAmount());
        $this->assertSame(1000, $this->request->getAppFeeAmountInteger());
    }

    /**
     * This test is slightly different as we need to expose the protected method
     */
    public function testGetAppFeeMoney()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('amount' => null)
        );
        $this->request->initialize($options);

        $class = new ReflectionClass(get_class($this->request));
        $method = $class->getMethod('getAppFeeMoney');
        $method->setAccessible(true);

        $currency = new Currency($this->request->getCurrency());

        $this->assertNull($method->invoke($this->request, null));
        $this->assertSame(
            '{"amount":"100","currency":"GBP"}',
            json_encode($method->invoke($this->request, new Money(100, $currency)))
        );
        $this->assertSame(
            '{"amount":"200","currency":"GBP"}',
            json_encode($method->invoke($this->request, 200))
        );
        $this->assertSame(
            '{"amount":"300","currency":"GBP"}',
            json_encode($method->invoke($this->request, '3.00'))
        );
    }

    public function testGetAppFeeMoneyPrecisionError()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('amount' => null)
        );
        $this->request->initialize($options);

        $class = new ReflectionClass(get_class($this->request));
        $method = $class->getMethod('getAppFeeMoney');
        $method->setAccessible(true);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Amount precision is too high for currency');
        $method->invoke($this->request, '3.005');
    }

    public function testGetAppFeeMoneyNegative()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('amount' => null)
        );
        $this->request->initialize($options);

        $class = new ReflectionClass(get_class($this->request));
        $method = $class->getMethod('getAppFeeMoney');
        $method->setAccessible(true);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('A negative amount is not allowed.');
        $method->invoke($this->request, '-1.00');
    }

    public function testGetAppFeeMoneyZero()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('amount' => null)
        );
        $this->request->initialize($options);

        $class = new ReflectionClass(get_class($this->request));
        $method = $class->getMethod('getAppFeeMoney');
        $method->setAccessible(true);
        $property = $class->getProperty('zeroAmountAllowed');
        $property->setAccessible(true);

        $property->setValue($this->request, false);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('A zero amount is not allowed.');
        $method->invoke($this->request, '0.00');
    }

    public function testGetData()
    {
        // override some data
        $options = array_merge(
            $this->options,
            array('customPaymentReferencesEnabled' => true, 'appFeeAmount' => '0.10', 'retry_if_possible' => true)
        );
        $this->request->initialize($options);
        $data = $this->request->getData();

        $this->assertSame(543, $data['amount']);
        $this->assertSame('GBP', $data['currency']);
        $this->assertSame(['mandate' => 'MD123'], $data['links']);
        // Optional values
        $this->assertSame(10, $data['app_fee']);
        $this->assertSame('2014-05-19', $data['charge_date']);
        $this->assertSame('Wine boxes', $data['description']);
        $this->assertSame(['order_dispatch_date' => '2014-05-22'], $data['metadata']);
        $this->assertSame('WINEBOX001', $data['reference']);
        $this->assertTrue($data['retry_if_possible']);
    }
}

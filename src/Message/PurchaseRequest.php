<?php

namespace Omnipay\GoCardless\Message;

use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\Number;
use Money\Parser\DecimalMoneyParser;
use Omnipay\Common\Exception\InvalidRequestException;

class PurchaseRequest extends AbstractRequest
{
    protected $action = '/payments';

    public function getMandateId()
    {
        return $this->getParameter('mandateId');
    }

    public function setMandateId($value)
    {
        return $this->setParameter('mandateId', $value);
    }

    /**
     * Treat mandate as a 'card' to support createCard pattern
     */
    public function getCardReference()
    {
        return $this->getMandateId();
    }

    /**
     * Treat mandate as a 'card' to support createCard pattern
     */
    public function setCardReference($value)
    {
        return $this->setMandateId($value);
    }

    public function getAppFeeAmount()
    {
        $money = $this->getAppFeeMoney();

        if ($money !== null) {
            $moneyFormatter = new DecimalMoneyFormatter($this->getCurrencies());

            return $moneyFormatter->format($money);
        }
    }

    public function getAppFeeAmountInteger()
    {
        $money = $this->getAppFeeMoney();

        if ($money !== null) {
            return (int) $money->getAmount();
        }
    }

    /**
     * {@see \Omnipay\Common\Message\AbstractRequest::getMoney() is private not protected, so copy-pasted and tweaked
     */
    protected function getAppFeeMoney($amount = null)
    {
        $currencyCode = $this->getCurrency() ?: 'USD';
        $currency = new Currency($currencyCode);

        $amount = $amount !== null ? $amount : $this->getParameter('appFeeAmount');

        if ($amount === null) {
            return null;
        } elseif ($amount instanceof Money) {
            $money = $amount;
        } elseif (is_integer($amount)) {
            $money = new Money($amount, $currency);
        } else {
            $moneyParser = new DecimalMoneyParser($this->getCurrencies());

            $number = Number::fromString($amount);

            // Check for rounding that may occur if too many significant decimal digits are supplied.
            $decimal_count = strlen($number->getFractionalPart());
            $subunit = $this->getCurrencies()->subunitFor($currency);
            if ($decimal_count > $subunit) {
                throw new InvalidRequestException('Amount precision is too high for currency.');
            }

            $money = $moneyParser->parse((string) $number, $currency);
        }

        // Check for a negative amount.
        if (!$this->negativeAmountAllowed && $money->isNegative()) {
            throw new InvalidRequestException('A negative amount is not allowed.');
        }

        // Check for a zero amount.
        if (!$this->zeroAmountAllowed && $money->isZero()) {
            throw new InvalidRequestException('A zero amount is not allowed.');
        }

        return $money;
    }

    public function setAppFeeAmount($value)
    {
        return $this->setParameter('appFeeAmount', $value !== null ? (string) $value : null);
    }

    public function getChargeDate()
    {
        return $this->getParameter('chargeDate');
    }

    /**
     * @param string $value  Format "YYYY-MM-DD"
     */
    public function setChargeDate($value)
    {
        return $this->setParameter('chargeDate', $value);
    }

    public function getMetaData()
    {
        return $this->getParameter('metadata');
    }

    /**
     * Meta data parameter is a key-value store
     *
     * Up to 3 keys are permitted, with key names up to 50 characters and values up to 500 characters
     *
     * @todo validate input and parse into a valid format
     */
    public function setMetaData($value)
    {
        return $this->setParameter('metadata', $value);
    }

    public function getRetryIfPossible()
    {
        return $this->getParameter('retryIfPossible');
    }

    public function setRetryIfPossible($value)
    {
        return $this->setParameter('retryIfPossible', $value);
    }

    public function getCustomPaymentReferencesEnabled()
    {
        return $this->getParameter('customPaymentReferencesEnabled');
    }

    public function setCustomPaymentReferencesEnabled($value)
    {
        return $this->setParameter('customPaymentReferencesEnabled', $value);
    }

    /**
     * Override to ensure able to be used, and is a string
     *
     * Ranges from 10-140 characters depending on the scheme (if enabled)
     */
    public function getTransactionId()
    {
        if (!$this->getCustomPaymentReferencesEnabled()) {
            return null;
        }
        $id = parent::getTransactionId();
        return $id === null ? null : $id;
    }

    public function getData()
    {
        $this->validate('amount', 'currency', 'mandateId');

        // Required values
        $data = [
            'amount' => $this->getAmountInteger(),
            'currency' => $this->getCurrency(),
            'links' => ['mandate' => $this->getMandateId()],
        ];

        // Optional values
        $data += array_filter([
            'app_fee' => $this->getAppFeeAmountInteger(),
            'charge_date' => $this->getChargeDate(),
            'description' => $this->getDescription(),
            'metadata' => $this->getMetaData(),
            'reference' => $this->getTransactionId(),
            'retry_if_possible' => $this->getRetryIfPossible(),
        ]);

        return $data;
    }

    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return PurchaseResponse
     */
    public function sendData($data)
    {
        $response = $this->sendRequest(['payments' => $data]);

        return $this->response = new PurchaseResponse($this, json_decode($response->getBody()->getContents(), true));
    }
}

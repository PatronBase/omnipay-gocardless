<?php

namespace Omnipay\GoCardless\Message;

/**
 * Fetch the details of a specific payment from GoCardless
 *
 * @see https://developer.gocardless.com/api-reference/#payments-get-a-single-payment
 */
class FetchPurchaseRequest extends AbstractRequest
{
    public function getPaymentId()
    {
        return $this->getParameter('paymentId');
    }

    public function setPaymentId($value)
    {
        return $this->setParameter('paymentId', $value);
    }

    public function getData()
    {
        $this->validate('paymentId');
        $this->action = '/payments/'.$this->getPaymentId();
        return null;
    }

    public function sendData($data)
    {
        $response = $this->sendRequest($data, 'GET');

        return $this->response = new PurchaseResponse($this, json_decode($response->getBody()->getContents(), true));
    }
}

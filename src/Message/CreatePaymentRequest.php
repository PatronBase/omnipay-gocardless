<?php

namespace Omnipay\GoCardless\Message;

/**
 * @method PaymentResponse send()
 */
class CreatePaymentRequest extends AbstractRequest
{
    public function getData()
    {
        $this->validate('amount', 'currency');

        // Remove null values
        $data = array_filter(
            [
                'amount' => $this->getAmountInteger(),
                'description' => $this->getPaymentDescription(),
                'app_fee' => $this->getServiceFeeAmount(),
                'metadata' => $this->getPaymentMetaData(),
                'charge_date' => $this->getPaymentDate(),
                'currency' => $this->getCurrency(),
                'reference' => $this->getReference(),
            ],
            function ($value) {
                return !empty($value);
            }
        );
        if ($this->getMandateReference()) {
            $data['links'] = ['mandate' => $this->getMandateReference()];
        }

        return ['params' => $data];
    }

    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return PaymentResponse
     */
    public function sendData($data)
    {
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getEndpoint().'/redirect_flows',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'GoCardless-Version' => '2015-07-06',
            ],
            json_encode($data)
        );

        return $this->response = new PaymentResponse($this, json_decode($httpResponse->getBody()->getContents(), true));
    }
}

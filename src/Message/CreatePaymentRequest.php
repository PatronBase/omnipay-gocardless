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
      //  'amount' => $this->getAmountInteger(),
          'app_fee'              => $this->getServiceFeeAmount(),
          'charge_date'          => $this->getPaymentDate(),
      //  'currency' => $this->getCurrency(),
          'description'          => $this->getPaymentDescription(),
          'metadata'             => $this->getPaymentMetaData(),
          'reference'            => $this->getReference(),
          'session_token'        => $this->getSessionToken(),
          'success_redirect_url' => $this->getReturnUrl(),
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
      json_encode(array($this->envelope_key => (object)$data['params']))
    );

    return $this->response = new PaymentResponse($this, json_decode($httpResponse->getBody()->getContents(), true));
  }
}

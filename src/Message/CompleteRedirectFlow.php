<?php

namespace Omnipay\GoCardless\Message;

/**
 * GoCardless Complete Redirect Flow
 */
class CompleteRedirectFlow extends AbstractRequest
{

  public function getData()
  {
    $data = [
      'mandateId' => $this->getMandateReference(),
      'params' => [
          'session_token' => $this->getSessionId(),
      ],
    ];

    return $data;
  }

  /**
 * Send the request with specified data
 *
 * @param  mixed $data The data to send
 *
 * @return CompleteRedirectFlow
 */
  public function sendData($data)
  {
    $httpResponse = $this->httpClient->request(
        'POST',
        $this->getEndpoint(). '/' .$data['mandateId']. '/actions/complete',
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

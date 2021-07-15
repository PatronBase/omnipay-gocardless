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
      'redirectFlowId' => $this->getRedirectFlowId(),
      'params' => [
          'session_token' => $this->getSessionToken(),
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
    var_dump($data);
    $httpResponse = $this->httpClient->request(
        'POST',
        $this->getEndpoint(). '/redirect_flows/' .$data['redirectFlowId']. '/actions/complete',
        [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->getAccessToken(),
            'GoCardless-Version' => '2015-07-06',
        ],
      json_encode(array($this->data_envelope => (object)$data['params']))
    );

    return $this->response = new PaymentResponse($this, json_decode($httpResponse->getBody()->getContents(), true));
  }

}

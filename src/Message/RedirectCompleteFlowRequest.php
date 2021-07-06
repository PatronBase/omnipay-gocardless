<?php

namespace Omnipay\GoCardless\Message;

/**
 * GoCardless Complete Redirect Flow
 */
class RedirectCompleteFlowRequest extends AbstractRequest
{
    public function getRedirectFlowId()
    {
        return $this->getParameter('redirectFlowId');
    }

    public function setRedirectFlowId($value)
    {
        return $this->setParameter('redirectFlowId', $value);
    }

    public function getSessionToken()
    {
        return $this->getParameter('sessionToken');
    }

    public function setSessionToken($value)
    {
        return $this->setParameter('sessionToken', $value);
    }

    public function getData()
    {
        $this->validate('redirectFlowId', 'sessionToken');
        $this->action = '/redirect_flows/'.$this->getRedirectFlowId().'/actions/complete';
        return ['session_token' => $this->getSessionToken()];
    }

    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return RedirectCompleteFlowResponse
     */
    public function sendData($data)
    {
        $response = $this->sendRequest(['data' => $data]);

        return $this->response = new RedirectCompleteFlowResponse(
            $this,
            json_decode($response->getBody()->getContents(), true)
        );
    }
}

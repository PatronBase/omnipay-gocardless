<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * GoCardless Redirect Complete Flow Response
 */
class RedirectCompleteFlowResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        return !isset($this->data['error']) && isset($this->data['redirect_flows']);
    }

    public function isRedirect()
    {
        return false;
    }

    public function getTransactionReference()
    {
        if ($this->isSuccessful()) {
            return $this->data['redirect_flows']['id'];
        }
    }

    public function getMessage()
    {
        if (!$this->isSuccessful()) {
            return $this->data['error']['message'];
        }
    }

    public function getRedirectUrl()
    {
        if ($this->isSuccessful()) {
            return $this->data['redirect_flows']['confirmation_url'];
        }
    }

    public function getRedirectMethod()
    {
        return 'GET';
    }

    public function getRedirectData()
    {
        return null;
    }

    public function getMandateId()
    {
        if ($this->isSuccessful()) {
            return $this->data['redirect_flows']['links']['mandate'];
        }
    }
}

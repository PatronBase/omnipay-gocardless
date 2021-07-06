<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RedirectResponseInterface;

/**
 * GoCardless Redirect Flow Response
 */
class RedirectFlowResponse extends AbstractResponse implements RedirectResponseInterface
{
    public function isSuccessful()
    {
        return false;
    }

    public function isRedirect()
    {
        return !isset($this->data['error']) && isset($this->data['redirect_flows']);
    }

    public function getTransactionReference()
    {
        if ($this->isRedirect()) {
            return $this->data['redirect_flows']['id'];
        }
    }

    public function getMessage()
    {
        if (!$this->isRedirect()) {
            return $this->data['error']['message'];
        }
    }

    public function getRedirectUrl()
    {
        if ($this->isRedirect()) {
            return $this->data['redirect_flows']['redirect_url'];
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
}

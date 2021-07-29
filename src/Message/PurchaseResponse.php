<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Message\AbstractResponse;

/**
 * GoCardless Purchase Response
 */
class PurchaseResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        $status = $this->getStatus();
        return !$this->isError() && isset($this->data['payments'])
            && ($status == 'submitted' || $status == 'confirmed' || $status == 'paid_out');
    }

    public function isPending()
    {
        $status = $this->getStatus();
        return $status == 'pending_customer_approval' || $status == 'pending_submission';
    }

    public function isCancelled()
    {
        return $this->getStatus() == 'cancelled';
    }

    public function isRedirect()
    {
        return false;
    }

    public function isError()
    {
        return isset($this->data['error']);
    }

    public function getTransactionReference()
    {
        if (!$this->isError()) {
            return $this->data['payments']['id'];
        }
    }

    public function getMessage()
    {
        if ($this->isError()) {
            return $this->data['error']['message'];
        }
    }

    public function getMandateId()
    {
        if ($this->isSuccessful() || $this->isPending()) {
            return $this->data['payments']['links']['mandate'];
        }
    }

    public function getMetaData()
    {
        if ($this->isSuccessful() || $this->isPending()) {
            return $this->data['payments']['metadata'];
        }
    }

    public function getCode()
    {
        return $this->isError() ? $this->data['error']['code'] : $this->getStatus();
    }

    public function getStatus()
    {
        return $this->isError() ? $this->data['error']['type'] : $this->data['payments']['status'];
    }
}

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
            && ($status == 'confirmed' || $status == 'paid_out');
    }

    public function isPending()
    {
        $status = $this->getStatus();
        return $status == 'pending_customer_approval' || $status == 'pending_submission' || $status == 'submitted';
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

    public function getChargeDate()
    {
        if ($this->isSuccessful() || $this->isPending() || $this->isCancelled()) {
            return $this->data['payments']['charge_date'];
        }
    }

    public function getAmount()
    {
        if ($this->isSuccessful() || $this->isPending() || $this->isCancelled()) {
            return $this->data['payments']['amount'];
        }
    }

    public function getDescription()
    {
        if ($this->isSuccessful() || $this->isPending()) {
            return $this->data['payments']['description'];
        }
    }

    public function getCurrency()
    {
        if ($this->isSuccessful() || $this->isPending() || $this->isCancelled()) {
            return $this->data['payments']['currency'];
        }
    }

    public function getMessage()
    {
        if ($this->isError()) {
            return $this->data['error']['message'];
        }
    }

    public function getErrors()
    {
        if ($this->isError()) {
            return $this->data['error']['errors'];
        }
    }

    public function getMandateId()
    {
        if ($this->isSuccessful() || $this->isPending()) {
            return $this->data['payments']['links']['mandate'];
        }
    }

    /**
     * Treat mandate as a 'card' to support createCard pattern
     */
    public function getCardReference()
    {
        return $this->getMandateId();
    }

    public function getMetaData()
    {
        if ($this->isSuccessful() || $this->isPending() || $this->isCancelled()) {
            return $this->data['payments']['metadata'];
        }
    }

    public function getAmountRefunded()
    {
        if ($this->isSuccessful() || $this->isPending() || $this->isCancelled()) {
            return $this->data['payments']['amount_refunded'];
        }
    }

    public function getFx()
    {
        if ($this->isSuccessful() || $this->isPending() || $this->isCancelled()) {
            return $this->data['payments']['fx'];
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

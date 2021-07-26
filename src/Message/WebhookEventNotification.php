<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Common\Message\NotificationInterface;

class WebhookEventNotification extends AbstractRequest implements NotificationInterface
{
    public function setNotification($value)
    {
        return $this->setParameter('notification', $value);
    }

    public function getNotification()
    {
        return $this->getParameter('notification');
    }

    public function getData()
    {
        $this->validate('notification');
        return $this->getNotification();
    }

    /**
     * @return null|string
     */
    public function getAction()
    {
        $data = $this->getData();
        if (isset($data['action'])) {
            return $data['action'];
        }
    }

    /**
     * @return null|string
     */
    public function getCode()
    {
        $data = $this->getData();
        if (isset($data['details']['cause'])) {
            return $data['details']['cause'];
        }
    }

    /**
     * @return null|string  One of: bank, gocardless, api, customer
     */
    public function getEventOrigin()
    {
        $data = $this->getData();
        if (isset($data['details']['origin'])) {
            return $data['details']['origin'];
        }
    }

    /**
     * @return null|string
     */
    public function getMessage()
    {
        $data = $this->getData();
        if (isset($data['details']['description'])) {
            return $data['details']['description'];
        }
    }

    /**
     * @return null|string
     */
    public function getTransactionReference()
    {
        $data = $this->getData();
        if (isset($data['id'])) {
            return $data['id'];
        }
    }

    /**
     * @link https://developer.gocardless.com/api-reference/#event-actions-payment
     *
     * @return null|string
     */
    public function getTransactionStatus()
    {
        if ($this->getType() === 'payments') {
            switch ($this->getAction()) {
                case 'confirmed':
                case 'paid_out':
                    return NotificationInterface::STATUS_COMPLETED;
                case 'pending_customer_approval':
                case 'pending_submission':
                    return NotificationInterface::STATUS_PENDING;
                case 'cancelled':
                case 'charged_back':
                case 'customer_approval_denied':
                case 'failed':
                    return NotificationInterface::STATUS_FAILED;
                default:
                    // no state change, do nothing? other events from docs:
                    // created
                    // customer_approval_granted -- pending?
                    // submitted -- completed? pending?
                    // chargeback_cancelled
                    // late_failure_settled -- failure?
                    // chargeback_settled -- failure?
                    // surcharge_fee_debited
                    // resubmission_requested -- pending?
                    break;
            }
        }
    }

    /**
     * @return null|string
     */
    public function getType()
    {
        $data = $this->getData();
        if (isset($data['resource_type'])) {
            return $data['resource_type'];
        }
    }

    /**
     * Implemented as part of {@see AbstractRequest}} for legacy support
     */
    public function sendData($data)
    {
        return $this;
    }
}

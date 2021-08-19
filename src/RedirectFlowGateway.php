<?php

namespace Omnipay\GoCardless;

use Omnipay\Common\Exception\RuntimeException;
use Omnipay\GoCardless\AbstractGateway;
use Omnipay\GoCardless\Message\FetchEventRequest;
use Omnipay\GoCardless\Message\FetchPurchaseRequest;
use Omnipay\GoCardless\Message\RedirectCompleteFlowRequest;
use Omnipay\GoCardless\Message\RedirectCompleteFlowResponse;
use Omnipay\GoCardless\Message\RedirectFlowRequest;
use Omnipay\GoCardless\Message\RedirectFlowResponse;
use Omnipay\GoCardless\Message\PurchaseRequest;
use Omnipay\GoCardless\Message\WebhookEventNotification;
use Omnipay\GoCardless\Message\WebhookNotification;

class RedirectFlowGateway extends AbstractGateway
{
    public function getName()
    {
        return 'GoCardless Redirect Flow';
    }

    // @todo note: might need to do this/add redirectFlowId accessors in order to get superclass unit tests working
    //             but can focus on those after getting the core tests done
    // public function getDefaultParameters()
    // {
    //     return parent::getDefaultParameters() + ['redirectFlowId' => null];
    // }

    // @todo authorize?
    // @todo completeAuthorize?

    /**
     * Begin Redirect flow
     *
     * Treats a mandate as a 'card'
     * 
     * @return RedirectFlowRequest
     */
    public function createCard(array $parameters = array())
    {
        return $this->redirectFlow($parameters);
    }

    /**
     * Complete Redirect flow
     *
     * Treats a mandate as a 'card'
     *
     * @return RedirectCompleteFlowRequest
     */
    public function completeCreateCard(array $parameters = array())
    {
        return $this->completeRedirectFlow($parameters);
    }

    /**
     * Begin Redirect Flow
     *
     * @return RedirectFlowRequest
     */
    public function redirectFlow(array $parameters = array())
    {
        return $this->createRequest(RedirectFlowRequest::class, $parameters);
    }

    /**
     * Complete Redirect Flow
     *
     * @return RedirectCompleteFlowRequest
     */
    public function completeRedirectFlow(array $parameters = array())
    {
        return $this->createRequest(RedirectCompleteFlowRequest::class, $parameters);
    }

    /**
     * Make a payment, or begin redirect flow if no mandate has been given
     *
     * @return RedirectFlowRequest|PurchaseRequest
     */
    public function purchase(array $parameters = array())
    {
        if (empty($parameters['mandateId'])) {
            return $this->redirectFlow($parameters);
        }
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }

    /**
     * Extract the mandate from a completed redirect flow, then make a payment
     *
     * @return RedirectCompleteFlowReponse|PurchaseRequest
     */
    public function completePurchase(array $parameters = array())
    {
        if (empty($parameters['mandateId'])) {
            $flowResponse = $this->completeRedirectFlow($parameters)->send();
            if (!$flowResponse->isSuccessful()) {
                // @todo change to use `->getRequest()` so it can be re-tried by calling application?
                return $flowResponse;
            }
            $parameters['mandateId'] = $flowResponse->getMandateId();
        }
        return $this->purchase($parameters);
    }

    /**
     * Parse webhook to validate/process pending transactions
     */
    public function acceptNotification(array $parameters = array())
    {
        return $this->createRequest(WebhookEventNotification::class, $parameters);
    }

    /**
     * Parse batch of webhooks to validate and provide events for {@see self::acceptNotification()}
     *
     * @todo do we both with parameters?
     */
    public function acceptNotificationBatch(array $parameters = array())
    {
        return $this->createRequest(WebhookNotification::class, $parameters);
    }

    /**
     * Fetch the details for a specific event (webhook notification)
     */
    public function fetchEvent(array $parameters = array())
    {
        return $this->createRequest(FetchEventRequest::class, $parameters);
    }

    /**
     * Fetch the details for a specific payment
     */
    public function fetchPurchase(array $parameters = array())
    {
        return $this->createRequest(FetchPurchaseRequest::class, $parameters);
    }
}

<?php

namespace Omnipay\GoCardless;

use Omnipay\Common\Exception\RuntimeException;
use Omnipay\GoCardless\AbstractGateway;
use Omnipay\GoCardless\Message\RedirectCompleteFlowRequest;
use Omnipay\GoCardless\Message\RedirectCompleteFlowResponse;
use Omnipay\GoCardless\Message\RedirectFlowRequest;
use Omnipay\GoCardless\Message\RedirectFlowResponse;
use Omnipay\GoCardless\Message\PurchaseRequest;

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
    // @todo createCard?
    // @todo completeCreateCard?

    /**
     * Complete Redirect Flow
     *
     * @todo could this alias to createCard()?
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
        throw new RuntimeException('Not implemented yet');
    }
}

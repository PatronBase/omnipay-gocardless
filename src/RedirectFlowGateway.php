<?php

namespace Omnipay\GoCardless;

class RedirectFlowGateway extends AbstractGateway
{
    /**
     * @param array $parameters
     *
     * @return Message\RedirectAuthoriseRequest|Message\AbstractRequest|RedirectFlowGateway
     */
    public function authoriseRequest(array $parameters = [])
    {
        return $this->createRequest(Message\RedirectAuthoriseRequest::class, $parameters);
    }

    /**
     * @param array $parameters
     *
     * @return Message\RedirectCompleteAuthoriseRequest|Message\AbstractRequest|RedirectFlowGateway
     */
    public function completeAuthoriseRequest(array $parameters = [])
    {
        return $this->createRequest(Message\RedirectCompleteAuthoriseRequest::class, $parameters);
    }
}

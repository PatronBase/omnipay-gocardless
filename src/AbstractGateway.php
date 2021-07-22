<?php

namespace Omnipay\GoCardless;

use Omnipay\Common\AbstractGateway as BaseAbstractGateway;

abstract class AbstractGateway extends BaseAbstractGateway
{
    /**
     * @inheritdoc
     */
    abstract public function getName();

    public function getDefaultParameters()
    {
        return array(
            'accessToken' => '',
            'testMode' => false,
        );
    }

    public function setAccessToken($value)
    {
        return $this->setParameter('access_token', $value);
    }

    public function getAccessToken()
    {
        return $this->getParameter('access_token');
    }

    public function setRedirectFlowId($value)
    {
        return $this->setParameter('redirectFlowId', $value);
    }

    public function setSessionToken($value)
    {
        return $this->setParameter('session_token', $value);
    }

    public function setMandateId($value)
    {
        return $this->setParameter('mandateId', $value);
    }
}

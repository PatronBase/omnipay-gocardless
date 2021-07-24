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
        return $this->setParameter('accessToken', $value);
    }

    public function getAccessToken()
    {
        return $this->getParameter('accessToken');
    }
}

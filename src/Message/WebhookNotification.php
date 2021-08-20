<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\AbstractRequest;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * @todo do we want to implement ResponseInterface so we have a getMessage() in case of error?
 */
class WebhookNotification extends AbstractRequest
{
    /**
     * @var mixed The data contained in the request.
     */
    protected $data;

    /**
     * @inheritDoc
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest)
    {
        parent::__construct($httpClient, $httpRequest);
        // fetch POST stream directly
        $this->data = json_decode($httpRequest->getContent(), true);
    }

    /**
     * @return null|mixed
     */
    public function getData()
    {
        return $this->data;
    }
    

    /**
     * @return array
     */
    public function getNotifications()
    {
        if (isset($this->data['events'])) {
            return $this->data['events'];
        }
        return [];
    }

    /**
     * @return null|string
     */
    public function getWebhookId()
    {
        if (isset($this->data['meta']['webhook_id'])) {
            return $this->data['meta']['webhook_id'];
        }
    }

    /**
     * Given a secret, is the signature on this webhook valid?
     *
     * Note: request bodies are minified JSON, so strip superfluous whitespace when testing, including trailing newlines
     *
     * @return bool  Application should return '498 Token Invalid' if false
     */
    public function hasValidSignature($secret)
    {
        $supplied = $this->httpRequest->headers->get('Webhook-Signature');
        $calculated = hash_hmac('sha256', $this->httpRequest->getContent(), $secret);
        return $supplied === $calculated;
    }

    /**
     * Implemented as part of {@see AbstractRequest}} for legacy support
     */
    public function sendData($data)
    {
        return $this;
    }
}

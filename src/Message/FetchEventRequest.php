<?php

namespace Omnipay\GoCardless\Message;

/**
 * Fetch the details of a specific event (webhook notification) from GoCardless
 *
 * @see https://developer.gocardless.com/api-reference/#events-get-a-single-event
 */
class FetchEventRequest extends AbstractRequest
{
    public function getEventId()
    {
        return $this->getParameter('eventId');
    }

    public function setEventId($value)
    {
        return $this->setParameter('eventId', $value);
    }

    public function getData()
    {
        $this->validate('eventId');
        $this->action = '/events/'.$this->getEventId();
        return null;
    }

    public function sendData($data)
    {
        $response = $this->sendRequest($data, 'GET');
        $json = json_decode($response->getBody()->getContents(), true);
        // if there's an event, retrieve the details, otherwise pass through the error
        $notification = isset($json['events']) ? $json['events'] : $json;

        $this->response = new WebhookEventNotification($this->httpClient, $this->httpRequest);
        $this->response->initialize(array_replace($this->getParameters(), ['notification' => $notification]));

        return $this->response;
    }
}

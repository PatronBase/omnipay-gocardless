<?php

namespace Omnipay\GoCardless\Message;

class RedirectFlowRequest extends AbstractRequest
{
    public function getSessionToken()
    {
        return $this->getParameter('sessionToken');
    }

    public function setSessionToken($value)
    {
        return $this->setParameter('sessionToken', $value);
    }

    public function getAccountType()
    {
        return $this->getParameter('accountType');
    }

    public function setAccountType($value)
    {
        return $this->setParameter('accountType', $value);
    }

    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    /**
     * @param string $value ISO 639-1 code, {@see http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes}
     */
    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    public function getDanishIdentityNumber()
    {
        return $this->getParameter('danishIdentityNumber');
    }

    public function setDanishIdentityNumber($value)
    {
        return $this->setParameter('danishIdentityNumber', $value);
    }

    public function getSwedishIdentityNumber()
    {
        return $this->getParameter('swedishIdentityNumber');
    }
  
    public function setSwedishIdentityNumber($value)
    {
        return $this->setParameter('swedishIdentityNumber', $value);
    }

    public function getMetaData()
    {
        return $this->getParameter('metadata');
    }

    /**
     * Meta data parameter is a key-value store
     *
     * Up to 3 keys are permitted, with key names up to 50 characters and values up to 500 characters
     *
     * @todo validate input and parse into a valid format
     */
    public function setMetaData($value)
    {
        return $this->setParameter('metadata', $value);
    }

    public function getScheme()
    {
        return $this->getParameter('scheme');
    }

    public function setScheme($value)
    {
        return $this->setParameter('scheme', $value);
    }

    public function getCreditorId()
    {
        return $this->getParameter('creditorId');
    }

    public function setCreditorId($value)
    {
        return $this->setParameter('creditorId', $value);
    }

    public function getData()
    {
        $this->validate('sessionToken', 'returnUrl');

        // Required values
        $data = [
            'session_token' => $this->getSessionToken(),
            'success_redirect_url' => $this->getReturnUrl(),
        ];

        // Optional values
        $prefilledBankAccount = [
            'account_type' => $this->getAccountType(),
        ];
        $prefilledCustomer = [
            'language' => $this->getLanguage(),
            // @todo validate against country?
            'danish_identity_number' => $this->getDanishIdentityNumber(),
            // @todo validate against country?
            'swedish_identity_number' => $this->getSwedishIdentityNumber(),
        ];
        $card = $this->getCard();
        if ($card) {
            $prefilledCustomer += [
                'address_line1' => $card->getAddress1(),
                'address_line2' => $card->getAddress2(),
                'address_line3' => $card->getAddress3(),
                'city' => $card->getCity(),
                'postal_code' => $card->getPostcode(),
                'region' => $card->getState(),
                // ISO 3166-1 alpha-2 code
                'country_code' => $card->getCountry(),
                'email' => $card->getEmail(),
                'family_name' => $card->getLastName(),
                'given_name' => $card->getFirstName(),
                 // phone number is for New Zealand customers only
                'phone_number' => $card->getCountry() == 'NZ' ? $card->getPhone() : null,
            ];
            if ($card->getLastName() == null && $card->getLastName() == null) {
                $prefilledCustomer['company_name'] = $card->getCompany();
            }
        }
        $links = [
            'creditor' => $this->getCreditorId(),
        ];
        $data += array_filter([
            'description' => $this->getDescription(),
            // key-value store, ends up as native JSON rather than encoded string
            'metadata' => $this->getMetaData(),
            'prefilled_bank_account' => array_filter($prefilledBankAccount),
            'prefilled_customer' => array_filter($prefilledCustomer),
            'scheme' => $this->getScheme(),
            'links' => array_filter($links),
        ]);

        return $data;
    }

    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return RedirectFlowResponse
     */
    public function sendData($data)
    {
        $response = $this->sendRequest(['redirect_flows' => $data]);

        return $this->response = new RedirectFlowResponse(
            $this,
            json_decode($response->getBody()->getContents(), true)
        );
    }
}

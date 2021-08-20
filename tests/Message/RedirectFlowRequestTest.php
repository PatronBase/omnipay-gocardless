<?php

namespace Omnipay\GoCardless\Message;

use Omnipay\Tests\TestCase;

class RedirectFlowRequestTest extends TestCase
{
    /** @var RedirectFlowRequest */
    private $request;

    /** @var mixed[]  Data to initialize the request with */
    private $options;

    public function setUp()
    {
        $this->request = new RedirectFlowRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->options = array(
            'card'                  => $this->getValidCard(),
            'description'           => 'Wine boxes',
            'returnUrl'             => 'https://example.com/pay/confirm',
            'sessionToken'          => 'SESS_wSs0uGYMISxzqOBq',
            'language'              => 'en',
            'accountType'           => 'checking',
            'scheme'                => 'iban',
            'creditorId'            => 'CR123',
            // these two would never be on the same request for a real request
            'swedishIdentityNumber' => '221014-1234',
            'danishIdentityNumber'  => '221014-5678',
        );
        $this->request->initialize($this->options);
    }

    public function testAccessors()
    {
        $this->assertSame('en', $this->request->getLanguage());
        $this->assertSame('checking', $this->request->getAccountType());
        $this->assertSame('iban', $this->request->getScheme());
        $this->assertSame('CR123', $this->request->getCreditorId());
        $this->assertSame('221014-1234', $this->request->getSwedishIdentityNumber());
        $this->assertSame('221014-5678', $this->request->getDanishIdentityNumber());
    }


    public function testCompanyName()
    {
        $card = $this->request->getCard();
        $card->setFirstName(null);
        $card->setLastName(null);
        $card->setCompany('Bank Testers Inc.');
        $this->request->setCard($card);

        $data = $this->request->getData();
        $this->assertSame('Bank Testers Inc.', $data['prefilled_customer']['company_name']);
        $this->assertArrayNotHasKey('family_name', $data['prefilled_customer']);
        $this->assertArrayNotHasKey('given_name', $data['prefilled_customer']);
    }
}

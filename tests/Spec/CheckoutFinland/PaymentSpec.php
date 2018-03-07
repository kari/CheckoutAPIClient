<?php

namespace Spec\CheckoutFinland;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PaymentSpec extends ObjectBehavior
{
    /**
     * @var string 375917 is the test id for Checkout Finland
     */
    private $demo_merchant_id       = "375917";

    /**
     * @var string SAIPPUAKAUPPIAS is the secret for the test merchant
     */
    private $demo_merchant_secret   = "SAIPPUAKAUPPIAS";

    public function it_is_initializable()
    {
        $this->shouldHaveType('CheckoutFinland\Payment');
    }

    public function let()
    {
        $this->beConstructedWith($this->demo_merchant_id, $this->demo_merchant_secret);
    }

    public function it_stores_order_data()
    {
        date_default_timezone_set('Europe/Helsinki');

        $stamp          = '1245132';
        $amount         = '1000';
        $reference      = '12344';
        $message        = 'Nuts and bolts';
        $delivery_date  = new \DateTime('2014-12-31');

        $this->setOrderData($stamp, $amount, $reference, $message, $delivery_date);
    }

    public function it_stores_customer_data()
    {
        $first_name     = 'John';
        $family_name    = 'Doe';
        $address        = 'Some Street 13 B 2';
        $postcode       = '33100';
        $post_office    = 'Some city';
        $country        = 'FIN';
        $language       = 'EN';

        $this->setCustomerData($first_name, $family_name, $address, $postcode, $post_office, $country, $language);
    }

    public function it_can_store_all_data_from_single_array()
    {
        $payment_data = [
            'stamp'             => '1245132',
            'amount'            => '1000',
            'reference'         => '12344',
            'message'           => 'Nuts and bolts',
            'deliveryDate'      => new \DateTime('2014-12-31'),
            'firstName'         => 'John',
            'familyName'        => 'Doe',
            'address'           => 'Some street 13 B 2',
            'postcode'          => '33100',
            'postOffice'        => 'Some city',
            'country'           => 'FIN',
            'language'          => 'EN',
            'phonenumber'       => '04512345678',
            'email'             => 'test@example.org'
        ];

        $this->setData($payment_data);

        $this->getReference()->shouldBe('12344');
        $this->getPostOffice()->shouldBe('Some city');
    }

    public function it_throws_exception_when_amount_is_too_large()
    {
        $this->shouldThrow('CheckoutFinland\Exceptions\AmountTooLargeException')->duringSetAmount("100000000");
    }

    public function it_throws_exception_when_amount_is_too_small()
    {
        $this->shouldThrow('CheckoutFinland\Exceptions\AmountUnderMinimumException')->duringSetAmount("10");
    }

    public function it_throws_exceptions_when_urls_are_too_long()
    {
        $long_url = str_pad("http://f", 301, "o");

        $this->shouldThrow('CheckoutFinland\Exceptions\UrlTooLongException')->duringSetCancelUrl($long_url);
        $this->shouldThrow('CheckoutFinland\Exceptions\UrlTooLongException')->duringSetReturnUrl($long_url);
        $this->shouldThrow('CheckoutFinland\Exceptions\UrlTooLongException')->duringSetDelayedUrl($long_url);
        $this->shouldThrow('CheckoutFinland\Exceptions\UrlTooLongException')->duringSetRejectUrl($long_url);
    }

    public function it_can_set_all_return_urls_at_once()
    {
        $url = 'www.return.url';
        $this->setUrls($url);

        $this->getReturnUrl()->shouldBe($url);
        $this->getCancelUrl()->shouldBe($url);
        $this->getDelayedUrl()->shouldBe($url);
        $this->getRejectUrl()->shouldBe($url);
    }

    public function it_throws_exceptions_when_trying_to_set_too_long_variables_to_critical_fields()
    {
        $long_string = str_pad('foo', 21, 'o');

        $this->shouldThrow('CheckoutFinland\Exceptions\VariableTooLongException')->duringSetMerchantId($long_string);
        $this->shouldThrow('CheckoutFinland\Exceptions\VariableTooLongException')->duringSetReference($long_string);
        $this->shouldThrow('CheckoutFinland\Exceptions\VariableTooLongException')->duringSetStamp($long_string);
    }

    public function it_truncates_strings_that_are_too_long_when_they_are_not_critical()
    {
        $long_name = str_pad("Jeffrey", 45, "y");
        $long_name_truncated = str_pad("Jeffrey", 40, "y");

        $this->setFirstName($long_name);
        $this->getFirstName()->shouldBe($long_name_truncated);

        $this->setFamilyName($long_name);
        $this->getFamilyName()->shouldBe($long_name_truncated);
    }

    public function it_calculates_a_mac_from_variables()
    {
        $payment_data = [
            'version'           => '0001',
            'stamp'             => '1245132',
            'amount'            => '1000',
            'reference'         => '12344',
            'message'           => 'Nuts and bolts',
            'language'          => 'EN',
            'merchant'          => $this->demo_merchant_id,
            'returnUrl'         => 'www.someurl.com',
            'cancelUrl'         => 'www.someurl.com',
            'rejectUrl'         => 'www.someurl.com',
            'delayedUrl'        => 'www.someurl.com',
            'country'           => 'FIN',
            'currency'          => 'EUR',
            'device'            => '10',
            'content'           => '1',
            'type'              => '0',
            'algorithm'         => '3',
            'deliveryDate'      => '20141005',
            'firstName'         => 'John',
            'familyName'        => 'Doe',
            'address'           => 'Some street 13 B 2',
            'postcode'          => '33100',
            'postOffice'        => 'Some city'
        ];


        $hashString = join(
            '+',
            array_values($payment_data)
        );

        $expected_mac = strtoupper(hash_hmac('sha256', $hashString, $this->demo_merchant_secret));


        $this->setUrls('www.someurl.com');

        $this->setOrderData(
            $payment_data['stamp'],
            $payment_data['amount'],
            $payment_data['reference'],
            $payment_data['message'],
            new \DateTime('2014-10-05')
        );

        $this->setCustomerData(
            $payment_data['firstName'],
            $payment_data['familyName'],
            $payment_data['address'],
            $payment_data['postcode'],
            $payment_data['postOffice'],
            $payment_data['country'],
            $payment_data['language'],
            'test@example.org',
            '045123456789'
        );

        $this->calculateMac()->shouldBe($expected_mac);
    }
}

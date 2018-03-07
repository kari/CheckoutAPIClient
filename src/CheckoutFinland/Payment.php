<?php

namespace CheckoutFinland;

use CheckoutFinland\Exceptions\AmountTooLargeException;
use CheckoutFinland\Exceptions\AmountUnderMinimumException;
use CheckoutFinland\Exceptions\CurrencyNotSupportedException;
use CheckoutFinland\Exceptions\UrlTooLongException;
use CheckoutFinland\Exceptions\VariableTooLongException;

/**
 * Class Payment
 * @package CheckoutFinland
 */
class Payment
{

    /**
     * @var string Merchant id (AN 20)
     */
    protected $merchantId;
    /**
     * @var string Secret merchant key
     */
    protected $merchantSecret;

    /**
     * @var string Payment version, currently always '0001' (AN 4)
     */
    protected $version;
    /**
     * @var string Unique identifier for the payment (AN 20)
     */
    protected $stamp;
    /**
     * @var string Amount of payment in cents (10€ == 1000) (N 8)
     */
    protected $amount;
    /**
     * @var string Reference number for the payment, recommended to be unique but not forced (AN 20)
     */
    protected $reference;
    /**
     * @var string Message/Description for the buyer (Nuts and bolts, Furniture, Cuckoo clocks) (AN 1000)
     */
    protected $message;
    /**
     * @var string Language of the payment selection page/bank interface if supported. Currently supported languages include Finnish FI, Swedish SE and English EN (AN  2)
     */
    protected $language;

    /**
     * @var string Url called when returning successfully (AN 300)
     */
    protected $returnUrl;
    /**
     * @var string Url called when user cancelled payment (AN 300)
     */
    protected $cancelUrl;
    /**
     * @var string Url called when payment was rejected (No credit on credit card etc) (AN 300)
     */
    protected $rejectUrl;
    /**
     * @var string Url called when payment is initially successful but not yet confirmed (AN 300)
     */
    protected $delayedUrl;

    /**
     * @var string Country of the buyer, affects available payment methods (AN 3)
     */
    protected $country;
    /**
     * @var string Currency used in payment. Currently only EUR is supported (AN 3)
     */
    protected $currency;
    /**
     * @var string device or method used when creating new transaction. Affects how Checkout servers respond to posting the new payment 1 = HTML 10 = XML (N 2)
     */
    protected $device;
    /**
     * @var string Payment type or content of purchase. Used to differentiate between adult entertainment and everything else. 1 = normal, 2 = adult entertainment (N 2)
     */
    protected $content;
    /**
     * @var string Type, currently always 0 (N 1)
     */
    protected $type;
    /**
     * @var string Algorithm used when calculating mac, currently = 3. 1 and 2 are still available but deprecated (N 1)
     */
    protected $algorithm;

    /**
     * @var string Expected delivery date (N 8) (Ymd)
     */
    protected $deliveryDate;
    /**
     * @var string First name of customer (AN 40)
     */
    protected $firstName;
    /**
     * @var string Last name of customer (AN 40)
     */
    protected $familyName;
    /**
     * @var string Street address of customer (AN 40)
     */
    protected $address;
    /**
     * @var string Postcode of customer (AN 14)
     */
    protected $postcode;
    /**
     * @var string Post office of customer (AN 18)
     */
    protected $postOffice;

    /**
     * @var bool If true overrides the minimum allowed amount check (by default 1€ is smallest allowed amount). Do not set to true unless you have a contract with Checkout Finland that allows smaller purchases then 1€.
     */
    private $allowSmallPurchases;

    /**
     * @var string Email of customer (AN 200)
     */
    private $email;

    /**
     * @var string Phone number of customer (AN 200)
     */
    private $phonenumber;

    /**
     * @var string Fields used in mac calculation
     * @static
     * @access private
     */
    private static $MAC_FIELDS = [
        'version',
        'stamp',
        'amount',
        'reference',
        'message',
        'language',
        'merchantId',
        'returnUrl',
        'cancelUrl',
        'rejectUrl',
        'delayedUrl',
        'country',
        'currency',
        'device',
        'content',
        'type',
        'algorithm',
        'deliveryDate',
        'firstName',
        'familyName',
        'address',
        'postcode',
        'postOffice'
    ];

    /**
     * @param $merchantId
     * @param $merchantSecret
     * @param $allowSmallPurchases
     */
    public function __construct($merchantId, $merchantSecret, $allowSmallPurchases = false)
    {
        $this->merchantId      = $merchantId;
        $this->merchantSecret  = $merchantSecret;

        $this->allowSmallPurchases = $allowSmallPurchases;

        $this->setDefaultValues();
    }

    /**
     * @return $this
     */
    private function setDefaultValues()
    {
        $this->version      = '0001';
        $this->device       = '10';
        $this->content      = '1';
        $this->type         = '0';
        $this->algorithm    = '3';
        $this->currency     = 'EUR';

        return $this;
    }

    /**
     * Sets payment/order information
     *
     * @param $stamp
     * @param $amount
     * @param $reference
     * @param $message
     * @param \DateTimeInterface $deliveryDate
     * @return $this
     */
    public function setOrderData($stamp, $amount, $reference, $message, \DateTimeInterface $deliveryDate)
    {
        $this->setStamp($stamp);
        $this->setAmount($amount);
        $this->setReference($reference);
        $this->setMessage($message);
        $this->setDeliveryDate($deliveryDate);

        return $this;
    }

    /**
     * Sets customer information
     *
     * @param $firstName
     * @param $familyName
     * @param $address
     * @param $postcode
     * @param $postOffice
     * @param $country
     * @param $language
     * @return $this
     */
    public function setCustomerData($firstName, $familyName, $address, $postcode, $postOffice, $country, $language, $email = '', $phonenumber = '')
    {
        $this->setFirstName($firstName);
        $this->setFamilyName($familyName);
        $this->setAddress($address);
        $this->setPostcode($postcode);
        $this->setPostOffice($postOffice);
        $this->setCountry($country);
        $this->setLanguage($language);
        $this->setEmail($email);
        $this->setPhonenumber($phonenumber);

        return $this;
    }

    /**
     * Sets multiple variables at once
     *
     * $example_data =  [
     *   'stamp'            => '1245132',
     *   'amount'           => '1000',
     *   'reference'        => '12344',
     *   'message'          => 'Nuts and bolts',
     *   'deliveryDate'     => new \DateTime('2014-12-31'),
     *   'firstName'        => 'John',
     *   'familyName'       => 'Doe',
     *   'address'          => 'Some street 13 B 2',
     *   'postcode'         => '33100',
     *   'postOffice'       => 'Some city',
     *   'country'          => 'FIN',
     *   'language'         => 'EN'
     *   ];
     *
     * @param array $params
     * @return $this
     */
    public function setData(array $params)
    {
        foreach ($params as $key => $value) {
            $setter_name = "set".ucfirst($key);

            if (method_exists($this, $setter_name)) {
                $this->$setter_name($value);
            }
        }

        return $this;
    }

    /**
     * Set all response urls as the same return url.
     *
     * @param string $returnUrl
     * @throws UrlTooLongException
     * @return $this
     */
    public function setUrls($returnUrl)
    {
        $this->setReturnUrl($returnUrl);
        $this->setCancelUrl($returnUrl);
        $this->setDelayedUrl($returnUrl);
        $this->setRejectUrl($returnUrl);

        return $this;
    }

    /**
    *   Calculates MAC string from all variables in the class
    *
    *   @return string
    */
    public function calculateMac()
    {
        $obj = $this;
        $mac_string = implode(
            '+',
            array_map(function ($e) use ($obj) {
                return $obj->$e;
            }, self::$MAC_FIELDS)
        );
        return strtoupper(hash_hmac('sha256', $mac_string, $this->getMerchantSecret()));
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = substr($address, 0, 40);

        return $this;
    }

    /**
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * @param string $algorithm
     * @return $this
     */
    public function setAlgorithm($algorithm)
    {
        $this->algorithm = substr($algorithm, 0, 1);

        return $this;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     * @throws AmountTooLargeException
     * @throws AmountUnderMinimumException
     * @return $this
     */
    public function setAmount($amount)
    {
        if (strlen($amount) > 8) {
            throw new AmountTooLargeException($amount ." is too large.");
        }

        if ($this->allowSmallPurchases == false and $amount < 100) {
            throw new AmountUnderMinimumException("1€ is the minimum allowed amount.");
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * @param string $cancelUrl
     * @throws UrlTooLongException
     * @return $this
     */
    public function setCancelUrl($cancelUrl)
    {
        if (strlen($cancelUrl) > 300) {
            throw new UrlTooLongException('Max url length is 300 characters');
        }

        $this->cancelUrl = $cancelUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = substr($content, 0, 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = substr($country, 0, 3);

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @throws CurrencyNotSupportedException
     * @return $this
     */
    public function setCurrency($currency)
    {
        if ($currency != 'EUR') {
            throw new CurrencyNotSupportedException('EUR is currently the only supported currency');
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getDelayedUrl()
    {
        return $this->delayedUrl;
    }

    /**
     * @param string $delayedUrl
     * @throws UrlTooLongException
     * @return $this
     */
    public function setDelayedUrl($delayedUrl)
    {
        if (strlen($delayedUrl) > 300) {
            throw new UrlTooLongException('Max url length is 300 characters');
        }

        $this->delayedUrl = $delayedUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->deliveryDate;
    }

    /**
     * @param \DateTimeInterface $deliveryDate
     * @return $this
     */
    public function setDeliveryDate(\DateTimeInterface $deliveryDate)
    {
        $this->deliveryDate = $deliveryDate->format('Ymd');

        return $this;
    }

    /**
     * @return string
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param string $device
     * @return $this
     */
    public function setDevice($device)
    {
        $this->device = substr($device, 0, 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * @param string $familyName
     * @return $this
     */
    public function setFamilyName($familyName)
    {
        $this->familyName = substr($familyName, 0, 40);

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = substr($firstName, 0, 40);

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = substr($language, 0, 2);

        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param string $merchantId
     * @throws VariableTooLongException
     * @return $this
     */
    public function setMerchantId($merchantId)
    {
        if (strlen($merchantId) > 20) {
            throw new VariableTooLongException("Merchant id: $merchantId too long, max length is 20 characters");
        }

        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantSecret()
    {
        return $this->merchantSecret;
    }

    /**
     * @param string $merchantSecret
     * @return $this
     */
    public function setMerchantSecret($merchantSecret)
    {
        $this->merchantSecret = $merchantSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = substr($message, 0, 1000);

        return $this;
    }

    /**
     * @return string
     */
    public function getPostOffice()
    {
        return $this->postOffice;
    }

    /**
     * @param string $postOffice
     * @return $this
     */
    public function setPostOffice($postOffice)
    {
        $this->postOffice = substr($postOffice, 0, 18);

        return $this;
    }

    /**
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode)
    {
        $this->postcode = substr($postcode, 0, 14);

        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @throws VariableTooLongException
     * @return $this
     */
    public function setReference($reference)
    {
        if (strlen($reference) > 20) {
            throw new VariableTooLongException("Reference: $reference too long, max length is 20 characters.");
        }

        $this->reference = $reference;

        return $this;
    }

    /**
     * @return string
     */
    public function getRejectUrl()
    {
        return $this->rejectUrl;
    }

    /**
     * @param string $rejectUrl
     * @throws UrlTooLongException
     * @return $this
     */
    public function setRejectUrl($rejectUrl)
    {
        if (strlen($rejectUrl) > 300) {
            throw new UrlTooLongException('Max url length is 300 characters');
        }

        $this->rejectUrl = $rejectUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     * @throws UrlTooLongException
     * @return $this
     */
    public function setReturnUrl($returnUrl)
    {
        if (strlen($returnUrl) > 300) {
            throw new UrlTooLongException('Max url length is 300 characters');
        }

        $this->returnUrl = $returnUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getStamp()
    {
        return $this->stamp;
    }

    /**
     * @param string $stamp
     * @throws VariableTooLongException
     * @return $this
     */
    public function setStamp($stamp)
    {
        if (strlen($stamp) > 20) {
            throw new VariableTooLongException("Stamp: $stamp too long, max length is 20 characters.");
        }

        $this->stamp = $stamp;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = substr($type, 0, 1);

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = substr($version, 0, 4);

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = substr($email, 0, 200);
    }

    /**
     * @param string $phonenumber
     * @return $this
     */
    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
    }

    /**
     * @return string
     */
    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}

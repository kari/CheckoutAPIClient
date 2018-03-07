<?php

namespace Spec\CheckoutFinland;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use CheckoutFinland\Payment;

class ClientSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('CheckoutFinland\Client');
    }

    public function it_sends_payment_data_to_checkout(Payment $payment)
    {
        date_default_timezone_set('Europe/Helsinki');

        $this->sendPayment($payment);
    }
}

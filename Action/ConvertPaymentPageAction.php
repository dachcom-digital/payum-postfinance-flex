<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Customer\Model\Company;
use CoreShop\Component\Order\Model\OrderInterface;
use DachcomDigital\Payum\PostFinance\Flex\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Request\Convert;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use PostFinanceCheckout\Sdk\Model\AddressCreate;
use PostFinanceCheckout\Sdk\Model\LineItemCreate;
use PostFinanceCheckout\Sdk\Model\LineItemType;

class ConvertPaymentPageAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use \Payum\Core\ApiAwareTrait;
    use \Payum\Core\GatewayAwareTrait;
    use \Payum\Core\Security\GenericTokenFactoryAwareTrait;

    const KEY_POSTFINANCE_OBJECTS = 'POSTFINANCE_OBJECTS';
    const KEY_POSTFINANCE_OBJECTS_LINE_ITEMS = 'LINE_ITEMS';
    const KEY_POSTFINANCE_OBJECT_SHIPPING_ADDRESS = 'SHIPPING_ADDRESS';
    const KEY_POSTFINANCE_BILLING_ADDRESS = 'BILLING_ADDRESS';
    const KEY_CURRENCY_CODE = 'CURRENCY_CODE';

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details['ORDERID'] = $payment->getNumber();
        $details['CURRENCY'] = $payment->getCurrencyCode();
        $details['AMOUNT'] = $payment->getTotalAmount();
        $details['COM'] = $payment->getDescription();

        $request->setResult((array)$details);
    }


    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof Payment;
    }
}

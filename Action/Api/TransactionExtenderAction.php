<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action\Api;

use DachcomDigital\Payum\PostFinance\Flex\Request\Api\TransactionExtender;
use DachcomDigital\Payum\PostFinance\Flex\Transaction\Transaction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;

class TransactionExtenderAction implements ActionInterface
{
    /**
     * @param TransactionExtender $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();

        $transaction = new Transaction();

        $transaction->setId($payment->getNumber());
        $transaction->setCurrency($payment->getCurrencyCode());
        $transaction->setAmount($payment->getTotalAmount());

        $request->setTransaction($transaction);
    }

    public function supports($request): bool
    {
        return $request instanceof TransactionExtender
            && $request->getFirstModel() instanceof PaymentInterface;
    }
}

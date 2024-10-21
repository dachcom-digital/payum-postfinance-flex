<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\GetTransactionDetails;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetStatusInterface;
use PostFinanceCheckout\Sdk\Model\TransactionState;

class StatusAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait {
        setApi as _setApi;
    }

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function setApi($api)
    {
        $this->_setApi($api);
    }

    /**
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['transaction_id'] === null) {
            $request->markNew();

            return;
        }

        $this->gateway->execute(new GetTransactionDetails($model));

        $state = $model['State'] ?? null;

        // @see https://checkout.postfinance.ch/doc/api/web-service#_transactionstate

        if ($state === TransactionState::CREATE) {
            $request->markNew();
        } elseif ($state === TransactionState::CONFIRMED) {
            $request->markNew();
        } elseif ($state === TransactionState::PENDING) {
            $request->markPending();
        } elseif ($state === TransactionState::PROCESSING) {
            $request->markPending();
        } elseif ($state === TransactionState::FAILED) {
            $request->markFailed();
        } elseif ($state === TransactionState::AUTHORIZED) {
            $request->markAuthorized();
        } elseif ($state === TransactionState::VOIDED) {
            $request->markCanceled();
        } elseif ($state === TransactionState::COMPLETED) {
            $request->markAuthorized();
        } elseif ($state === TransactionState::FULFILL) {
            $request->markCaptured();
        } elseif ($state === TransactionState::DECLINE) {
            $request->markFailed();
        } else {
            $request->markUnknown();
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}

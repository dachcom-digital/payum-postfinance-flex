<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action\Api;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\CreateTransaction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use PostFinanceCheckout\Sdk\Model\Transaction;

class CreateTransactionAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * @param CreateTransaction $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        try {

            $returnUrl = $request->getToken()->getTargetUrl();
            $tokenHash = $request->getToken()->getHash();

            /** @var Transaction $transaction */
            $transaction = $this->api->prepareTransaction($model, $returnUrl, $tokenHash);

            $model->replace(['transaction_id' => $transaction->getId()]);

        } catch (\Throwable $e) {
            $model->replace(['error_message' => $e->getMessage(), 'failed' => true]);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof CreateTransaction &&
            $request->getModel() instanceof \ArrayAccess;
    }
}

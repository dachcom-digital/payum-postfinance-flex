<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use PayumPostFinanceFlexBundle\Helper\FlexObjectsHelper;

class CapturePaymentPageAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct() {
        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        /** @var Capture $request */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = $request->getModel();

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        if (array_key_exists(Api::KEY_STATUS, $httpRequest->query)) {
            $model->replace($httpRequest->query);
            $request->setModel($model);
            return;
        }

        $returnUrl = $request->getToken()->getTargetUrl();
        $tokenHash = $request->getToken()->getHash();

        try {
            $transactionId = $this->api->prepareTransaction(
                $model,
                $returnUrl,
                $tokenHash
            );
        } catch (\Exception $e) {
            $model[Api::KEY_STATUS] = Api::STATUS_FAILED;
            $request->setModel($model);

            return;
        }

        throw new HttpRedirect(
            $this->api->getPaymentPageUrl($transactionId)
        );
    }

    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}

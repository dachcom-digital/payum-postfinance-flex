<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\CaptureOffsite;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\CreateTransaction;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\RenderIframe;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\RenderLightbox;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;

class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait {
        setApi as _setApi;
    }

    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function setApi($api)
    {
        $this->_setApi($api);
    }

    /**
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (array_key_exists('redirect_status', $httpRequest->query)) {
            $model->replace($httpRequest->query);

            return;
        }

        $transaction = new CreateTransaction($request->getToken());
        $transaction->setModel($model);

        $this->gateway->execute($transaction);

        if ($this->api->getIntegrationType() === 'lightbox') {
            $this->gateway->execute(new RenderLightbox($model));
        } elseif ($this->api->getIntegrationType() === 'iframe') {
            $this->gateway->execute(new RenderIframe($model));
        } else {
            $this->gateway->execute(new CaptureOffsite($model));
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}

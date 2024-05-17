<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action\Api;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use DachcomDigital\Payum\PostFinance\Flex\Request\Api\RenderLightbox;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\RenderTemplate;

class RenderLightboxAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait {
        setApi as _setApi;
    }

    protected string $templatePath;

    public function __construct(string $templatePath)
    {
        $this->apiClass = Api::class;
        $this->templatePath = $templatePath;
    }

    public function setApi($api)
    {
        $this->_setApi($api);
    }

    /**
     * @param RenderLightbox $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $modelData = $model->toUnsafeArray();
        $javascriptUrl = $this->api->getJavascriptUrl($modelData['transaction_id']);

        $template = sprintf('%s/lightbox.html.twig', $this->templatePath);

        $this->gateway->execute($renderTemplate = new RenderTemplate($template, [
            'model'         => $modelData,
            'transactionId' => $modelData['transaction_id'],
            'javascriptUrl' => $javascriptUrl
        ]));

        throw new HttpResponse($renderTemplate->getResult());
    }

    public function supports($request): bool
    {
        if (!$request instanceof RenderLightbox) {
            return false;
        }

        if (!$request->getModel() instanceof \ArrayAccess) {
            return false;
        }

        return array_key_exists('transaction_id', ArrayObject::ensureArrayObject($request->getModel())->toUnsafeArray());
    }
}

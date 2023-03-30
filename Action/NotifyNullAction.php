<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use PostFinanceCheckout\Sdk\Model\Transaction;

class NotifyNullAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        try {

            $body = json_decode($httpRequest->content, true, 512, JSON_THROW_ON_ERROR);

            if ($body === false) {
                return;
            }

            if (!array_key_exists('entityId', $body)) {
                return;
            }

            $transaction = $this->api->getEntity($body['entityId']);

            if (!$transaction instanceof Transaction) {
                return;
            }

            $tokenHash = $transaction->getMetaData()['paymentToken'] ?? null;

        } catch (\Throwable $e) {
            throw new HttpResponse($e->getMessage(), 500, ['Content-Type' => 'text/plain', 'X-Notify-Message' => $e->getMessage()]);
        }

        if ($tokenHash === null) {
            throw new HttpResponse('OK', 200, ['Content-Type' => 'text/plain', 'X-Notify-Message' => 'NO_TOKEN_HASH_FOUND']);
        }

        try {
            $this->gateway->execute($getToken = new GetToken($tokenHash));
        } catch (\Throwable $e) {
            throw new HttpResponse('OK', 200, ['Content-Type' => 'text/plain', 'X-Notify-Message' => 'ALREADY_CLEARED_OUT']);
        }

        $this->gateway->execute(new Notify($getToken->getToken()));
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() === null;
    }
}

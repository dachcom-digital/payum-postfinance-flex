<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use PostFinanceCheckout\Sdk\Model\Transaction;

class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $request = new GetHttpRequest();
        $this->gateway->execute($request);

        try {
            $body = json_decode($request->content, true, 512, JSON_THROW_ON_ERROR);

            if ($body === false) {
                return;
            }

            if (!array_key_exists('entityId', $body)) {
                return;
            }

            $transaction = $this->api->getEntity($body('entityId'));

            if (!$transaction instanceof Transaction) {
                return;
            }

            $tokenHash = $transaction->getMetaData()[Api::META_DATA_KEY_PAYMENT_TOKEN] ?? null;

        } catch (\Exception $e) {
            throw new HttpResponse($e->getMessage(), 500, ['Content-Type' => 'text/plain']);
        }

        if ($tokenHash === null) {
            throw new HttpResponse('Bad Request', 400, ['Content-Type' => 'text/plain']);
        }

        try {
            $token = new GetToken($tokenHash);
            $this->gateway->execute($token);
            $this->gateway->execute(new GetHumanStatus($token->getToken()));
        } catch (LogicException $e) {
            throw new HttpResponse($e->getMessage(), 400, ['Content-Type' => 'text/plain']);
        }
    }

    public function supports($request)
    {
        return $request instanceof Notify;
    }
}

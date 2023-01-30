<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Action;

use DachcomDigital\Payum\PostFinance\Flex\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());

        if (null === $model[Api::KEY_STATUS]) {
            $request->markNew();
            return;
        }

        switch ($model[Api::KEY_STATUS]) {
            case Api::STATUS_CAPTURED:
                $request->markCaptured();
                break;
            case Api::STATUS_FAILED:
                $request->markFailed();
                break;
            default:
                $request->markUnknown();
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}

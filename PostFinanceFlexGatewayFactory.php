<?php

namespace DachcomDigital\Payum\PostFinance\Flex;

use DachcomDigital\Payum\PostFinance\Flex\Action\Api\GetTransactionDetailsAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\Api\RenderIframeAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\Api\RenderLightboxAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\Api\TransactionExtenderAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\CaptureAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\ConvertPaymentAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\NotifyAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\NotifyNullAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\StatusAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\Api\CaptureOffsiteAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\Api\CreateTransactionAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\SyncAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class PostFinanceFlexGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name'  => 'postfinance_flex',
            'payum.factory_title' => 'PostFinance Checkout Flex',

            'payum.template.path' => '@PayumPostFinanceFlex/action',

            'payum.action.capture'         => new CaptureAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.notify_null'     => new NotifyNullAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.sync'            => new SyncAction(),

            'payum.action.api.get_transaction_details' => new GetTransactionDetailsAction(),
            'payum.action.api.transaction_extender'   => new TransactionExtenderAction(),
            'payum.action.api.initialize_transaction' => new CreateTransactionAction(),
            'payum.action.api.capture_offsite'        => new CaptureOffsiteAction(),
            'payum.action.api.render_lightbox'        => function (ArrayObject $config) {
                return new RenderLightboxAction($config['payum.template.path']);
            },
            'payum.action.api.render_iframe'        => function (ArrayObject $config) {
                return new RenderIframeAction($config['payum.template.path']);
            },
        ]);

        $config['payum.default_options'] = [
            'sandbox'           => false,
            'integrationType'   => 'paymentPage',
            'spaceId'           => '',
            'postFinanceSecret' => '',
            'postFinanceUserId' => ''
        ];

        $config['payum.required_options'] = [
            'spaceId',
            'integrationType',
            'postFinanceSecret',
            'postFinanceUserId'
        ];

        $config['payum.paths'] = array_replace([
            'PayumPostFinanceFlex' => __DIR__.'/Resources/views',
        ], $config['payum.paths'] ?: []);

        if (!empty($config['payum.api'])) {
            return;
        }

        $config->defaults($config['payum.default_options']);

        $config['payum.api'] = static function (ArrayObject $config) {

            $config->validateNotEmpty($config['payum.required_options']);

            return new Api($config->toUnsafeArray());
        };

    }
}

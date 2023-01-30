<?php

namespace DachcomDigital\Payum\PostFinance\Flex;

use DachcomDigital\Payum\PostFinance\Flex\Action\CapturePaymentPageAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\ConvertPaymentPageAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\NotifyAction;
use DachcomDigital\Payum\PostFinance\Flex\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use PostFinanceCheckout\Sdk\ApiClient;
use PostFinanceCheckout\Sdk\Service\TransactionPaymentPageService;
use PostFinanceCheckout\Sdk\Service\TransactionService;

class PostFinanceFlexGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([

            'payum.factory_name' => 'postfinance-flex',
            'payum.factory_title' => 'PostFinance Checkout Flex',
            'payum.action.capture' => new CapturePaymentPageAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.notify_null' => new NotifyAction(),
            'payum.action.convert_payment' => new ConvertPaymentPageAction(),
        ]);

        if (empty($config['payum.api'])) {
            $config['payum.default_options'] = [
                'environment' => Api::TEST,
                Api::KEY_SPACE_ID => '',
                Api::KEY_POSTFINANCE_SECRET => '',
                Api::KEY_POSTFINANCE_USER_ID => '',
                Api::KEY_SANDBOX => true,
            ];

            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [Api::KEY_SPACE_ID, Api::KEY_POSTFINANCE_SECRET, Api::KEY_POSTFINANCE_USER_ID];

            $config['payum.api'] = static function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $apiClient = new ApiClient($config[Api::KEY_POSTFINANCE_USER_ID], $config[Api::KEY_POSTFINANCE_SECRET]);

                return new Api(
                    [
                        Api::KEY_SANDBOX => $config['environment'] === Api::TEST,
                        Api::KEY_SPACE_ID => $config[Api::KEY_SPACE_ID],
                    ],
                    new TransactionPaymentPageService($apiClient),
                    new TransactionService($apiClient)
                );
            };
        }
    }
}

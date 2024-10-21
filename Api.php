<?php

namespace DachcomDigital\Payum\PostFinance\Flex;

use Payum\Core\Bridge\Spl\ArrayObject;
use PostFinanceCheckout\Sdk\ApiClient;
use PostFinanceCheckout\Sdk\Model\AddressCreate;
use PostFinanceCheckout\Sdk\Model\LineItemCreate;
use PostFinanceCheckout\Sdk\Model\LineItemType;
use PostFinanceCheckout\Sdk\Model\ModelInterface;
use PostFinanceCheckout\Sdk\Model\Transaction;
use PostFinanceCheckout\Sdk\Model\TransactionCreate;
use PostFinanceCheckout\Sdk\Service\TransactionIframeService;
use PostFinanceCheckout\Sdk\Service\TransactionLightboxService;
use PostFinanceCheckout\Sdk\Service\TransactionPaymentPageService;
use PostFinanceCheckout\Sdk\Service\TransactionService;

class Api
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getPaymentPageUrl(int $transactionId): string
    {
        return $this->getTransactionPaymentPageService()->paymentPageUrl($this->getSpaceId(), $transactionId);
    }

    public function getJavascriptUrl(int $transactionId): string
    {
        $transactionLightboxService = new TransactionLightboxService($this->getClient());

        return $transactionLightboxService->javascriptUrl($this->options['spaceId'], $transactionId);
    }

    public function getIframeUrl(int $transactionId): string
    {
        $transactionIframeService = new TransactionIframeService($this->getClient());

        return $transactionIframeService->javascriptUrl($this->options['spaceId'], $transactionId);
    }

    public function prepareTransaction(ArrayObject $details, string $returnUrl, string $notifyTokenHash): Transaction
    {
        $transactionExtender = [];
        if ($details->offsetExists('transaction_extender')) {
            $transactionExtender = $details['transaction_extender'];
        }

        $shippingAddress = $this->createPostFinanceModel(AddressCreate::class, $transactionExtender['shippingAddress'] ?? []);
        $billingAddress = $this->createPostFinanceModel(AddressCreate::class, $transactionExtender['billingAddress'] ?? []);

        $lineItem = $this->createPostFinanceModel(LineItemCreate::class, [
            'quantity'           => 1,
            'amountIncludingTax' => $transactionExtender['amount'] / 100,
            'taxes'              => $transactionExtender['totalTaxes'] ?? null,
            'uniqueId'           => $transactionExtender['id'],
            'name'               => $transactionExtender['id'],
            'sku'                => $transactionExtender['id'],
            'type'               => LineItemType::PRODUCT,
        ]);

        $transaction = $this->createPostFinanceModel(TransactionCreate::class, [
            'currency'                   => $transactionExtender['currency'] ?? null,
            'language'                   => $transactionExtender['language'] ?? null,
            'lineItems'                  => [$lineItem],
            'autoConfirmationEnabled'    => true,
            'failedUrl'                  => $this->getFailedUrl($returnUrl),
            'successUrl'                 => $this->getSuccessUrl($returnUrl),
            'shippingAddress'            => $shippingAddress,
            'billingAddress'             => $billingAddress,
            'metaData'                   => ['paymentToken' => $notifyTokenHash],
            'allowedPaymentMethodBrands' => $transactionExtender['allowedPaymentMethodBrands'] ?? [],
            'allowedPaymentMethodConfigurations' => $transactionExtender['allowedPaymentMethodConfigurations'] ?? [],
        ]);

        return $this->getTransactionService()->create($this->getSpaceId(), $transaction);
    }

    public function getEntity($entityId): ModelInterface
    {
        return $this->getTransactionService()->read($this->getSpaceId(), $entityId);
    }

    protected function getSuccessUrl(string $url): string
    {
        return $this->getUrlWithStatus($url, 'success');
    }

    protected function getFailedUrl(string $url): string
    {
        return $this->getUrlWithStatus($url, 'failed');
    }

    public function createTransactionInfo(Transaction $transaction): array
    {
        $data = [];

        $ref = new \ReflectionClass($transaction);

        $invalidNames = [
            'getters'
        ];

        $metaData = $transaction->getMetaData();

        $data['meta_paymentToken'] = $metaData['paymentToken'] ?? null;

        foreach ($ref->getMethods() as $method) {

            $methodName = $method->getName();

            if (!$method->isPublic()) {
                continue;
            }

            if (in_array($methodName, $invalidNames, true)) {
                continue;
            }

            if (!str_starts_with($methodName, 'get')) {
                continue;
            }

            $value = $transaction->$methodName();

            if ($value === null) {
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $data[str_replace('get', '', $methodName)] = $value;
        }

        return $data;
    }

    protected function getTransactionPaymentPageService(): TransactionPaymentPageService
    {
        return new TransactionPaymentPageService($this->getClient());
    }

    protected function getTransactionService(): TransactionService
    {
        return new TransactionService($this->getClient());
    }

    protected function getClient(): ApiClient
    {
        return new ApiClient($this->options['postFinanceUserId'], $this->options['postFinanceSecret']);
    }

    protected function getUrlWithStatus(string $url, string $status): string
    {
        $combiner = str_contains($url, '?') ? '&' : '?';

        return sprintf('%s%s%s=%s', $url, $combiner, 'redirect_status', $status);
    }

    protected function isSandbox(): bool
    {
        return $this->options['sandbox'] ?? false;
    }

    protected function getSpaceId(): mixed
    {
        return $this->options['spaceId'];
    }

    public function getIntegrationType(): string
    {
        return $this->options['integrationType'];
    }

    protected function createPostFinanceModel(string $model, array $data): ModelInterface
    {
        $modelClass = new $model();
        foreach ($data as $key => $value) {
            $setter = sprintf('set%s', ucfirst($key));
            $modelClass->$setter($value);
        }

        return $modelClass;
    }

}

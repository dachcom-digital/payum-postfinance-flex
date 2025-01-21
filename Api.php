<?php

namespace DachcomDigital\Payum\PostFinance\Flex;

use Payum\Core\Bridge\Spl\ArrayObject;
use PostFinanceCheckout\Sdk\ApiClient;
use PostFinanceCheckout\Sdk\Model\LineItemCreate;
use PostFinanceCheckout\Sdk\Model\LineItemType;
use PostFinanceCheckout\Sdk\Model\ModelInterface;
use PostFinanceCheckout\Sdk\Model\Transaction;
use PostFinanceCheckout\Sdk\Model\TransactionCreate;
use PostFinanceCheckout\Sdk\ObjectSerializer;
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

    public function getIntegrationType(): string
    {
        return $this->options['integrationType'];
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
        $transactionConfig = [];
        $detailsArray = $details->toUnsafeArray();

        if ($details->offsetExists('transaction_extender')) {
            $transactionConfig = $detailsArray['transaction_extender'];
        }

        if (array_key_exists('transactionCreate', $transactionConfig)) {
            $transactionCreateObject = ObjectSerializer::deserialize($transactionConfig['transactionCreate'], TransactionCreate::class);
        } else {
            $transactionCreateObject = new TransactionCreate();
        }

        $this->setDefaultsToTransactionCreateObject(
            $transactionCreateObject,
            $transactionConfig,
            $returnUrl,
            $notifyTokenHash
        );

        return $this->getTransactionService()->create($this->getSpaceId(), $transactionCreateObject);
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

    private function setDefaultsToTransactionCreateObject(
        TransactionCreate $transactionCreateObject,
        array $transactionConfig,
        string $returnUrl,
        string $notifyTokenHash
    ): void {

        $defaults = [
            'autoConfirmationEnabled' => true,
            'currency'                => $transactionConfig['currency'] ?? null,
            'language'                => $transactionConfig['language'] ?? null,
            'failedUrl'               => $this->getFailedUrl($returnUrl),
            'successUrl'              => $this->getSuccessUrl($returnUrl),
            'metaData'                => function (mixed $storedValue) use ($notifyTokenHash) {

                $data = ['paymentToken' => $notifyTokenHash];

                if (!is_array($storedValue)) {
                    return $data;
                }

                return array_merge($storedValue, $data);
            }
        ];

        foreach ($defaults as $defaultKey => $defaultValue) {

            $getter = sprintf('get%s', ucfirst($defaultKey));
            $setter = sprintf('set%s', ucfirst($defaultKey));

            if (is_callable($defaultValue)) {
                $transactionCreateObject->$setter($defaultValue($transactionCreateObject->$getter()));
            } elseif ($transactionCreateObject->$getter() === null) {
                $transactionCreateObject->$setter($defaultValue);
            }
        }

        if (empty($transactionCreateObject->getLineItems())) {

            /** @var LineItemCreate $defaultLineItem */
            $defaultLineItem = $this->createDefaultLineItem($transactionConfig);

            $transactionCreateObject->setLineItems([
                $defaultLineItem
            ]);
        }
    }

    private function createDefaultLineItem(array $data): ModelInterface
    {
        $lineItem = new LineItemCreate();

        $lineItem
            ->setQuantity(1)
            ->setAmountIncludingTax($data['amount'] / 100)
            ->setTaxes($data['totalTaxes'] ?? null)
            ->setUniqueId($data['id'])
            ->setName($data['id'])
            ->setSku($data['id'])
            ->setType(LineItemType::PRODUCT);

        return $lineItem;
    }
}

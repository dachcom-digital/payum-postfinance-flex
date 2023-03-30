<?php

namespace DachcomDigital\Payum\PostFinance\Flex;

use Payum\Core\Bridge\Spl\ArrayObject;
use PostFinanceCheckout\Sdk\ApiClient;
use PostFinanceCheckout\Sdk\Model\LineItemCreate;
use PostFinanceCheckout\Sdk\Model\LineItemType;
use PostFinanceCheckout\Sdk\Model\ModelInterface;
use PostFinanceCheckout\Sdk\Model\Transaction;
use PostFinanceCheckout\Sdk\Model\TransactionCreate;
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

    public function prepareTransaction(ArrayObject $details, string $returnUrl, string $tokenHash): Transaction
    {
        $transactionExtender = [];
        if ($details->offsetExists('transaction_extender')) {
            $transactionExtender = $details['transaction_extender'];
        }

        $lineItem = new LineItemCreate();
        $lineItem->setQuantity(1);
        $lineItem->setAmountIncludingTax(($transactionExtender['amount']) / 100);
        $lineItem->setUniqueId($transactionExtender['id']);
        $lineItem->setName($transactionExtender['id']);
        $lineItem->setType(LineItemType::PRODUCT);
        $lineItem->setSku($transactionExtender['id']);

        $transaction = new TransactionCreate();
        $transaction->setCurrency($transactionExtender['currency']);
        $transaction->setLanguage($transactionExtender['language'] ?? null);
        $transaction->setLineItems([$lineItem]);
        $transaction->setAutoConfirmationEnabled(true);
        $transaction->setFailedUrl($this->getFailedUrl($returnUrl));
        $transaction->setSuccessUrl($this->getSuccessUrl($returnUrl));
        $transaction->setMetaData(['paymentToken' => $tokenHash]);

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
        return sprintf('%s?%s=%s', $url, 'redirect_status', $status);
    }

    protected function isSandbox(): bool
    {
        return $this->options['sandbox'] ?? false;
    }

    protected function getSpaceId(): mixed
    {
        return $this->options['spaceId'];
    }
}

<?php

namespace DachcomDigital\Payum\PostFinance\Flex;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use Payum\Core\Model\Payment;
use PostFinanceCheckout\Sdk\Model\AddressCreate;
use PostFinanceCheckout\Sdk\Model\CompletionLineItemCreate;
use PostFinanceCheckout\Sdk\Model\LineItem;
use PostFinanceCheckout\Sdk\Model\LineItemCreate;
use PostFinanceCheckout\Sdk\Model\LineItemType;
use PostFinanceCheckout\Sdk\Model\ModelInterface;
use PostFinanceCheckout\Sdk\Model\TransactionCreate;
use PostFinanceCheckout\Sdk\Service\TransactionPaymentPageService;
use PostFinanceCheckout\Sdk\Service\TransactionService;

class Api
{
    const KEY_SPACE_ID = 'spaceId';
    const KEY_STATUS = 'STATUS';
    const KEY_SANDBOX ='sandbox';
    const KEY_RETURN_URL = 'returnUrl';
    const KEY_POSTFINANCE_SECRET = 'postFinanceSecret';
    const KEY_POSTFINANCE_USER_ID = 'postFinanceUserId';
    const STATUS_CAPTURED = 'captured';
    const STATUS_FAILED = 'failed';
    const META_DATA_KEY_PAYMENT_TOKEN = 'paymentToken';
    public const TEST = 'test';
    public const PRODUCTION = 'production';

    const PAYMENT_PAGE_REQUIRED_FIELDS = [
        self::KEY_SPACE_ID
    ];

    const TRANSACTION_CREATE_REQUIRED_FIELDS = [
        self::KEY_SPACE_ID,
    ];

    private ArrayObject $options;
    private TransactionPaymentPageService $paymentPageService;
    private TransactionService $transactionService;

    public function __construct(array $options, TransactionPaymentPageService $paymentPageService, TransactionService $transactionService)
    {
        $this->paymentPageService = $paymentPageService;
        $this->options = ArrayObject::ensureArrayObject($options);
        $this->transactionService = $transactionService;
    }

    public function getPaymentPageUrl(int $transactionId): string
    {
        $this->options->validateNotEmpty(static::PAYMENT_PAGE_REQUIRED_FIELDS);

        if ($this->options->get(self::KEY_SANDBOX, true)) {
            if ($this->options->get(self::KEY_RETURN_URL, false)) {
                return $this->getSuccessUrl($this->options[self::KEY_RETURN_URL]);
            }
            throw new LogicException(sprintf('No return url in sandbox mode set. Call %s::%s first', self::class, 'prepareTransaction'));
        }

        return $this->paymentPageService->paymentPageUrl($this->options[self::KEY_SPACE_ID], $transactionId);
    }

    /**
     * @param LineItemCreate[] $lineItems
     */
    public function prepareTransaction(ArrayObject $details, string $returnUrl, string $tokenHash): int
    {
        $this->options->validateNotEmpty(static::TRANSACTION_CREATE_REQUIRED_FIELDS);

        if ($this->options->get(self::KEY_SANDBOX, true)) {
            $this->options[self::KEY_RETURN_URL] = $returnUrl;

            return 0;
        }

        $lineItem = new LineItemCreate();
        $lineItem->setQuantity(1);
        $lineItem->setAmountIncludingTax($details['AMOUNT'] / 100);
        $lineItem->setUniqueId($details['ORDERID']);
        $lineItem->setName($details['ORDERID']);
        $lineItem->setType(LineItemType::PRODUCT);
        $lineItem->setSku($details['ORDERID']);

        return $this->transactionService->create(
            $this->options[self::KEY_SPACE_ID],
            $this->createTransaction(
                $lineItem,
                'CHF', //$details['CURRENCY'],
                $returnUrl,
                $tokenHash)
        )->getId();
    }

    /**
     * @param LineItemCreate[] $lineItems
     */
    protected function createTransaction(LineItemCreate $completionLineItem, string $currency, string $returnUrl, string $tokenHash): TransactionCreate
    {
        $transaction = new TransactionCreate();
        $transaction->setCurrency($currency);
        $transaction->setLineItems([$completionLineItem]);
        $transaction->setAutoConfirmationEnabled(true);
        $transaction->setFailedUrl($this->getFailedUrl($returnUrl));
        $transaction->setSuccessUrl($this->getSuccessUrl($returnUrl));
        $transaction->setMetaData(
            [
                static::META_DATA_KEY_PAYMENT_TOKEN  => $tokenHash
            ]
        );

        return $transaction;
    }

    public function getEntity($entityId): ModelInterface
    {
       return  $this->transactionService->read($this->options[self::KEY_SPACE_ID], $entityId);
    }

    protected function getUrlWithStatus(string $url, string $status): string
    {
        return sprintf('%s?%s=%s', $url, self::KEY_STATUS, $status);
    }

    protected function getSuccessUrl(string $url): string
    {
        return $this->getUrlWithStatus($url, self::STATUS_CAPTURED);
    }

    protected function getFailedUrl(string $url): string
    {
        return $this->getUrlWithStatus($url, self::STATUS_FAILED);
    }
}

<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Transaction;

use PostFinanceCheckout\Sdk\Model\TransactionCreate;
use PostFinanceCheckout\Sdk\ObjectSerializer;

class Transaction
{
    protected mixed $id;
    protected int|float $amount;
    protected ?string $currency;
    protected ?string $language = null;
    protected ?array $totalTaxes = null;

    public function __construct(protected TransactionCreate $transactionCreate)
    {
    }

    public function getTransactionCreateObject(): TransactionCreate
    {
        return $this->transactionCreate;
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getAmount(): float|int
    {
        return $this->amount;
    }

    public function setAmount(float|int $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getTotalTaxes(): ?array
    {
        return $this->totalTaxes;
    }

    public function setTotalTaxes(?array $totalTaxes): void
    {
        $this->totalTaxes = $totalTaxes;
    }

    public function toArray(): array
    {
        $transactionCreateJson = ObjectSerializer::json_encode(
            ObjectSerializer::sanitizeForSerialization(
                $this->getTransactionCreateObject()
            )
        );

        return [
            'id'                => $this->getId(),
            'amount'            => $this->getAmount(),
            'currency'          => $this->getCurrency(),
            'language'          => $this->getLanguage(),
            'totalTaxes'        => $this->getTotalTaxes(),
            'transactionCreate' => ObjectSerializer::json_decode($transactionCreateJson)
        ];
    }
}

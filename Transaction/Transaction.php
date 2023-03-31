<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Transaction;

class Transaction
{
    protected mixed $id;
    protected int|float $amount;
    protected ?string $currency;
    protected ?string $language = null;

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

    public function toArray(): array
    {
        return [
            'id'       => $this->getId(),
            'amount'   => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'language' => $this->getLanguage(),
        ];

    }
}
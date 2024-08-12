<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Transaction;

use PostFinanceCheckout\Sdk\Model\AddressCreate;
use PostFinanceCheckout\Sdk\ObjectSerializer;

class Transaction
{
    protected mixed $id;
    protected int|float $amount;
    protected ?string $currency;
    protected ?string $language = null;
    protected ?array $totalTaxes = null;
    protected ?array $allowedPaymentMethodBrands = null;
    protected ?array $allowedPaymentMethodConfigurations = null;

    protected ?AddressCreate $shippingAddress = null;
    protected ?AddressCreate $billingAddress = null;

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

    public function getAllowedPaymentMethodBrands(): ?array
    {
        return $this->allowedPaymentMethodBrands;
    }

    public function setAllowedPaymentMethodBrands(?array $allowedPaymentMethodBrands): void
    {
        $this->allowedPaymentMethodBrands = $allowedPaymentMethodBrands;
    }

    public function getAllowedPaymentMethodConfigurations(): ?array
    {
        return $this->allowedPaymentMethodConfigurations;
    }

    public function setAllowedPaymentMethodConfigurations(?array $allowedPaymentMethodConfigurations): void
    {
        $this->allowedPaymentMethodConfigurations = $allowedPaymentMethodConfigurations;
    }

    public function getShippingAddress(): ?AddressCreate
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?AddressCreate $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getBillingAddress(): ?AddressCreate
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?AddressCreate $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function toArray(): array
    {
        $data = [
            'id'                                 => $this->getId(),
            'amount'                             => $this->getAmount(),
            'currency'                           => $this->getCurrency(),
            'language'                           => $this->getLanguage(),
            'totalTaxes'                         => $this->getTotalTaxes(),
            'allowedPaymentMethodBrands'         => $this->getAllowedPaymentMethodBrands(),
            'allowedPaymentMethodConfigurations' => $this->getAllowedPaymentMethodConfigurations(),
            'shippingAddress'                    => $this->shippingAddress === null ? [] : (array) ObjectSerializer::sanitizeForSerialization($this->shippingAddress),
            'billingAddress'                     => $this->billingAddress === null ? [] : (array) ObjectSerializer::sanitizeForSerialization($this->billingAddress)
        ];

        return array_filter($data, static function ($row) {
            return $row !== null;
        });
    }
}

<?php

namespace DachcomDigital\Payum\PostFinance\Flex\Request\Api;

use DachcomDigital\Payum\PostFinance\Flex\Transaction\Transaction;
use Payum\Core\Request\Generic;

class TransactionExtender extends Generic
{
    protected Transaction $transaction;

    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }

    public function toArray(): array
    {
        return $this->transaction->toArray();
    }
}

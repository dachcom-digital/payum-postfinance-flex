# Payum PostFinance Flex
Payum Gateway For [PostFinance Checkout](https://checkout.postfinance.ch)

## Requirements
- PHP 8.0+
- [Payum](https://github.com/Payum/Payum)

## Information
This extension currently **does not** support multiple line items. 
It only creates one line item which contains all the total information of the given order.

***

## BackOffice

### Environments
Use multiple spaces to determine test/production.

### Webhook
You need to define a global webhook: `https://your-domain.com/payment/notify/unsafe/[YOUR_POSTFINANCE_FLEX_GATEWAY_NAME]`

***

## Changelog

### 2.0.0
- [NEW FEATURE | **BC BREAK**] `TransactionCreate` Object added: The object `DachcomDigital\Payum\PostFinance\Flex\Transaction\Transaction` 
now provides a `getTransactionCreateObject` method, which gives you full control over the transaction data. 
Therefor we've removed several methods within the `Transaction` object itself.
Use to the `TransactionCreate` object directly, to add additional data.
  - Removed methods:
    - `(get|set)AllowedPaymentMethodBrands`
    - `(get|set)AllowedPaymentMethodConfigurations`
    - `(get|set)ShippingAddress`
    - `(get|set)BillingAddress`
  - Signature of `transaction_extender` within the payment `details` has changed:
    - `transactionCreate` added
    - `allowedPaymentMethodBrands`, `allowedPaymentMethodConfigurations`, `shippingAddress`, `billingAddress` removed

***

### 1.3.0
- introduce `GetTransactionDetailsAction`

### 1.2.0
- dependency `postfinancecheckout/sdk:^4.1` added
- added `totalTaxes` to transaction extender to allow tax rates submission to line item
### 1.1.1
- change payment state to `authorized` if transaction state is `completed`
### 1.1.0
- add integration types
- Add `allowedPaymentMethodConfigurations` option
### 1.0.4
- keep payment state at `new` even if postfinance status is `confirmed`. Reason: PF state `confirmed` only means, that the payment itself cannot be altered anymore and instantly triggers, as soon the payment transaction has been dispatched.
### 1.0.3
- allow address data submission, use abstract model creation
### 1.0.2
- use dedicated notify token for webhooks to prevent invalidation before completing payment state submission
### 1.0.1
- Add `allowedPaymentMethodBrands` option

## Copyright and License
Copyright: [DACHCOM.DIGITAL](https://www.dachcom-digital.ch)
For licensing details please visit [LICENSE.md](LICENSE.md)

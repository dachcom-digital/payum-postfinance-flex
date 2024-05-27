# Payum PostFinance Flex
Payum Gateway For [PostFinance Checkout](https://checkout.postfinance.ch)

## Requirements
- PHP 8.0+
- [Payum](https://github.com/Payum/Payum)

## BackOffice

### Environments
Use multiple spaces to determine test/production.

### Webhook
You need to define a global webhook: `https://your-domain.com/payment/notify/unsafe/[YOUR_POSTFINANCE_FLEX_GATEWAY_NAME]`

## Changelog
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

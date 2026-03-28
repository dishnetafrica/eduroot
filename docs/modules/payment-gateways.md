# Payment Gateways

EduRoot ships with **28 payment gateway integrations**. All gateways are available for both the student fee portal and the online admission flow.

---

## Supported Gateways

| Gateway | Region | Controller (fees) | Controller (admission) |
|---|---|---|---|
| Razorpay | India | `user/gateway/Razorpay.php` | `onlineadmission/Razorpay.php` |
| Stripe | Global | `user/gateway/Stripe.php` | `onlineadmission/Stripe.php` |
| PayU | India | `user/gateway/Payu.php` | `onlineadmission/Payu.php` |
| Paytm | India | `user/gateway/Paytm.php` | `onlineadmission/Paytm.php` |
| Instamojo | India | `user/gateway/Instamojo.php` | `onlineadmission/Instamojo.php` |
| Cashfree | India | `user/gateway/Cashfree.php` | `onlineadmission/Cashfree.php` |
| CCAvenue | India | `user/gateway/Ccavenue.php` | `onlineadmission/Ccavenue.php` |
| Flutterwave | Africa | `user/gateway/Flutterwave.php` | `onlineadmission/Flutterwave.php` |
| Paystack | Africa | `user/gateway/Paystack.php` | `onlineadmission/Paystack.php` |
| iPay Africa | Africa | `user/gateway/Ipayafrica.php` | `onlineadmission/Ipayafrica.php` |
| Pesapal | Africa | `user/gateway/Pesapal.php` | `onlineadmission/Pesapal.php` |
| MomoPay | Africa | `user/gateway/Momopay.php` | `onlineadmission/Momopay.php` |
| DpoPay | Africa | `user/gateway/Dpopay.php` | `onlineadmission/Dpopay.php` |
| Kowri | Africa | `user/gateway/Kowri.php` | `onlineadmission/Kowri.php` |
| Ihela | Africa | `gateway_ins/Ihela.php` | — |
| Midtrans | Indonesia | `user/gateway/Midtrans.php` | `onlineadmission/Midtrans.php` |
| Toyyibpay | Malaysia | `user/gateway/Toyyibpay.php` | `onlineadmission/Toyyibpay.php` |
| Billplz | Malaysia | `user/gateway/Billplz.php` | `onlineadmission/Billplz.php` |
| JazzCash | Pakistan | `user/gateway/Jazzcash.php` | `onlineadmission/Jazzcash.php` |
| Onepay | Sri Lanka | `user/gateway/Onepay.php` | `onlineadmission/Onepay.php` |
| PayPal | Global | `user/gateway/Paypal.php` | `onlineadmission/Paypal.php` |
| Skrill | Global | `user/gateway/Skrill.php` | `onlineadmission/Skrill.php` |
| Mollie | Europe | `user/gateway/Mollie.php` | `onlineadmission/Mollie.php` |
| 2Checkout | Global | `user/gateway/Twocheckout.php` | `onlineadmission/Twocheckout.php` |
| SSLCommerz | Bangladesh | `user/gateway/Sslcommerz.php` | `onlineadmission/Sslcommerz.php` |
| PayFast | South Africa | `user/gateway/Payfast.php` | `onlineadmission/Payfast.php` |
| PayHere | Sri Lanka | `user/gateway/Payhere.php` | `onlineadmission/Payhere.php` |
| Checkout.com | Global | `user/gateway/Checkout.php` | `onlineadmission/Checkout.php` |
| Walkingm | — | `user/gateway/Walkingm.php` | `onlineadmission/Walkingm.php` |

---

## Gateway Configuration

Each gateway is configured per school in **Admin → Payment Settings** (`payment_settings` table). Fields typically include:

- `api_key` / `key_id`
- `secret_key` / `api_secret`
- `merchant_id` (where applicable)
- `is_active` — enables/disables the gateway in the student portal
- `is_sandbox` — test vs live mode

---

## Payment Flow

```
Student Portal                     EduRoot Server                    Gateway
      │                                   │                              │
      │  1. Select "Pay Online"           │                              │
      │ ─────────────────────────────►    │                              │
      │                                   │  2. Create payment record    │
      │                                   │     in student_fees_processing│
      │  3. Redirect to gateway           │                              │
      │ ◄─────────────────────────────    │                              │
      │                                   │                              │
      │  4. User completes payment on gateway page                       │
      │                                   │                              │
      │                                   │  ◄── 5. Webhook POST         │
      │                                   │         /webhooks/{gateway}  │
      │                                   │                              │
      │                                   │  6. Verify + mark paid       │
      │                                   │     create student_fees_deposite
      │                                   │     send notification        │
      │  7. Redirect to success page      │                              │
      │ ◄─────────────────────────────    │                              │
```

---

## Adding a New Gateway

See the full guide: [Adding a Payment Gateway](../development/new-gateway.md)

Quick summary:
1. Create `application/controllers/user/gateway/NewGateway.php`
2. Create `application/controllers/onlineadmission/NewGateway.php`
3. Add gateway settings fields to `payment_settings` table
4. Register gateway name in `Paymentsettings.php`
5. Add webhook handler in `Webhooks.php` (or gateway controller)
6. Add gateway config view in `views/admin/paymentsettings/`

---

## Instalment Gateway (`gateway_ins`)

Some gateways support instalment payments. These are handled separately via `controllers/gateway_ins/`:

- `Ihela.php`
- `Payfast.php`
- `Payhere.php`
- `Skrill.php`
- `Toyyibpay.php`
- `Twocheckout.php`

Instalment records are stored in `gateway_ins` with individual payment tracking in `gateway_ins_response`.

---

## Webhook Security

Webhooks are received at `POST /webhooks/{gateway_name}`. Each gateway uses its own verification mechanism:
- Razorpay: HMAC-SHA256 signature verification
- Stripe: Stripe-Signature header + webhook secret
- Others: Varies by gateway — check the individual controller

Gateway callback data is logged in `gateway_ins_response` for debugging.

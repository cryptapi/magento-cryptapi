![CryptAPI](https://i.imgur.com/IfMAa7E.png)

# CryptAPI – Crypto Payment Gateway for Magento
Accept Bitcoin, Ethereum, USDT, USDC, Solana and other cryptocurrency payments in Magento. No sign-up: funds go straight to your wallet.

### Requirements:

```
Magento >= 2.3.5
```

### Description

Accept payments in Bitcoin, Ethereum, Bitcoin Cash, Litecoin, BNB, USDT, SHIB, DOGE and many more directly to your crypto wallet, without any sign-ups or lengthy processes.
All you need is to provide your crypto address and verify your domain once.

#### Allow users to pay with crypto directly on your store

The CryptAPI extension enables your Magento store to receive payments in cryptocurrency, with a simple setup and no sign-ups required.

#### Accepted cryptocurrencies & tokens include:

* (BTC) Bitcoin
* (ETH) Ethereum
* (BCH) Bitcoin Cash
* (LTC) Litecoin
* (TRX) Tron
* (BNB) Binance Coin
* (USDT) USDT
* (USDC) USDC
* (SHIB) Shiba Inu
* (DOGE) Dogecoin
* (POL) Polygon
* (SOL) Solana

among many others, for a full list of the supported cryptocurrencies and tokens, check [this page](https://cryptapi.io/cryptocurrencies/).

Stablecoins include USDT on TRC20 (Tron), ERC20, BEP20, Polygon, Solana, Base and TON, and USDC on ERC20, BEP20, Polygon, Solana, Base and Arbitrum.

#### Features

* QR codes with or without the amount
* Handles partial payments and shows a payment history on the payment page
* Emails the customer a link to the payment page
* Cancels unpaid orders after a time you choose
* Can add service and blockchain fees at checkout
* Light and dark color schemes

#### Auto-value conversion

CryptAPI plugin will attempt to automatically convert the value you set on your store to the cryptocurrency your customer chose.
Exchange rates are fetched every 5 minutes.

Supported currencies for automatic exchange rates are:

* (USD) United States Dollar
* (EUR) Euro
* (GBP) Great Britain Pound
* (JPY) Japanese Yen
* (CNY) Chinese Yuan
* (INR) Indian Rupee
* (CAD) Canadian Dollar
* (HKD) Hong Kong Dollar
* (BRL) Brazilian Real
* (DKK) Danish Krone
* (MXN) Mexican Peso
* (AED) United Arab Emirates Dirham

If your Magento's currency is none of the above, the exchange rates will default to USD.
If you're using Magento in a different currency not listed here and need support, please [contact us](https://cryptapi.io) via our live chat.

#### Why choose CryptAPI?

CryptAPI has no setup fees, no monthly fees, no hidden costs, and you don't even need to sign-up!
Simply set your crypto addresses, verify your domain and you're ready to go. As soon as your customers pay we forward your earnings directly to your own wallet.

CryptAPI has a low 1% fee on the transactions processed. No hidden costs.
For more info on our fees [click here](https://cryptapi.io/fees/).

### Installation

1. Upload code to the folder app/code/Cryptapi/Cryptapi

2. Enter following commands to install module:
```bash
php bin/magento module:enable Cryptapi_Cryptapi
php bin/magento setup:upgrade 
php bin/magento setup:di:compile 
php bin/magento cache:enable cryptapi_cryptocurrencies
```

3. Enable and configure CryptAPI in Magento Admin under Stores -> Configuration -> Sales -> Payment Methods -> CryptAPI


### Configuration


1. Access your Magento Admin Panel 
2. Go to Stores -> Configuration -> Sales -> Payment Methods -> CryptAPI 
3. Activate the payment method (if inactive) 
4. Set the name you wish to show your users on Checkout (for example: "Cryptocurrency") 
5. Under Cryptocurrencies, click "Add More" and pick each cryptocurrency you wish to accept 
6. Input your addresses to the cryptocurrencies you selected. This is where your funds will be sent to, so make sure the addresses are correct. 
7. Click "Save Config" 
8. Verify your store's domain at [cryptapi.io/verify_domain](https://cryptapi.io/verify_domain/), with a DNS TXT record or an HTTPS file. You only need to do this once, but customers can't pay with CryptAPI until you do. When you're done, set "Domain verified" to Yes and save to hide the notice on the settings page.
9. All done!

### Frequently Asked Questions

#### Do I need an API key?

No. You just need to insert your crypto address of the cryptocurrencies you wish to accept and verify your domain once (see below). Whenever a customer pays, the money will be automatically and instantly forwarded to your address.

#### Do I need to verify my domain?

Yes. CryptAPI only creates payment addresses for verified domains, so customers can't pay until you verify the domain your store runs on. You only need to do it once, at [cryptapi.io/verify_domain](https://cryptapi.io/verify_domain/), with a DNS TXT record or an HTTPS file. You don't need an account.

A DNS record also covers your subdomains. An HTTPS file covers only the exact host, so `www.example.com` and `example.com` count as two domains. If your store views run on more than one domain, verify each one. Payment addresses you already created keep working. Our [step-by-step guide](https://docs.cryptapi.io/kb/developers/how-to-verify-your-callback-domain) shows how.

#### How long do payments take before they're confirmed?

This depends on the cryptocurrency you're using. Bitcoin usually takes up to 11 minutes, Ethereum usually takes less than a minute.

#### Is there a minimum for a payment?

Yes, the minimums change according to the chosen cryptocurrency and can be checked [here](https://cryptapi.io/cryptocurrencies/).
If the Magento order total is below the chosen cryptocurrency's minimum, an error is raised to the user.

#### Where can I find more documentation on your service?

You can find more documentation about our service on our [get started](https://cryptapi.io/get-started) page, our [technical documentation](https://cryptapi.io/docs/) page or our [resources](https://cryptapi.io/resources/) page.
If there's anything else you need that is not covered on those pages, please get in touch with us, we're here to help you!

#### Where can I get support? 

The easiest and fastest way is via our live chat on our [website](https://cryptapi.io) or via our [contact form](https://cryptapi.io/contacts/).

### Changelog 

#### 1.0
* Initial release.

#### 2.0
* New coins.
* Updated codebase.
* New API URL.
* UI Improvements
* Minor Bug Fixes

#### 3.0
* New settings and color schemes to fit dark mode
* New settings to add CryptAPI's services fees to the checkout
* New settings to add blockchain fees to the checkout
* Upgrade the settings
* Added a history of transactions to the order payment page
* Better handling of partial payments
* Disable QR Code with value in certain currencies due to some wallets not supporting it
* Minor fixes
* UI Improvements

#### 3.0.1
* Minor fixes

#### 3.0.2
* Minor fixes

#### 3.0.3
* Minor fixes

#### 3.1
* Support CryptAPI Pro
* Minor fixes

#### 3.1.1
* Minor fixes

#### 3.2.0
* Support for BlockBee
* Add e-mail with link for order payment
* Minor fixes

#### 3.2.1
* Minor bugfixes and improvements.

#### 3.2.2
* Minor bugfixes and improvements.

#### 3.2.3
* Add new choices for order cancellation.

#### 3.2.4
* Security fixes.
* Minor bugfixes and improvements.

#### 3.2.5
* Domain verification notice.

#### 3.2.6
* Bugfixes.

### Upgrade Notice
* After updating, run `php bin/magento setup:upgrade` and `php bin/magento setup:di:compile`, then flush the cache.
* The server must reach api.cryptapi.io over HTTPS with a valid certificate.
* Stores behind Fastly: check unpaid orders placed before the update by hand, as their callbacks may be refused.
* From 3.2.5, the settings page shows a domain verification notice until you set "Domain verified" to Yes. If you haven't verified your domain yet, see [Do I need to verify my domain?](#do-i-need-to-verify-my-domain)

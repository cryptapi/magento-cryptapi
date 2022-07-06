![CryptAPI](https://i.imgur.com/IfMAa7E.png)

# CryptAPI Payment Gateway for Magento
Accept cryptocurrency payments on your Magento store

### Requirements:

```
Magento >= 2.4
Magento >= 2.3.5
```

### Description

Accept payments in Bitcoin, Bitcoin Cash, Litecoin, Ethereum, Monero and IOTA directly to your crypto wallet, without any sign-ups or lengthy processes.
All you need is to provide your crypto address.

#### Allow users to pay with crypto directly on your store

The CryptAPI extension enables your Magento store to get receive payments in cryptocurrency, with a simple setup and no sign-ups required.

#### Accepted cryptocurrencies & tokens include:

* (BTC) Bitcoin
* (ETH) Ethereum
* (BCH) Bitcoin Cash
* (LTC) Litecoin
* (XMR) Monero
* (TRX) Tron
* (BNB) Binance Coin
* (USDT) USDT

CryptAPI plugin will attempt to automatically convert the value you set on your store to the cryptocurrency your customer chose.
Exchange rates are fetched every 5 minutes.

### Supported currencies for automatic exchange rates are:

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
Simply set your crypto addresses and you're ready to go. As soon as your customers pay we forward your earnings directly to your own wallet.

CryptAPI has a low 1% fee on the transactions processed. No hidden costs.
For more info on our fees [click here](https://cryptapi.io/get_started/#fees)

### Installation

1. Upload code to the folder app/code/Cryptapi/Cryptapi

2. Enter following commands to install module:
```bash
php bin/magento module:enable Cryptapi_Cryptapi
php bin/magento setup:upgrade 
php bin/magento setup:di:compile 
php bin/magento setup:static-content:deploy -f 
php bin/magento cache:flush 
php bin/magento cache:enable cryptapi_cryptocurrencies
```

4. Enable and configure CryptApi in Magento Admin under Stores -> Configuration-> Sales -> Payment Methods -> CryptApi


### Configuration


1. Access your Magento Admin Panel 
2. Go to Stores -> Configuration -> Sales -> Payment Methods -> CryptAPI 
3. Activate the payment method (if inactive) 
4. Set the name you wish to show your users on Checkout (for example: "Cryptocurrency") 
5. Select which cryptocurrencies you wish to accept (control + click to select many) 
6. Input your addresses to the cryptocurrencies you selected. This is where your funds will be sent to, so make sure the addresses are correct. 
7. Click "Save Changes" 
8. All done!

### Frequently Asked Questions

#### Do I need an API key?

No. You just need to insert your crypto address of the cryptocurrencies you wish to accept. Whenever a customer pays, the money will be automatically and instantly forwarded to your address.

#### How long do payments take before they're confirmed?

This depends on the cryptocurrency you're using. Bitcoin usually takes up to 11 minutes, Ethereum usually takes less than a minute.

#### Is there a minimum for a payment?

Yes, the minimums change according to the chosen cryptocurrency and can be checked [here](https://cryptapi.io/get_started/#fees).
If the Magento order total is below the chosen cryptocurrency's minimum, an error is raised to the user.

#### Where can I find more documentation on your service?

You can find more documentation about our service on our [get started](https://cryptapi.io/get_started) page, our [technical documentation](https://cryptapi.io/docs/) page or our [resources](https://cryptapi.io/resources/) page.
If there's anything else you need that is not covered on those pages, please get in touch with us, we're here to help you!

#### Where can I get support? 

The easiest and fastest way is via our live chat on our [website](https://cryptapi.io) or via our [contact form](https://cryptapi.io/contact/).

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

### Upgrade Notice
* No breaking changes.

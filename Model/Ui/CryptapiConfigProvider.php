<?php

namespace Cryptapi\Cryptapi\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Cryptapi\Cryptapi\lib\CryptAPIHelper;

class CryptapiConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cryptapi';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        PaymentHelper                                      $paymentHelper,
        Escaper                                            $escaper,
        \Magento\Framework\App\CacheInterface              $cache,
        \Magento\Framework\Serialize\SerializerInterface   $serializer
    )
    {
        $this->escaper = $escaper;
        $this->scopeConfig = $scopeConfig;
        $this->paymentHelper = $paymentHelper;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    public function getConfig() : array
    {
        return [
            'payment' => [
                self::CODE => [
                    'cryptocurrencies' => $this->getCryptocurrencies(),
                    'instructions' => $this->getInstructions(),
                ]
            ]
        ];
    }

    public function getInstructions(): \Magento\Framework\Phrase
    {
        return __('Pay with cryptocurrency');
    }

    public function getCryptocurrencies(): array
    {
        $cacheKey = \Cryptapi\Cryptapi\Model\Cache\Type::TYPE_IDENTIFIER;
        $cacheTag = \Cryptapi\Cryptapi\Model\Cache\Type::CACHE_TAG;

        if (empty($this->cache->load($cacheKey)) || !$this->serializer->unserialize($this->cache->load($cacheKey))) {
            $this->cache->save(
                $this->serializer->serialize($this->serializer->serialize(CryptAPIHelper::get_supported_coins())),
                $cacheKey,
                [$cacheTag],
                86400
            );
        }

        $available_cryptos = $this->serializer->unserialize($this->cache->load($cacheKey));

        $selected = json_decode($this->scopeConfig->getValue('payment/cryptapi/supported_cryptocurrencies/cryptocurrencies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), true);

        $apiKey = $this->scopeConfig->getValue('payment/cryptapi/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $output = [];

        if (!empty($selected)) {
            foreach (json_decode($available_cryptos) as $ticker => $coin) {
                foreach ($selected as $uuid => $data) {
                    if (!empty($data['cryptocurrency_address'] || !empty($apiKey))) { // Check for API Key / Address configuration. Prevents unexpected errors.
                        if ($ticker == $data['cryptocurrency'])
                            $output[] = [
                                'value' => $data['cryptocurrency'],
                                'type' => $coin,
                            ];
                    }
                }
            }
        }

        return $output;
    }
}

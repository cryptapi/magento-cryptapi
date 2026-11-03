<?php

namespace Cryptapi\Cryptapi\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Cryptapi\Cryptapi\lib\CryptAPIHelper;

class CryptapiConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cryptapi';

    protected $scopeConfig;
    protected $paymentHelper;
    protected $escaper;
    protected $cache;
    protected $serializer;

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

        $cached = $this->cache->load($cacheKey);
        $available_cryptos = $cached ? $this->serializer->unserialize($cached) : null;

        if (empty($available_cryptos) || !is_array($available_cryptos)) {
            $available_cryptos = CryptAPIHelper::get_supported_coins();

            // A failed lookup is not cached, or checkout would stay broken until the cache expires.
            if (empty($available_cryptos)) {
                return [];
            }

            $this->cache->save(
                $this->serializer->serialize($available_cryptos),
                $cacheKey,
                [$cacheTag],
                86400
            );
        }

        $selected = json_decode((string)$this->scopeConfig->getValue('payment/cryptapi/supported_cryptocurrencies/cryptocurrencies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), true);

        $output = [];

        if (!empty($selected)) {
            foreach ($available_cryptos as $ticker => $coin) {
                foreach ($selected as $uuid => $data) {
                    if (!empty($data['cryptocurrency_address'])) { // Only coins with an address can be offered.
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

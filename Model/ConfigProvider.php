<?php

namespace Cryptapi\Cryptapi\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Cryptapi\Cryptapi\lib\CryptAPIHelper;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cryptapi';

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
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

    public function getConfig()
    {
        $config = [
            'payment' => array(
                self::CODE => array(
                    'cryptocurrencies' => $this->getCryptocurrencies(),
                    'instructions' => $this->getInstructions(),
                )
            )
        ];
        return $config;
    }

    public function getInstructions()
    {
        return __('Pay with cryptocurrency');
    }

    public function getCryptocurrencies()
    {
        $cacheKey = \Cryptapi\Cryptapi\Model\Cache\Type::TYPE_IDENTIFIER;
        $cacheTag = \Cryptapi\Cryptapi\Model\Cache\Type::CACHE_TAG;

        if (empty($this->cache->load($cacheKey))) {
            $this->cache->save(
                $this->serializer->serialize(json_encode(CryptAPIHelper::get_supported_coins())),
                $cacheKey,
                [$cacheTag],
                86400
            );
        }

        $selected = json_decode($this->scopeConfig->getValue('payment/cryptapi/supported_cryptocurrencies/cryptocurrencies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), true);

        $available_cryptos = $this->serializer->unserialize($this->cache->load($cacheKey));

        $output = [];

        foreach (json_decode($available_cryptos) as $ticker => $coin) {
            foreach ($selected as $uid => $data) {
                if ($ticker == $data['cryptocurrency'])
                    $output[] = [
                        'value' => $data['cryptocurrency'],
                        'type' => $coin,
                    ];
            }
        }

        return $output;
    }
}

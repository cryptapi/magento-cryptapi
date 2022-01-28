<?php

namespace Cryptapi\Cryptapi\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class Type extends TagScope
{
    const TYPE_IDENTIFIER = 'cryptapi_cryptocurrencies';

    const CACHE_TAG = 'CRYPTAPI_CRYPTOCURRENCIES';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(
        FrontendPool $cacheFrontendPool
    )
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}

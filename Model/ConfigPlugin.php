<?php

namespace Cryptapi\Cryptapi\Model;

use function _PHPStan_76800bfb5\RingCentral\Psr7\str;

class ConfigPlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    private $sortOrderArray = [];

    /**
     * Construct.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->request = $request;
    }

    /**
     * added validation for sort order around save action
     *
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure $proceed
     *
     * @return void
     */
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure                     $proceed
    )
    {
        $requestData = $this->request->getParams();
        $configSection = $requestData["section"] ?? "";

        if ($configSection == "payment") {
            $groups = $requestData["groups"] ?? [];

            if (!empty($groups)) {
                $cryptocurrencies = $groups["cryptapi"]["groups"]["supported_cryptocurrencies"]["fields"]["cryptocurrencies"]["value"];

                $cryptocurrencies_array = array_map(function($val){return $val['cryptocurrency'];}, array_filter($cryptocurrencies));

                if (count($cryptocurrencies_array) !== count(array_unique($cryptocurrencies_array, SORT_STRING))) {
                    throw new \Magento\Framework\Exception\AlreadyExistsException(
                        __('You can only add one address per cryptocurrency')
                    );
                }
            }
        }

        return $proceed();
    }

}

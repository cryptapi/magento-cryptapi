<?php

namespace Cryptapi\Cryptapi\Model\Config;

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
                $cryptocurrenciesArray = array_map(function ($val) {
                    return $val['cryptocurrency'];
                }, array_filter($cryptocurrencies));

                $hasEmptyAddr = false;

                $c = 0;
                foreach ($cryptocurrencies as $ticker => $addr) {
                    if($c < (count($cryptocurrencies) - 1)) {
                        if (empty($addr["cryptocurrency_address"])) {
                            $hasEmptyAddr = true;
                        }
                    }
                    $c++;
                }

                if ($hasEmptyAddr) {
                    throw new \Magento\Framework\Exception\AlreadyExistsException(
                        __("Please enter an address for each cryptocurrency.")
                    );
                }

                if (count($cryptocurrenciesArray) !== count(array_unique($cryptocurrenciesArray, SORT_STRING))) {
                    throw new \Magento\Framework\Exception\AlreadyExistsException(
                        __('You can only add one address per cryptocurrency')
                    );
                }
            }
        }

        return $proceed();
    }

}

<?php

namespace Cryptapi\Cryptapi\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;

class Mail extends AbstractHelper
{
    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        TransportBuilder      $transportBuilder,
        StateInterface        $inlineTranslation,
        LoggerInterface       $logger
    )
    {
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Send Mail
     *
     * @return $this
     *
     * @throws LocalizedException
     * @throws MailException
     */
    public function sendMail($order, $metadata)
    {
        $customerId = $order->getCustomerId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');

        $customerData = $objectManager->create('Magento\Customer\Model\Customer')->load($customerId);

        $email = $customerData->getEmail();

        $storeId = $this->getStoreId();
        $orderId = $order->getId();
        $coin = $metadata['cryptapi_currency'];
        $url = $metadata['cryptapi_payment_url'];

        $sender = [
            'email' => $scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE),
            'name' => $scopeConfig->getValue('trans_email/ident_sales/name', ScopeInterface::SCOPE_STORE)
        ];

        $this->inlineTranslation->suspend();

        $vars = [
            'order' => $orderId,
            'coin' => strtoupper($coin),
            'url' => $url,
        ];

        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($vars);

        $transport = $this->transportBuilder->setTemplateIdentifier(
            'cryptapi_email_link'
        )->
        setTemplateVars(['data' => $postObject])->
        setTemplateOptions([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $storeId,
        ])->
        setFromByScope($sender)->
        addTo($email)->
        getTransport();

        try {
            $transport->sendMessage();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
        $this->inlineTranslation->resume();

        return $this;
    }

    /*
     * get Current store id
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /*
     * get Current store Info
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }
}



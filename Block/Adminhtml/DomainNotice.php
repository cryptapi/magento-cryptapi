<?php

namespace Cryptapi\Cryptapi\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;

/**
 * The domain verification notice at the top of the CryptAPI settings.
 *
 * CryptAPI only creates payment addresses for callbacks on a verified domain,
 * and the callbacks go to the store view that took the order, so the notice
 * names every active store view's domain. It stays until the merchant sets
 * "Domain verified" to Yes and saves.
 */
class DomainNotice extends Field
{
    const VERIFY_DOMAIN_URL = 'https://cryptapi.io/verify_domain/';
    const VERIFY_DOMAIN_GUIDE_URL = 'https://docs.cryptapi.io/kb/developers/how-to-verify-your-callback-domain';

    public function render(AbstractElement $element)
    {
        if ($this->_scopeConfig->isSetFlag('payment/cryptapi/domain_verified')) {
            return '';
        }

        $domains = [];
        foreach ($this->_storeManager->getStores() as $store) {
            if (!$store->isActive()) {
                continue;
            }

            // The callback URL is secure or not depending on the buyer's page, so take both.
            foreach ([false, true] as $secure) {
                $host = (string)parse_url($store->getBaseUrl(UrlInterface::URL_TYPE_WEB, $secure), PHP_URL_HOST);
                if ($host !== '') {
                    $domains[$host] = '<code>' . $this->escapeHtml($host) . '</code>';
                }
            }
        }

        $domainsHtml = $domains ? implode(', ', $domains) : (string)__('your domain');

        return '<tr id="row_' . $element->getHtmlId() . '"><td colspan="5">'
            . '<div class="message message-warning">'
            . '<p><strong>' . __('Verify your domain so customers can pay') . '</strong></p>'
            . '<p>' . __('CryptAPI only creates payment addresses for verified domains. Until you verify %1, customers can\'t pay with CryptAPI. You only need to do this once, with a DNS TXT record or an HTTPS file. Payment addresses you already created keep working.', $domainsHtml) . '</p>'
            . '<p>'
            . '<a class="action-primary" href="' . $this->escapeUrl(self::VERIFY_DOMAIN_URL) . '" target="_blank" rel="noopener">' . __('Verify your domain') . '</a> '
            . '<a class="action-default" href="' . $this->escapeUrl(self::VERIFY_DOMAIN_GUIDE_URL) . '" target="_blank" rel="noopener">' . __('Step-by-step guide') . '</a>'
            . '</p>'
            . '<p>' . __('When you\'re done, set <strong>Domain verified</strong> below to Yes and save to hide this notice.') . '</p>'
            . '</div>'
            . '</td></tr>';
    }
}

<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Soisy
 * @copyright 2007-2022 Soisy
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of Soisy
 */

class SoisyConfiguration
{
    const SOISY_API_URL_SERVER_SANDBOX = 'https://api.sandbox.soisy.it';
    const SOISY_API_URL_SERVER_PRODUCTION = 'https://api.soisy.it';
    const SOISY_API_REDIRECT_MODE = 'POST';
    const SOISY_CONFIG_AJAX_TOKEN = 'QZjfZFDtUXT48fGF';
    const SOISY_CONFIG_DEFAULT_LOCALE = 'it_IT';

    const SOISY_ORDER_STATE_LOAN_APPROVED = 'LoanWasApproved'; // Richiesta approvata
    const SOISY_ORDER_STATE_REQUEST_COMPLETED = 'RequestCompleted'; // Richiesta completata
    const SOISY_ORDER_STATE_LOAN_VERIFIED = 'LoanWasVerified'; // In attesa di finanziamento
    const SOISY_ORDER_STATE_LOAN_PAID = 'LoanWasDisbursed'; // Finanziato
    const SOISY_ORDER_STATE_USER_WAS_REJECTED = 'UserWasRejected'; // Annullato 2 x eventMessage

    /** @var soisy */
    public $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function getOrderStateIdFromSoisyOrderState($soisyOrderStateName)
    {
        return Configuration::get($this->module->orderStates[$soisyOrderStateName]['key']);
    }

    public function isProductExcluded($id_product)
    {
        if ($excluded_products = Configuration::get('SOISY_PRODUCTS_FILTER')) {
            $excluded_products_array = explode(',', $excluded_products);
            return in_array($id_product, $excluded_products_array);
        }

        return false;
    }

    public function isCategoryExcluded($id_product)
    {
        if ($excluded_categories = Configuration::get('SOISY_CATEGORIES_FILTER')) {
            $excluded_categories_array = explode(',', $excluded_categories);
            $lang_id = (int)Configuration::get('PS_LANG_DEFAULT');
            $product = new Product($id_product, false, $lang_id);
            return in_array($product->id_category_default, $excluded_categories_array);
        }

        return false;
    }

    public function isExcludedSingle($id_product)
    {
        if ($this->isProductExcluded($id_product) || $this->isCategoryExcluded($id_product)) {
            return true;
        }
        return false;
    }

    public function isExcludedMultiple($products)
    {
        foreach ($products as $product) {
            if ($this->isExcludedSingle($product['id_product'])) {
                return true;
            }
        }
        return false;
    }

    public function updateOnDelete($id_product)
    {
        if ($this->isProductExcluded($id_product)) {
            $excluded_products = explode(',', Configuration::get('SOISY_PRODUCTS_FILTER'));
            $key = array_search($id_product, $excluded_products);
            unset($excluded_products[$key]);
            Configuration::updateValue('SOISY_PRODUCTS_FILTER', implode(',', $excluded_products));
        }
    }
}

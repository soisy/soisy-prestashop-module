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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . '/soisy/autoload.php');

class Soisy extends PaymentModule
{
    const SOISY_LOAN_SIMULATION_CDN = 'https://cdn.soisy.it/loan-quote-widget.js';
    const SOISY_SANDBOX_SHOP_ID = 'partnershop';

    protected $languages;
    protected $transTabContent = array();
    protected $apiUrl;
    protected $shopId;
    protected $apiKey;
    public $soisyApi;
    public $orderStates;
    public $psVersion;
    public $psSpecificVersion;
    public $sandboxMode;

    /** @var \Context */
    public $context;

    /** @var SoisyConfiguration */
    public $soisyConfigurations;

    /** @var Soisy\Plugins\SoisyPlugin\LoanController */
    public $loanController;

    /** @var SetupProcedure */
    public $setupProcedure;

    public function __construct()
    {
        $this->name = 'soisy';
        $this->module_key = '2137af924343568029001f1c00825e9f';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.4';
        $this->author = 'Soisy S.p.A';
        $this->need_instance = 1;
        $this->allow_push = true;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Soisy');
        $this->description = $this->l(
            'Aumenta le conversioni con i pagamenti rateali Soisy: semplici, veloci, 100% online.'
        );

        $this->confirmUninstall = $this->l('Are you sure you want uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->languages = Language::getLanguages(true);

        $this->transTabContent = array(
            'Name' => $this->l('Name'),
            'Value' => $this->l('Value'),
        );

        $this->psVersion = (int)Tools::substr(str_replace('.', '', _PS_VERSION_), 0, 2);
        $this->psSpecificVersion = (int)Tools::substr(str_replace('.', '', _PS_VERSION_), 0, 4);

        $this->orderStates = array(
            SoisyConfiguration::SOISY_ORDER_STATE_LOAN_APPROVED => array(
                'key' => 'SOISY_ORDER_STATE_LOAN_APPROVED',
                'name' => $this->l('Soisy: Request in progress'),
                'color' => '#4169E1',
                'invoice' => false,
                'paid' => false,
            ),
            SoisyConfiguration::SOISY_ORDER_STATE_LOAN_VERIFIED => array(
                'key' => 'SOISY_ORDER_STATE_LOAN_VERIFIED',
                'name' => $this->l('Soisy: Request approved'),
                'color' => '#FFE36B',
                'invoice' => false,
                'paid' => false,
            ),
            SoisyConfiguration::SOISY_ORDER_STATE_LOAN_PAID => array(
                'key' => 'SOISY_ORDER_STATE_LOAN_PAID',
                'name' => $this->l('Soisy: Request paid'),
                'color' => '#32CD32',
                'invoice' => true,
                'paid' => true,
            ),
            SoisyConfiguration::SOISY_ORDER_STATE_USER_WAS_REJECTED => array(
                'key' => 'SOISY_ORDER_STATE_USER_WAS_REJECTED',
                'name' => $this->l('Soisy: Request canceled'),
                'color' => '#FA3C3C',
                'invoice' => false,
                'paid' => false,
            ),
        );
        $this->soisyConfigurations = new SoisyConfiguration($this);

        $this->sandboxMode = !Configuration::get('SOISY_LIVE_MODE');

        $this->shopId = $this->sandboxMode ? self::SOISY_SANDBOX_SHOP_ID : Configuration::get('SOISY_SHOP_ID');
        $this->apiKey = $this->sandboxMode ? 'partnerkey' : Configuration::get('SOISY_API_KEY');
        $this->apiUrl = $this->sandboxMode
            ? SoisyConfiguration::SOISY_API_URL_SERVER_SANDBOX : SoisyConfiguration::SOISY_API_URL_SERVER_PRODUCTION;

        $this->soisyApi = new SoisyApi($this->apiUrl, $this->shopId, $this->apiKey);

        $loanRepository = new Soisy\Plugins\SoisyPlugin\Implementations\PrestaShop\PsLoanRepository(
            $this,
            $this->context
        );
        $shopApi = new Soisy\Plugins\SoisyPlugin\Implementations\PrestaShop\PsShopApi($this, $this->context);
        $logger = new Soisy\Plugins\SoisyPlugin\Implementations\PrestaShop\PsLogger();
        $translator = new Soisy\Plugins\SoisyPlugin\Implementations\PrestaShop\PsTranslator($this);
        $translator->initIt(
            array(
                'Cart was already bought' => $this->l('Cart was already bought'),
                'Found loan without an order' => $this->l('Found loan without an order'),
                'Unable to cancel shop order' => $this->l('Unable to cancel shop order'),
                'Unable to create loan' => $this->l('Unable to create loan'),
                'Unable to create shop order' => $this->l('Unable to create shop order'),
                'Unable to find loan' => $this->l('Unable to find loan'),
                'Unable to find order total' => $this->l('Unable to find order total'),
                'Unable to find related shop order' => $this->l('Unable to find related shop order'),
                'Unable to save created loan' => $this->l('Unable to save created loan'),
                'Unable to update loan data from order' => $this->l('Unable to update loan data from order'),
                'Unable to update loan state' => $this->l('Unable to update loan state'),
                'Unable to update loan total' => $this->l('Unable to update loan total'),
                'Unable to update shop order' => $this->l('Unable to update shop order'),
            )
        );
        $this->loanController = new Soisy\Plugins\SoisyPlugin\LoanController();
        $this->loanController->setLoanRepository($loanRepository);
        $this->loanController->setShopApi($shopApi);
        $this->loanController->setLogger($logger);
        $this->loanController->setTranslator($translator);
    }

    public function install()
    {
        if (extension_loaded('curl') === false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        include(_PS_MODULE_DIR_ . $this->name . '/sql/install.php');

        return
            parent::install() &&
            $this->registerHooks() &&
            $this->initConfigurations() &&
            $this->installOrderStates();
    }

    public function uninstall()
    {
        include(_PS_MODULE_DIR_ . $this->name . '/sql/uninstall.php');

        return parent::uninstall();
    }

    protected function registerHooks()
    {
        return
            $this->registerHook('header') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayPayment') &&
            $this->registerHook('displayProductPriceBlock') &&
            $this->registerHook('displayInternalLoanSimulation');
    }

    protected function installOrderStates()
    {
        foreach ($this->orderStates as $orderStateOptions) {
            $val = Configuration::get($orderStateOptions['key']);
            if ($val) {
                continue;
            }
            Configuration::updateValue($orderStateOptions['key'], '');

            $order_state = new OrderState();
            $order_state->module_name = $this->name;
            $order_state->name = array(
                $this->context->language->id => $orderStateOptions['name'],
            );

            $order_state->send_email = false;
            $order_state->color = $orderStateOptions['color'];
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = true;
            $order_state->invoice = $orderStateOptions['invoice'];
            $order_state->paid = $orderStateOptions['paid'];

            if ($order_state->add()) {
                Configuration::updateValue($orderStateOptions['key'], (int)$order_state->id);

                if ($this->psVersion > 16) {
                    $source = $this->local_path . '/logo.gif';
                    $destination = dirname($this->local_path, 2) . '/img/os/' . (int)$order_state->id . '.gif';
                    copy($source, $destination);
                }
            } else {
                return false;
            }
        }

        return true;
    }

    protected function initConfigurations()
    {
        Configuration::updateValue('SOISY_LIVE_MODE', false); // Modalità Live/Sandbox
        Configuration::updateValue('SOISY_LOG_ENABLED', false);
        Configuration::updateValue('SOISY_WIDGET_ENABLED', true); // Attivazione del widget per anteprima delle rate
        /**  Start Ticket 18546 */
        Configuration::updateValue('SOISY_NO_COLLISION',false); //attivazione no collision zone del widget
        /**  End Ticket  18546  */
        Configuration::updateValue('SOISY_SHOP_ID', self::SOISY_SANDBOX_SHOP_ID); // Shop ID, questo ID funziona solo per il pagamento e non mostra il Widget. Usare soisytests per il widget
        Configuration::updateValue('SOISY_API_KEY', 'partnerkey'); // Api Key
        Configuration::updateValue('SOISY_QUOTE_INSTALMENTS_AMOUNT', 10); // Numero rate simulazione prestito
        Configuration::updateValue('SOISY_MIN_AMOUNT', 100); // Importo minimo rateizzabile
        Configuration::updateValue('SOISY_MAX_AMOUNT', 15000);
        Configuration::updateValue('SOISY_WHITE_LIST', '');
        Configuration::updateValue('SOISY_ZERO_INTEREST_RATE', false);
        Configuration::updateValue('SOISY_CUSTOMER_FULL_INFO', false);
        return true;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        Media::addJsDef(array('soisy_products_ajax_url' => $this->context->link->getAdminLink('AdminModules',true).'&configure='.$this->name.'&action=load_products'));
        $this->context->controller->addJS($this->_path . 'views/js/admin.js');
        $html = $this->postProcess();
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('soisy_api_key', Configuration::get('SOISY_API_KEY'));
        $html .= $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
        $html .= $this->renderForm();
        return $html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSoisyModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(
            array(
                $this->getConfigForm1(),
                $this->getConfigForm2(),
                $this->filterform,
                $this->getConfigForm4()
            )
        );
    }

    protected function getConfigForm1()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Authentication Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'SOISY_LIVE_MODE',
                        'is_bool' => true,
                        'required' => true,
                        'desc' => $this->l(
                            'Enable the sandbox mode to test the service or live mode to go in production.'
                        ),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable log'),
                        'name' => 'SOISY_LOG_ENABLED',
                        'is_bool' => true,
                        'required' => true,
                        'desc' => $this->l('Enable log write.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SOISY_SHOP_ID',
                        'label' => $this->l('Shop ID'),
                        'desc' => $this->l('Please enter your shop id here'),
                        'lang' => false,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SOISY_API_KEY',
                        'label' => $this->l('API Key'),
                        'desc' => $this->l('Please enter your API key here'),
                        'lang' => false,
                        'required' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable widget'),
                        'name' => 'SOISY_WIDGET_ENABLED',
                        'is_bool' => true,
                        'required' => true,
                        'desc' => $this->l('Enable the preview widget.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    /**  Start Ticket 18546 */
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable no collision zone widget'),
                        'name' => 'SOISY_NO_COLLISION',
                        'is_bool' => true,
                        'required' => true,
                        'desc' => $this->l('Abilitare se il  widget collide con la descrizione o con il prezzo del prodotto.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                    /**  End Ticket  18546  */
                ),
                'submit' => array(
                    'name' => 'submitSoisyModuleConfigForm1',
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigForm2()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Installment management'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'name' => 'SOISY_MIN_AMOUNT',
                        'label' => $this->l('Minimum installment amount'),
                        'desc' => $this->l('Minimum amount that can be paid in installments'),
                        'lang' => false,
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'SOISY_QUOTE_INSTALMENTS_AMOUNT',
                        'label' => $this->l('Instalments number simulated'),
                        'desc' => $this->l('Instalments number simulated shown in products or cart totals'),
                        'lang' => false,
                        'required' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Zero Interest Rate'),
                        'name' => 'SOISY_ZERO_INTEREST_RATE',
                        'is_bool' => true,
                        'required' => true,
                        'desc' => $this->l('Consente l accettazione di prestiti anche a tasso zero.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'name' => 'submitSoisyModuleConfigForm2',
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    protected function getConfigForm4()
    {
        $inputs = array();

        foreach ($this->orderStates as $custom_order_status) {
            $inputs[] = array(
                'type' => 'select',
                'label' => $custom_order_status['name'],
                'name' => $custom_order_status['key'],
                'multiple' => false,
                'required' => true,
                'options' => array(
                    'query' => OrderState::getOrderStates($this->context->language->id),
                    'id' => 'id_order_state',
                    'name' => 'name',
                ),
                'desc' => $this->l('Select the order state'),
                'class' => 'fixed-width-xxl'
            );
        }

        $inputs[] = array(
            'type' => 'bookmark_advanced_settings',
            'name' => '',
        );

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Advanced settings (click to toggle)'),
                    'icon' => 'icon-cogs',
                ),
                'input' => $inputs,
                'submit' => array(
                    'name' => 'submitSoisyModuleConfigForm4',
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    public $filterform;

    protected function getConfigFormFilter()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Exclude products/categories'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    'products' => array(
                        'type' => 'text',
                        'label' => $this->l('Escludi questi prodotti'),
                        'name' => 'products',
                        'autocomplete' => false,
                        'class' => 'fixed-width-xxl',
                        'desc' => ''
                    ),
                    'categories' => array(
                        'type' => 'categories',
                        'label' => $this->l('Escludi queste categorie (evidenziare le categorie da escludere)'),
                        'name' => 'excluded_categories',
                        'tree' => array(
                            'use_search' => true,
                            'id' => 'categoryBox',
                            'use_checkbox' => true,
                            'selected_categories' => [],
                        )
                    ),
                ),
                'submit' => array(
                    'name' => 'submitSoisyModuleConfigFormFilter',
                    'title' => $this->l('Save'),
                ),
            )
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $configurations = array(
            'SOISY_LIVE_MODE' => Configuration::get('SOISY_LIVE_MODE'),
            'SOISY_LOG_ENABLED' => Configuration::get('SOISY_LOG_ENABLED'),
            'SOISY_SHOP_ID' => Configuration::get('SOISY_SHOP_ID'),
            'SOISY_API_KEY' => Configuration::get('SOISY_API_KEY'),
            'SOISY_WIDGET_ENABLED' => Configuration::get('SOISY_WIDGET_ENABLED'),
            /**  Start Ticket 18546 */
            'SOISY_NO_COLLISION' => Configuration::get('SOISY_NO_COLLISION'),
            /**  End Ticket  18546  */
            'SOISY_MIN_AMOUNT' => Configuration::get('SOISY_MIN_AMOUNT'),
            'SOISY_QUOTE_INSTALMENTS_AMOUNT' => Configuration::get('SOISY_QUOTE_INSTALMENTS_AMOUNT'),
            'SOISY_ZERO_INTEREST_RATE' => Configuration::get('SOISY_ZERO_INTEREST_RATE'),
        );

        // Custom order states
        foreach ($this->orderStates as $custom_order_status) {
            $configurations[$custom_order_status['key']] = Configuration::get($custom_order_status['key']);
        }

        $this->filterform = $this->getConfigFormFilter();
        // Excluded products
        $products_html = '';
        if (Configuration::get('SOISY_PRODUCTS_FILTER')) {
            $excluded_products = explode(',', Configuration::get('SOISY_PRODUCTS_FILTER'));
            foreach ($excluded_products as $id_product) {
                $products_html .= '<li>' . Product::getProductName($id_product) . '
            <a href="javascript:;" class="del_product"><img src="../img/admin/delete.gif" alt="del_img" /></a>
            <input type="hidden" name="id_product[]" value="' . $id_product . '" /></li>';
            }
        }
        $this->filterform['form']['input']['products']['desc'] = $this->l('Excluded products:')
            . '<ul id="curr_products"><span id="empty">' . $this->l('No products excluded') . '</span>' . $products_html . '</ul>';

        // Excluded categories
        $this->filterform['form']['input']['categories']['tree']['selected_categories'] = explode(',', Configuration::get('SOISY_CATEGORIES_FILTER'));

        return $configurations;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::getValue('action') == 'load_products') {
            if (!$q = Tools::getValue('q'))
                die;
            $excludeIds = Tools::getValue('excludeIds');
            $sql = 'SELECT p.`id_product`, pl.`name`
			    FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                ON p.`id_product` = pl.`id_product`
			    WHERE pl.`name` LIKE \'%' . pSQL($q) . '%\'
                AND `id_shop` = ' . (int)Shop::getContextShopID() . '
                AND `id_lang` = ' . $this->context->language->id;

            if (strlen($excludeIds) > 0)
                $sql .= ' AND p.`id_product` NOT IN (' . $excludeIds . ')';

            $result = Db::getInstance()->executeS($sql);
            foreach ($result as $value)
                echo trim($value['name']) . '|' . (int)($value['id_product']) . "\n";
            die;
        }

        if (Tools::isSubmit('submitSoisyModuleConfigForm1')) {
            Configuration::updateValue('SOISY_LIVE_MODE', Tools::getValue('SOISY_LIVE_MODE'));
            Configuration::updateValue('SOISY_LOG_ENABLED', Tools::getValue('SOISY_LOG_ENABLED'));
            Configuration::updateValue('SOISY_SHOP_ID', Tools::getValue('SOISY_SHOP_ID'));
            Configuration::updateValue('SOISY_API_KEY', Tools::getValue('SOISY_API_KEY'));
            Configuration::updateValue('SOISY_WIDGET_ENABLED', Tools::getValue('SOISY_WIDGET_ENABLED'));
            /**  Start Ticket 18546 */
            Configuration::updateValue('SOISY_NO_COLLISION', Tools::getValue('SOISY_NO_COLLISION'));
            /**  End Ticket 18546 */
            return $this->displayConfirmation($this->l('Configuration updated successfully'));
        } elseif (Tools::isSubmit('submitSoisyModuleConfigForm2')) {
            $validation_result = $this->validateConfigForm();

            if ($validation_result['status'] === 'error') {
                return $this->displayError($validation_result['data']);
            }

            Configuration::updateValue('SOISY_MIN_AMOUNT', Tools::getValue('SOISY_MIN_AMOUNT'));
            Configuration::updateValue('SOISY_QUOTE_INSTALMENTS_AMOUNT', Tools::getValue('SOISY_QUOTE_INSTALMENTS_AMOUNT'));
            Configuration::updateValue('SOISY_ZERO_INTEREST_RATE', Tools::getValue('SOISY_ZERO_INTEREST_RATE'));
            return $this->displayConfirmation($this->l('Configuration updated successfully'));
        } elseif (Tools::isSubmit('submitSoisyModuleConfigForm4')) {
            // Custom order states
            foreach ($this->orderStates as $custom_order_status) {
                Configuration::updateValue($custom_order_status['key'], Tools::getValue($custom_order_status['key']));
            }
            return $this->displayConfirmation($this->l('Configuration updated successfully'));
        } elseif (Tools::isSubmit('submitSoisyModuleConfigFormFilter')) {
            // Excluded products
            $excluded_products = Tools::getValue('id_product');
            if (is_array($excluded_products)) {
                $ids_product = array();
                foreach ($excluded_products as $id_product)
                    $ids_product[] = (int)$id_product;
                Configuration::updateValue('SOISY_PRODUCTS_FILTER', implode(',', $ids_product));
            } else {
                Configuration::updateValue('SOISY_PRODUCTS_FILTER', '');
            }
            // Excluded categories
            $excluded_categories = Tools::getValue('excluded_categories');
            if (is_array($excluded_categories)) {
                $ids_category = array();
                foreach ($excluded_categories as $id_category) {
                    if ($id_category != 0) {
                        $ids_category[] = (int)$id_category;
                    }
                }
                Configuration::updateValue('SOISY_CATEGORIES_FILTER', implode(',', $ids_category));
            } else {
                Configuration::updateValue('SOISY_CATEGORIES_FILTER', '');
            }

            return $this->displayConfirmation($this->l('Configuration updated successfully'));
        }
    }

    protected function validateConfigForm()
    {
        if (filter_var(Tools::getValue('SOISY_MIN_AMOUNT'), FILTER_VALIDATE_INT) === false) {
            return array(
                'status' => 'error',
                'data' => $this->l('The minimum amount must be an integer'),
            );
        }
        // SOISY_BASIC_QUOTE_INSTALMENTS_AMOUNT between 3 and 60
        $instalments = Configuration::get('SOISY_QUOTE_INSTALMENTS_AMOUNT');
        $amount = 100 * Tools::getValue('SOISY_MIN_AMOUNT');

        return $this->soisyApi->loanQuotes($amount, $instalments);
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') === $this->name || Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJquery();
            $this->context->controller->addJS(
                _PS_MODULE_DIR_ . $this->name . '/views/js/' . $this->psVersion . '/back.js'
            );
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->hookDisplayHeader();
    }

    public function getCurrentControllerName()
    {
        $controller_name = $this->context->controller->php_self;
        if (empty($controller_name)) {
            $controller_name = Tools::getValue('controller');
        }

        return $controller_name;
    }

    public function hookDisplayHeader()
    {
        $controller_name = $this->getCurrentControllerName();

        if ($controller_name == 'product') {
            Media::addJsDef(
                array(
                    'soisy_controller' => $controller_name,
                    'soisy_ps_version' => $this->psVersion,
                )
            );
            /**  Start Ticket 18546 */
            $enabled = Configuration::get('SOISY_NO_COLLISION');
            if($enabled){
                $this->context->controller->addCSS($this->_path.'views/css/product.css');
            }
            /**  End Ticket  18546  */
        }

        if ($this->psVersion > 16) {
            $this->context->controller->registerJavascript(
                'soisy_cdn',
                self::SOISY_LOAN_SIMULATION_CDN,
                array('server' => 'remote', 'attributes' => 'defer')
            );
            if ($this->psSpecificVersion <= 1772) {
                if ($controller_name == 'product') {
                    $this->context->controller->registerJavascript(
                        'modules-soisy-product',
                        'modules/soisy/views/js/product.js',
                        array('media' => 'all', 'priority' => 200)
                    );
                }
            }
        } else {
            if ($controller_name == 'product') {
                $this->context->controller->addJS(($this->_path) . 'views/js/product.js');
            }
            $this->smarty->assign('soisyJsUrl', self::SOISY_LOAN_SIMULATION_CDN);
            return $this->display(__FILE__, 'views/templates/hook/16/soisy_js_import.tpl');
        }
    }

    public function hookActionProductDelete($params){
        $this->soisyConfigurations->updateOnDelete($params['id_product']);
    }


    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->isModuleUsable($params['cart'])) {
            return;
        }

        if($this->soisyConfigurations->isExcludedMultiple($params['cart']->getProducts())) {
            return;
        }

        $amount = $params['cart']->getOrderTotal();

        $option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $option->setCallToActionText($this->l('Pay in installments with Soisy'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo-soisy-min.png'))
            ->setAction($this->context->link->getModuleLink($this->name, 'redirect', array(), true))
            ->setInputs(
                array(
                    'token' => array('name' => 'token', 'type' => 'hidden', 'value' => '12345689'),
                )
            )
            ->setAdditionalInformation(
                $this->fetch('module:' . $this->name . '/views/templates/hook/payment_info.tpl')
                .
                $this->getCachedLoanSimulation($amount)
            );

        return array($option);
    }

    public function hookDisplayPayment($params)
    {
        if (!$this->isModuleUsable($params['cart'])) {
            return;
        }

        if($this->soisyConfigurations->isExcludedMultiple($params['cart']->getProducts())) {
            return;
        }

        $this->smarty->assign(
            array(
                'this_path' => $this->_path,
                'this_path_ssl' => Tools::getShopDomainSsl(
                    true,
                    true
                ) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            )
        );

        if ($this->psVersion > 16) {
            return $this->display(__FILE__, 'views/templates/hook/17/payment.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/hook/16/payment.tpl');
        }
    }

    /**
     * @param Cart        $cart
     * @param ?float|null $amount
     * @return bool
     */
    public function isModuleUsable($cart, $amount = null)
    {
        // Module must be active.
        if (!$this->active) {
            return false;
        }

        // Module must accept current currency.
        $accepted = false;
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            $currency_order = new Currency($cart->id_currency);
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    $accepted = true;
                    break;
                }
            }
        }
        if (!$accepted) {
            return false;
        }

        // Module can request loans only if inside a strict range.
        if (is_null($amount)) {
            $amount = $cart->getOrderTotal();
        }
        return $amount >= Configuration::get('SOISY_MIN_AMOUNT') && $amount < Configuration::get('SOISY_MAX_AMOUNT');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        $productId = @$params['product']->id;

        if ($this->soisyConfigurations->isExcludedSingle($productId)) {
            return '';
        }

        if ($params['type'] !== 'after_price' or empty($productId)) {
            return '';
        }

        $id_product_attribute = 0;
        if (isset($params['product']->id_product_attribute)) {
            $id_product_attribute = $params['product']->id_product_attribute;
        }

        $amount = Product::getPriceStatic($productId, true, $id_product_attribute, 2);
        if (!$this->isModuleUsable($params['cart'], $amount)) {
            return '';
        }

        return $this->getCachedLoanSimulation($amount);
    }

    public function hookDisplayInternalLoanSimulation($params)
    {
        if (!isset($params['cart'])) {
            return '';
        }
        $amount = $params['cart']->getOrderTotal();
        return $this->getCachedLoanSimulation($amount);
    }

    public function getCachedLoanSimulation($amount)
    {
        $enabled = Configuration::get('SOISY_WIDGET_ENABLED');
        if (!$enabled) {
            return '';
        }
        $widgetShopId = $this->sandboxMode ? self::SOISY_SANDBOX_SHOP_ID : Configuration::get('SOISY_SHOP_ID');

        $this->smarty->assign(
            array(
                'amount' => round($amount, 2),
                'instalments' => Configuration::get('SOISY_QUOTE_INSTALMENTS_AMOUNT'),
                'shop_id' => $widgetShopId,
                'zero_interest_rate' => Configuration::get('SOISY_ZERO_INTEREST_RATE'),
            )
        );
        if ($this->psVersion > 16) {
            return $this->fetch('module:' . $this->name . '/views/templates/hook/loan_simulation.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/hook/loan_simulation.tpl');
        }
    }

    /**
     * @param string $template
     * @param array  $params
     * @return string
     */
    private function render($template, $params = array())
    {
        /** @var Twig_Environment $twig */
        $twig = $this->get('twig');

        return $twig->render($template, $params);
    }

    // Enables new translation system: https://stackoverflow.com/a/64118133
    public function isUsingNewTranslationSystem()
    {
        return false;
    }
}

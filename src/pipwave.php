<?php
/**
 * pipwave Prestashop Plugin
 *
 * @author    pipwave <support@pipwave.com>
 * @copyright 2016 Dynamic Podium
 * @license   GPLv3
 */

if (!defined('_PS_VERSION_'))
    exit;

class pipwave extends PaymentModule {

    protected $api_key, $api_secret, $surcharge_group, $order_prefix;
    public $test_mode;
    
    protected $sdk_version = 'v1.0';
    protected $api_version = 'v1.0';
    
    public $merchant_portal_url, $secure_portal_url, $api_portal_url;

    public function __construct() {
        $this->module_key = '1438c7820eb9d39855e4758c349ba0ba';
        $this->name = 'pipwave';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Dynamic Podium';
        $this->author_uri = 'https://github.com/dpodium';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        $this->displayName = 'pipwave';
        $this->description = $this->l('The simple, reliable and cost-effective way to accept payments online.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall pipwave and remove your details?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6');

        $config = Configuration::getMultiple(array('PIPWAVE_API_KEY', 'PIPWAVE_API_SECRET', 'PIPWAVE_SURCHARGE_GROUP', 'PIPWAVE_TEST_MODE', 'PIPWAVE_ORDER_PREFIX'));
        if (array_key_exists('PIPWAVE_API_KEY', $config)) {
            $this->api_key = $config['PIPWAVE_API_KEY'];
        }
        if (array_key_exists('PIPWAVE_API_SECRET', $config)) {
            $this->api_secret = $config['PIPWAVE_API_SECRET'];
        }
        if (array_key_exists('PIPWAVE_SURCHARGE_GROUP', $config)) {
            $this->surcharge_group = $config['PIPWAVE_SURCHARGE_GROUP'];
        }
        if (array_key_exists('PIPWAVE_TEST_MODE', $config)) {
            $this->test_mode = $config['PIPWAVE_TEST_MODE'];
        }
        if (array_key_exists('PIPWAVE_ORDER_PREFIX', $config)) {
            $this->order_prefix = $config['PIPWAVE_ORDER_PREFIX'];
        }
        parent::__construct();
        if (empty($this->api_key) || empty($this->api_secret)) {
            $this->warning = $this->l('Your pipwave account is not set yet');
        }
        if ($this->test_mode == '0') {
            $this->merchant_portal_url = 'https://merchant.pipwave.com/';
            $this->secure_portal_url = 'https://secure.pipwave.com/';
            $this->api_portal_url = 'https://api.pipwave.com/payment';
        } else {
            $this->merchant_portal_url = 'https://staging-merchant.pipwave.com/';
            $this->secure_portal_url = 'https://staging-checkout.pipwave.com/';
            $this->api_portal_url = 'https://staging-api.pipwave.com/payment';
        }
    }

    /**
     * Install the module into prestashop
     * 
     * @return boolean
     */
    public function install() {
        return parent::install() && $this->registerHook('payment') && $this->registerHook('paymentReturn') && $this->registerHook('BackOfficeHeader') && $this->registerHook('adminOrder') && $this->registerHook('header');
    }

    /**
     * Uninstall the module from prestashop
     * 
     * @return boolean
     */
    public function uninstall() {
        return Configuration::deleteByName('PIPWAVE_API_KEY') && Configuration::deleteByName('PIPWAVE_API_SECRET') && Configuration::deleteByName('PIPWAVE_SURCHARGE_GROUP') && Configuration::deleteByName('PIPWAVE_TEST_MODE') && Configuration::deleteByName('PIPWAVE_ORDER_PREFIX') && parent::uninstall();
    }

    /**
     * Display the configuration setting.
     * 
     * @return string
     */
    public function getContent() {
        $html = array();
        if (Tools::isSubmit('btnSubmit')) {
            $html = array_merge($html, $this->updateConfiguration());
        } else {
            $html[] = '<br />';
        }

        $html[] = $this->display(__FILE__, 'info.tpl');
        $html[] = $this->generateForm();

        return implode("\n", $html);
    }

    protected function updateConfiguration() {
        $html = array();
        if (!Tools::getValue('api_key')) {
            $html[] = $this->displayError($this->l('API key is required'));
        } else if (!Tools::getValue('api_secret') && !Configuration::get('PIPWAVE_API_SECRET')) {
            $html[] = $this->displayError($this->l('API secret is required.'));
        } else {
            Configuration::updateValue('PIPWAVE_API_KEY', Tools::getValue('api_key'));
            Configuration::updateValue('PIPWAVE_SURCHARGE_GROUP', Tools::getValue('surcharge_group'));
            Configuration::updateValue('PIPWAVE_TEST_MODE', Tools::getValue('test_mode'));
            Configuration::updateValue('PIPWAVE_ORDER_PREFIX', Tools::getValue('order_prefix'));
            if (Tools::getValue('api_secret')) {
                Configuration::updateValue('PIPWAVE_API_SECRET', Tools::getValue('api_secret'));
            }
            $html[] = $this->displayConfirmation($this->l('Configurations updated'));
        }
        return $html;
    }

    protected function generateForm() {
        $fields_form = array(
            //0
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-gear',
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('API key'),
                            'name' => 'api_key',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('API secret (leave blank if no update)'),
                            'name' => 'api_secret',
                            'required' => true
                        ),
                        array(
                            'type' => 'radio',
                            'label' => $this->l('Payment environment'),
                            'name' => 'test_mode',
                            'values' => array(
                                array(
                                    'id' => 'test_mode_live',
                                    'value' => '0',
                                    'label' => $this->l('Live'),
                                ),
                                array(
                                    'id' => 'test_mode_test',
                                    'value' => '1',
                                    'label' => $this->l('Test'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Processing fee group reference ID'),
                            'name' => 'surcharge_group',
                            'required' => false
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Order prefix'),
                            'name' => 'order_prefix',
                            'required' => false
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                    )
                ),
            ),
        );

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->default_form_language = $lang;
        $helper->allow_employee_form_lang = $lang;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Load current value
        $helper->fields_value['api_key'] = $this->api_key;
        $helper->fields_value['surcharge_group'] = $this->surcharge_group;
        $helper->fields_value['test_mode'] = $this->test_mode;
        $helper->fields_value['order_prefix'] = $this->order_prefix;

        return $helper->generateForm($fields_form);
    }

    /**
     * Hook the payment form to display pipwave payment method selection
     * 
     * @param array $params
     * @return string
     */
    public function hookPayment($params) {
        if (!$this->active)
            return;

        $caller_version = 'Prestashop v' . _PS_VERSION_ . ' Plugin v' . $this->version;

        $lang = new Language($this->context->cart->id_lang);
        $billing_address = new Address((int) $this->context->cart->id_address_invoice);
        $billing_state = new State((int) $billing_address->id_state);
        $billing_country = new Country((int) $billing_address->id_country);
        $shipping_address = new Address((int) $this->context->cart->id_address_delivery);
        $shipping_state = new State((int) $shipping_address->id_state);
        $shipping_country = new Country((int) $shipping_address->id_country);

        $data = array(
            'action' => 'initiate-payment',
            'timestamp' => time(),
            'api_key' => $this->api_key,
            'txn_id' => (!empty($this->order_prefix) ? $this->order_prefix : '') . $this->context->cart->id,
            'amount' => number_format($this->context->cart->getOrderTotal(true, Cart::BOTH), 2, ".", ""),
            'currency_code' => $this->context->currency->iso_code,
            'short_description' => 'Payment for Cart#' . $this->context->cart->id,
            'session_info' => array(
                'ip_address' => $this->getBuyerIp(),
                'language' => $lang->language_code,
            ),
            'buyer_info' => array(
                'id' => '' . $this->context->customer->id,
                'email' => $this->context->customer->email,
                'first_name' => $this->context->customer->firstname,
                'last_name' => $this->context->customer->lastname,
                'surcharge_group' => $this->surcharge_group,
            ),
            'billing_info' => array(
                'name' => $billing_address->firstname . " " . $billing_address->lastname,
                'address1' => $billing_address->address1,
                'address2' => $billing_address->address2,
                'city' => $billing_address->city,
                'state' => $billing_state->name,
                'zip' => $billing_address->postcode,
                'country' => $billing_address->country,
                'country_iso2' => $billing_country->iso_code,
                'contact_no' => $billing_address->phone_mobile,
            ),
            'shipping_info' => array(
                'name' => $shipping_address->firstname . " " . $shipping_address->lastname,
                'address1' => $shipping_address->address1,
                'address2' => $shipping_address->address2,
                'city' => $shipping_address->city,
                'state' => $shipping_state->name,
                'zip' => $shipping_address->postcode,
                'country' => $shipping_address->country,
                'country_iso2' => $shipping_country->iso_code,
                'contact_no' => $shipping_address->phone_mobile,
            ),
            'api_override' => array(
                'success_url' => $this->context->link->getModuleLink('pipwave', 'return'),
                'fail_url' => $this->context->link->getModuleLink('pipwave', 'fail'),
                'notification_url' => $this->context->link->getModuleLink('pipwave', 'notification'),
                'notification_extra_param1' => $this->context->cart->id,
            ),
            'version' => $caller_version,
        );
        foreach ($this->context->cart->getProducts() as $product) {
            $data['item_info'][] = array(
                "name" => $product['name'],
                "description" => strip_tags(trim($product['description_short'])),
                "amount" => $product['price'],
                "currency_code" => $data['currency_code'],
                "quantity" => $product['quantity'],
            );
        }

        $signatureParam = array(
            'txn_id' => $data['txn_id'],
            'amount' => $data['amount'],
            'currency_code' => $data['currency_code'],
            'action' => $data['action'],
            'timestamp' => $data['timestamp']
        );
        $data['signature'] = $this->generateSignature($signatureParam);

        $response = $this->sendRequest($data);

        if (array_key_exists('status', $response) && $response['status'] == 200) {
            $this->smarty->assign(array(
                'initiate_payment' => 'success',
                'api_data' => array(
                    'api_key' => $this->api_key,
                    'token' => $response['token'],
                    'caller_version' => $caller_version,
                ),
                'sdk_url' => $this->secure_portal_url . 'sdk/',
            ));
        } else {
            $this->smarty->assign(array(
                'initiate_payment' => 'fail',
                'message' => $this->l('An error has occured. Please contact the server administrator.') . (!empty($response['message']) ? ' ' . $this->l('Message') . ' : ' . $response['message'] : ''),
            ));
        }

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * Hook the payment return to the prestashop payment return method
     * 
     * @param array $params
     * @return string
     */
    public function hookPaymentReturn($params) {
        if (!$this->active)
            return;

        if (array_key_exists('objOrder', $params) && !empty($params['objOrder']->reference)) {
            $this->smarty->assign(array(
                'reference' => $params['objOrder']->reference,
            ));
        }

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /*
     * Hook back office header to intercept refund requests
     *
     * @return string
     */
    public function hookBackOfficeHeader() {
        // Continue only if we are on the order's details page (Back-office)
        if (!Tools::getIsset('vieworder') || !Tools::getIsset('id_order')) {
            return;
        }
        
        $order = new Order(Tools::getValue('id_order'));
        if ($order->module != $this->name) {
            return;
        }
        // Refund button is clicked!
        if (Tools::getIsset('pipwave_refund_amount')) {
            $this->smarty->assign(array(
                'pipwave_refund_amount' => Tools::getValue('pipwave_refund_amount'),
            ));
            $this->processRefund($order, Tools::getValue('pipwave_refund_amount'));
        }
    }

    /*
     * @param array $params
     * @return string
     */
    public function hookAdminOrder($params) {
        $order = new Order($params['id_order']);
        
        if ($order->module != $this->name) {
            return;
        }
        $payment_collection = $this->getFirstOrderPayment($order);
        $pw_id = !empty($payment_collection->transaction_id) ? $payment_collection->transaction_id : '';
        $this->smarty->assign(array(
            'pipwave_pw_id' => $pw_id,
            'pipwave_merchant_portal_url' => $this->merchant_portal_url,
        ));
        return $this->display(__FILE__, 'admin_order.tpl');
    }
    
    /*======== Process flows ========*/
    
    protected function processRefund($order, $amount) {
        $amount = $amount * 1;
        if ($amount <= 0) {
            $this->smarty->assign(array(
                'pipwave_head' => $this->displayError($this->l('Please enter valid refund amount and try again')),
            ));
            return;
        }
        $payment_collection = $this->getFirstOrderPayment($order);
        $pw_id = !empty($payment_collection->transaction_id) ? $payment_collection->transaction_id : '';
        if (empty($pw_id)) {
            $this->smarty->assign(array(
                'pipwave_head' => $this->displayError($this->l('pipwave reference ID not found. Kindly contact pipwave administrator.')),
            ));
            return;
        }
        
        $data = array(
            'timestamp' => time(),
            'api_key' => $this->api_key,
            'pw_id' => $pw_id,
            'refund_amount' => $amount,
        );
        
        $response = $this->attemptRefundRequest($data, 'initiate');
        if (array_key_exists('status', $response) && $response['status'] == 200) {
            if (!$response['supports_refund']) {
                $this->smarty->assign(array(
                    'pipwave_head' => $this->displayError($this->l('Refund for this transaction must be done in pipwave merchant center.')),
                ));
            } else {
                $response = $this->attemptRefundRequest($data, 'submit');
                if (!array_key_exists('status', $response)) {
                    $this->smarty->assign(array(
                        'pipwave_head' => $this->displayError($this->l('Refund for this transaction must be done in pipwave merchant center.')),
                    ));
                } else if ($response['status'] == 200) {
                    $this->smarty->assign(array(
                        'pipwave_head' => $this->displayConfirmation($this->l('Refund request submitted successfully!')),
                    ));
                } else if (in_array($response['status'], array(3003, 3004, 3005, '3003', '3004', '3005'))) {
                    $this->smarty->assign(array(
                        'pipwave_head' => $this->displayError($this->l('Refund for this transaction must be done in pipwave merchant center.')),
                    ));
                } else {
                    $this->smarty->assign(array(
                        'pipwave_head' => $this->displayError($this->l('Refund for this transaction must be done in pipwave merchant center.')),
                    ));
                }
            }
        } else {
            $this->smarty->assign(array(
                'pipwave_head' => $this->displayError($this->l('Refund for this transaction must be done in pipwave merchant center.')),
            ));
        }
    }
    
    protected function attemptRefundRequest($data, $op) {
        $data['op'] = $op;
        $signatureParam = array(
            'op' => $data['op'],
            'pw_id' => $data['pw_id'],
            'refund_amount' => $data['refund_amount'],
            'action' => $data['action'],
            'timestamp' => $data['timestamp'],
        );
        $data['signature'] = $this->generateSignature($signatureParam);
        
        return $this->sendRequest($data);
    }
    
    /*======== Utilities ========*/

    protected function getBuyerIp() {
        return !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDE‌​D_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
    }

    protected function sendRequest($data) {
        $agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("x-api-key:$this->api_key"));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, Tools::jsonEncode($data));
        curl_setopt($ch, CURLOPT_URL, $this->api_portal_url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $response = curl_exec($ch);
        if ($response == false) {
            echo "<pre>";
            echo 'CURL ERROR: ' . curl_errno($ch) . '::' . curl_error($ch);
            die;
        }
        curl_close($ch);

        return Tools::jsonDecode($response, true, 512, JSON_BIGINT_AS_STRING);
    }
    
    /*======== Public functions ========*/

    public function generateSignature($array) {
        $array['api_key'] = $this->api_key;
        $array['api_secret'] = $this->api_secret;
        ksort($array);
        $signature = "";
        foreach ($array as $key => $value) {
            $signature .= $key . ':' . $value;
        }
        return sha1($signature);
    }

    public function getFirstOrderPayment($order) {
        $payment_collection = $order->getOrderPaymentCollection();
        if (!empty($payment_collection[0])) {
            return $payment_collection[0];
        }
        return null;
    }
}

<?php

/**
 * pipwave Prestashop Plugin
 *
 * @author pipwave <support@pipwave.com>
 *
 */
class pipwaveReturnModuleFrontController extends ModuleFrontController {

    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {
        if (!$this->module->active || !isset($_GET['pipwaveTxn'])) {
            //Redirect user back to order since something is wrong
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        if (Order::getOrderByCartId($cart->id) === false) {
            $base_msg = "Cart ID : %s\nTxn ID : %s\n";
            if ($this->module->test_mode != '0') {
                $base_msg .= "This transaction is test mode!\n";
            }
            $base_msg .= "\n";
            $this->module->validateOrder($cart->id, Configuration::get('PS_OS_PREPARATION'), number_format($this->context->cart->getOrderTotal(true, Cart::BOTH), 2, ".", ""), $this->module->displayName, sprintf($base_msg, $cart->id, $_GET['pipwaveTxn']), array(), null, false, $customer->secure_key);
        }
        //Show order confirmation page
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }

}

<?php
/**
 * pipwave Prestashop Plugin
 *
 * @author    pipwave <support@pipwave.com>
 * @copyright 2016 Dynamic Podium
 * @license   GPLv3
 */

class pipwaveNotificationModuleFrontController extends ModuleFrontController {

    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {
        ignore_user_abort(true); // just to be safe
        ob_start();
        echo "OK";
        $size = ob_get_length();
        header('HTTP/1.1 200 OK');
        header("Connection: close");
        header("Content-Length: $size");
        ob_end_flush();
        ob_flush();
        flush();
        
        $post_content = Tools::file_get_contents("php://input");
        $post_data = Tools::jsonDecode($post_content, true);
        $this->actualProcess($post_data);
        exit;
    }

    /**
     * @param array $post_data
     */
    protected function actualProcess($post_data) {
        $timestamp = (array_key_exists('timestamp', $post_data) && !empty($post_data['timestamp'])) ? $post_data['timestamp'] : time();
        $pw_id = (array_key_exists('pw_id', $post_data) && !empty($post_data['pw_id'])) ? $post_data['pw_id'] : '';
        $order_number = (array_key_exists('txn_id', $post_data) && !empty($post_data['txn_id'])) ? $post_data['txn_id'] : '';
        $cart_id = (array_key_exists('extra_param1', $post_data) && !empty($post_data['extra_param1'])) ? $post_data['extra_param1'] : '';
        $amount = (array_key_exists('amount', $post_data) && !empty($post_data['amount'])) ? $post_data['amount'] : '';
        $final_amount = (array_key_exists('final_amount', $post_data) && !empty($post_data['final_amount'])) ? $post_data['final_amount'] : "0.00";
        $currency_code = (array_key_exists('currency_code', $post_data) && !empty($post_data['currency_code'])) ? $post_data['currency_code'] : '';
        $transaction_status = (array_key_exists('transaction_status', $post_data) && !empty($post_data['transaction_status'])) ? $post_data['transaction_status'] : '';
        $payment_method = 'pipwave' . (!empty($post_data['payment_method_title']) ? (" - " . $post_data['payment_method_title']) : "");
        $signature = (array_key_exists('signature', $post_data) && !empty($post_data['signature'])) ? $post_data['signature'] : '';
        $signatureParam = array(
            'timestamp' => $timestamp,
            'pw_id' => $pw_id,
            'txn_id' => $order_number,
            'amount' => $amount,
            'currency_code' => $currency_code,
            'transaction_status' => $transaction_status,
        );
        $generatedSignature = $this->module->generateSignature($signatureParam);
        if ($signature != $generatedSignature) {
            $transaction_status = -1;
        }

        $with_warning_msg = ($post_data['status'] == 3001) ? " (with warning)" : '';

        $note = array();
        $note[] = sprintf("Paid with : %s", $payment_method);
        
        $move_order = null;
        switch ($transaction_status) {
            case 5: // pending
                $note[] = "Payment Status: Pending$with_warning_msg";
                $move_order = Configuration::get('PS_OS_PREPARATION');
                break;
            case 1: // failed
                $note[] = "Payment Status: Failed$with_warning_msg";
                $move_order = Configuration::get('PS_OS_ERROR');
                break;
            case 2: // cancelled
                $note[] = "Payment Status: Cancelled$with_warning_msg";
                $move_order = Configuration::get('PS_OS_CANCELED');
                break;
            case 10: // complete
                $note[] = "Payment Status: Completed$with_warning_msg";
                $move_order = Configuration::get('PS_OS_PAYMENT');
                break;
            case 20: // refunded
                $note[] = "Payment Status: Refunded$with_warning_msg";
                $move_order = Configuration::get('PS_OS_REFUND');
                break;
            case 25: // partial refunded
                $note[] = "Payment Status: Refunded$with_warning_msg";
                $move_order = Configuration::get('PS_OS_REFUND');
                break;
            case -1: // signature mismatch
                $note[] = "Signature mismatch$with_warning_msg";
                $move_order = Configuration::get('PS_OS_ERROR');
                break;
            default:
                $note[] = "\nUnknown payment status\n";
                $move_order = Configuration::get('PS_OS_ERROR');
        }

        $order = new Order((int) Order::getOrderByCartId($cart_id));
        $payment_collection = null;
        
        if (empty($order)) {
            //Order not created yet, probably buyer disconnected
            $cart = new Cart((int) $cart_id);
            if (empty($cart->id)) {
                //TODO Find no cart... Ignore?
                return;
            } else {
                $currency_id = Currency::getIdByIsoCode($currency_code, $cart->id_shop);
                $customer = new Customer($cart->id_customer);
                $cart_order_total = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, ".", "");
                
                if (in_array($transaction_status, array(10, 20, 25))) {
                    $note[] = sprintf("Currently paid : %s %s", $currency_code, $final_amount);
                }
                $note[] = sprintf("pipwave Reference ID : %s\n", $pw_id);
                
                if ($amount != $cart_order_total) {
                    $note[] = "Amount mismatch";
                }
                if ($currency_id != $cart->id_currency) {
                    $note[] = "Currency mismatch";
                }
                $base_msg = "Cart ID : %s\nTxn ID : %s\n";
                if ($this->module->test_mode != '0') {
                    $base_msg .= "This transaction is test mode!\n";
                }
                $base_msg .= "\n%s";
                $this->module->validateOrder((int) $cart->id, $move_order, $cart_order_total, $payment_method, sprintf($base_msg, $cart->id, $order_number, implode("\n", $note)), array(), $cart->id_currency, false, $customer->secure_key);
                
                //Pre-grab payment collection object so that we can set pw_id later
                $order = new Order((int) Order::getOrderByCartId($cart_id));
                $payment_collection = $this->module->getFirstOrderPayment($order);
            }
        } else {
            //Validate the notification
            $currency_id = Currency::getIdByIsoCode($currency_code, $order->id_shop);
            $payment_collection = $this->module->getFirstOrderPayment($order);
            
            if (in_array($transaction_status, array(10, 20, 25))) {
                $note[] = sprintf("Currently paid : %s %s", $currency_code, $final_amount);
            }
            if (!empty($payment_collection->transaction_id) && $payment_collection->transaction_id != $pw_id) {
                $note[] = "\npipwave Reference ID mismatch";
            } else {
                $note[] = sprintf("pipwave Reference ID : %s\n", $pw_id);
            }
            if ($amount != $order->total_paid) {
                $note[] = "Amount mismatch";
            }
            if ($currency_id != $order->id_currency) {
                $note[] = "Currency mismatch";
            }
            $history = new OrderHistory();
            $history->id_order = (int) $order->id;
            $history->changeIdOrderState($move_order, (int) ($order->id), true);
            $history->addWithemail(true);
            
            $msg = new Message();
            $msg->message = implode("\n", $note);
            $msg->id_cart = (int) $order->id_cart;
            $msg->id_customer = (int) ($order->id_customer);
            $msg->id_order = (int) $order->id;
            $msg->private = 1;
            $msg->add();
            
            //Payment collection not found, means no invoice yet
            if (!empty($payment_collection)) {
                //Try to create the invoice here, see if failed then it means invoice is disabled for the status
                $order->setInvoice(true);
                //If successful create invoice, grab the payment collection object again so that we can set pw_id
                $payment_collection = $this->module->getFirstOrderPayment($order);
            }
        }
        
        //Refresh order model in case other functions changed data in database
        $order = new Order((int) $order->id);
        if (!empty($order)) {
            //Update the payment method
            $order->payment = $payment_method;
            $order->save();
        }
        
        if (!empty($payment_collection) && $payment_collection->transaction_id == '') {
            //Update the payment collection transaction ID
            $payment_collection->transaction_id = $pw_id;
            $payment_collection->payment_method = $payment_method;
            $payment_collection->save();
        }
    }

}

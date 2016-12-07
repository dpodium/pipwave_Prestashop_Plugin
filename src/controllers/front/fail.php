<?php

/**
 * pipwave Prestashop Plugin
 *
 * @author pipwave <support@pipwave.com>
 *
 */
class pipwaveFailModuleFrontController extends ModuleFrontController {

    public $display_column_left = false;
    public $display_column_right = false;
    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {
        if (!$this->module->active) {
            //Redirect user back to order since something is wrong
            Tools::redirect('index.php?controller=order&step=1');
        }
        $this->setTemplate('fail.tpl');
    }

}

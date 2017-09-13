{*
* 2007-2016 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<a id="pipwave_section"></a>
<br />
<div class="row">
    <div class="col-lg-12">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-money"></i>
                {l s='Paid with pipwave' mod='pipwave'}
            </div>
            {if isset($pipwave_head)}{$pipwave_head|escape:'htmlall':'UTF-8'}{/if}
            <form method="POST" action="#pipwave_section">
                <div class="row">
                    <div class="form-horizontal col-lg-8">
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='pipwave Reference ID: ' mod='pipwave'}</label>
                            <label class="control-label col-lg-5">
                                <div class="text-left"><a href="{$pipwave_merchant_portal_url|escape:'htmlall':'UTF-8'}reports/payment-transaction/view?pw_id={$pipwave_pw_id|escape:'htmlall':'UTF-8'}" target="_pipwave">{$pipwave_pw_id|escape:'htmlall':'UTF-8'} <i class="icon-external-link"></i></a></div>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-lg-3">{l s='Refund: ' mod='pipwave'}</label>
                            <div class="col-lg-5">
                                <input type="text" name="pipwave_refund_amount" value="{if isset($pipwave_refund_amount)}{$pipwave_refund_amount|floatval}{/if}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-9 col-lg-offset-3"><input type="submit" class="btn btn-default" value="{l s='refund' mod='pipwave'}" /></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
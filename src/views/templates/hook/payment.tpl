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
<style>
    #pm_pipwave {
        border: 1px solid #d6d4d4;
        border-radius: 4px;
        color: #333;
        display: block;
        font-size: 17px;
        font-weight: bold;
        position: relative;
        padding: 15px;
        margin-bottom: 10px;
    }
</style>
<div class="row">
    <div class="col-xs-12">
        {if $initiate_payment == 'success'}
            <div id="pwscript" class="text-center"></div>
            <div id="pwloading" style="text-align: center;">
                <img src="https://secure.pipwave.com/images/loading.gif" width="64" height="64" />
            </div>
            <script type="text/javascript">
                var pwconfig = {Tools::jsonEncode($api_data)|escape:'javascript':'UTF-8'};
                (function (_, p, w, s, d, k) {
                    var a = _.createElement("script");
                    a.setAttribute('data-main', w + s);
                    a.setAttribute('src', w + d);
                    a.setAttribute('id', k);
                    setTimeout(function () {
                        var reqPwInit = (typeof reqPipwave != 'undefined');
                        if (reqPwInit) {
                            reqPipwave.require(['pw'], function (pw) {
                                pw.setOpt(pwconfig);
                                pw.startLoad();
                            });
                        } else {
                            _.getElementById(k).parentNode.replaceChild(a, _.getElementById(k));
                        }
                    }, 800);
                })(document, 'script', "{$sdk_url|escape:'javascript':'UTF-8'}", "pw.sdk.min.js", "pw.sdk.min.js", "pwscript");
            </script>
        {else}
            <div id="pm_pipwave" class="payment_module">
                <p>
                    {$message|escape:'htmlall':'UTF-8'}
                </p>
            </div>
        {/if}
    </div>
</div>
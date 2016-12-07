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
<p class="alert alert-success" role="alert">{l s='Your order is being processed.' sprintf=$reference mod='pipwave'}</p>
<div class="box">
    <h3>{l s="Order reference : %s" sprintf=$reference mod='pipwave'}</h3>
    {l s='An email has been sent with the order details.' mod='pipwave'}
    <br />
    {l s='If you have questions, comments or concerns, ' mod='pipwave'} <a class="alert-link" href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='please contact us' mod='pipwave'}</a>.
</div>
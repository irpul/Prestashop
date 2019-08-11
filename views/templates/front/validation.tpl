{*
* iPresta.ir
*  @author iPresta.ir - Danoosh Miralayi
*  @copyright  2014-2015 iPresta.ir
*}

<div class="block-center" id="">
    <h2>پرداخت توسط ایرپول</h2>

    {include file="$tpl_dir./errors.tpl"}

    <p>{l s='Your order on' mod='irpul'} <span class="bold">{$shop_name}</span> {l s='is not complete.' mod='irpul'}
        <br /><br /><span class="bold">{l s='There is some errors in your payment.' mod='irpul'}</span>
        <br /><br />{l s='For any questions or for further information, please contact our' mod='irpul'} <a href="{$link->getPageLink('contact-form', true)}">{l s='customer support' mod='irpul'}</a>.
    </p>

    {if !empty($order_id) || !empty($tran_id)}
        <p class="required">{l s='Payment Details' mod='irpul'}:</p>
        <p>
            شماره سفارش: {$order_id}<br />
            شناسه تراکنش: {$tran_id}<br />
			کد پیگیری: {$refcode}
        </p>
		<br />
	{else}
		شماره سفارش یا شماره تراکنش موجود نیست
   {/if}
</div>
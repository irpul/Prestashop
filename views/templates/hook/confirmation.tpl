{l s='Your order on %s is complete.' sprintf=$shop_name mod='irpul'}
	{if !isset($reference)}
		<br /><br />{l s='Your order number' mod='irpul'}: {$id_order}
	{else}
		<br /><br />{l s='Your order number' mod='irpul'}: {$id_order}
		<br /><br />{l s='Your order reference' mod='irpul'}: {$reference}
	{/if}		<br /><br />{l s='An email has been sent with this information.' mod='irpul'}
	<br /><br /> <strong>{l s='Your order will be sent as soon as posible.' mod='irpul'}</strong>
	<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='irpul'} 
	<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='irpul'}</a>.
</p><br /> 
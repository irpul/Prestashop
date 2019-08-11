{*
* 2014-2015 iPresta.ir
*
*
*  @author iPresta.ir - Danoosh Miralayi
*  @copyright  2014-2015 iPresta.ir
*}

<div class="block-center" id="">
<h2>پرداخت توسط ایرپول</h2>

{include file="$tpl_dir./errors.tpl"}

{if isset($prepay) && $prepay}
	<br />
	<p>{l s='Connecting to gateway' mod='irpul'}...</p>
	<p>{l s='If there is problem on redirectiong click on payment button bellow' mod='irpul'}</p>
	<script type="text/javascript">
		setTimeout("document.forms.frmpayment.submit();",10);
	</script>
	<form name="frmpayment" action="{$redirect_link}" method="post">
		<input type="hidden" id="id" name="tid" value="{$tid}" />
		<input type="submit" class="button" value="{l s='Payment' mod='irpul'}" />
	</form>
	<p></p>
{/if}
</div>
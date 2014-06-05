<table class="dataTable">

	<tr>
		<th>{'OxidPlugin_PaymentType'|translate}</th>
		<th>{'OxidPlugin_Count'|translate}</th>
		<th>{'OxidPlugin_Sum'|translate}</th>
	</tr>

	<tr class="subDataTable">
		<td class="labelodd"><a href="javascript:broadcast.propagateAjax('module=OxidPlugin&action=oxidMenuCIAnotPaid')" style="text-decoration:underline;color:#255792;">{'OxidPlugin_CIA'|translate}</a></td>
		<td class="columnodd">{$countCIA}</td>
		<td class="columnodd" align="right"><span {if $openCIA < 0}style="color:red;"{/if}>{$openCIA|string_format:"%.2f"}&nbsp;{$siteCurrency}</span></td>
	</tr>

	<tr class="subDataTable">
		<td class="labeleven"><a href="javascript:broadcast.propagateAjax('module=OxidPlugin&action=oxidMenuCODnotReceived')" style="text-decoration:underline;color:#255792;">{'OxidPlugin_COD'|translate}</a></td>
		<td class="columneven">{$countCOD}</td>
		<td class="columneven" align="right"><span {if $openCOD < 0}style="color:red;"{/if}>{$openCOD|string_format:"%.2f"}&nbsp;{$siteCurrency}</span></td>
	</tr>

	<tr class="subDataTable">
		<td class="labelodd"><a href="javascript:broadcast.propagateAjax('module=OxidPlugin&action=oxidMenuInvoiceNotPaid')" style="text-decoration:underline;color:#255792;">{'OxidPlugin_Invoice'|translate}</a></td>
		<td class="columnodd">{$countInvoices}</td>
		<td class="columnodd" align="right"><span {if $openInvoices < 0}style="color:red;"{/if}>{$openInvoices|string_format:"%.2f"}&nbsp;{$siteCurrency}</span></td>
	</tr>

	<tr class="subDataTable">
		<td class="labeleven"><a href="javascript:broadcast.propagateAjax('module=OxidPlugin&action=oxidMenuPaidInAdvance')" style="text-decoration:underline;color:#255792;">{'OxidPlugin_Prepaid'|translate}</a></td>
		<td class="columneven">{$countPrepaid}</td>
		<td class="columneven" align="right"><span {if $openPrepaid < 0}style="color:red;"{/if}>{$openPrepaid|string_format:"%.2f"}&nbsp;{$siteCurrency}</span></td>
	</tr>

</table>

<div class="dataTableFeatures">
    <div class="datatableFooterMessage" style="margin-top:0px;">
        {'OxidPlugin_ClickForDetails'|translate}
    </div>
</div>
        
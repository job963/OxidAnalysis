<table class="dataTable">

	<tr>
		<th>{'OxidPlugin_PaymentType'|translate}</th>
		<th>{'OxidPlugin_Count'|translate}</th>
		<th>{'OxidPlugin_Sum'|translate}</th>
		<th>{'OxidPlugin_Percentage'|translate}</th>
	</tr>

	{section name=db loop=$data}
		<tr class="subDataTable">
			<td>{$data[db].oxdesc}</td>
			<td>{$data[db].ordercount}</td>
			<td align="right">{$data[db].totalordersum|string_format:"%.1f"}&nbsp;{$siteCurrency}</td>
			<td align="right">{$data[db].percentage|string_format:"%.1f"}&nbsp;%</td>
		</tr>
	{sectionelse}
		<tr class="subDataTable" colspan="5">
			<td>{'OxidPlugin_NoEntries'|translate}</td>
		</tr>
	{/section}

</table>
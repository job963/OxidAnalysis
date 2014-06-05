<table class="dataTable">

	<tr>
		<th>{'OxidPlugin_AgeClass'|translate}</th>
		<th>{'OxidPlugin_MenCount'|translate}</th>
		<th>{'OxidPlugin_MenRevenue'|translate}</th>
		<th>{'OxidPlugin_WomenCount'|translate}</th>
		<th>{'OxidPlugin_WomenRevenue'|translate}</th>
	</tr>

	{assign var="cellClass" value="even"}
        {section name=db loop=$data}
            {if $cellClass == "even"}
                {assign var="cellClass" value="odd"}
            {else}
                {assign var="cellClass" value="even"}
            {/if}
            <tr class="subDataTable">
			<td class="label{$cellClass}">{$data[db].ageclass}</td>
			<td class="column{$cellClass}" align="right">{$data[db].malecount|string_format:"%.1f"} %</td>
			<td class="column{$cellClass}" align="right">{$data[db].malerevenue|string_format:"%.1f"} %</td>
			<td class="column{$cellClass}" align="right">{$data[db].femalecount|string_format:"%.1f"} %</td>
			<td class="column{$cellClass}" align="right">{$data[db].femalerevenue|string_format:"%.1f"} %</td>
		</tr>
	{sectionelse}
		<tr class="subDataTable" colspan="5">
			<td>{'OxidPlugin_NoEntries'|translate}</td>
		</tr>
	{/section}

</table>
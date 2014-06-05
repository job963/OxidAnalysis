<table class="dataTable">

	<tr>
		<th>{'OxidPlugin_Days'|translate}</th>
		<th>{'OxidPlugin_OrderNo'|translate}</th>
		<th>{'OxidPlugin_Name'|translate}</th>
		<th>{'OxidPlugin_City'|translate}</th>
	</tr>

	{section name=db loop=$data}
		<tr class="subDataTable">
			<td><div title="{$data[db].oxsenddate}">{$data[db].days}</div></td>
			<td>{$data[db].oxordernr}</td>
			<td><a href="mailto:{$data[db].oxbillemail}"><span style="text-decoration:underline;">{$data[db].oxbillfname} {$data[db].oxbilllname}</span></a></td>
			<td>{$data[db].oxbillzip} {$data[db].oxbillcity}</td>
		</tr>
	{sectionelse}
		<tr class="subDataTable" colspan="5">
			<td>{'OxidPlugin_NoEntries'|translate}</td>
		</tr>
	{/section}

</table>
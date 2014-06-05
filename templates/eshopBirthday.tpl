<table class="dataTable">

	<tr>
		<th>{'OxidPlugin_Name'|translate}</th>
		<th>{'OxidPlugin_Age'|translate}</th>
	</tr>

	{section name=db loop=$data}
		<tr class="subDataTable">
			<td>
				{if $data[db].oxdboptin == 0}<img src="plugins/OxidPlugin/images/NewsInfoRed.jpg" alt="Not allowed" />
				{elseif $data[db].oxdboptin == 1}<img src="plugins/OxidPlugin/images/NewsInfoGreen.jpg" alt="Allowed" />
				{elseif $data[db].oxdboptin ==  2}<img src="plugins/OxidPlugin/images/NewsInfoAmber.jpg" alt="Not confirmed" />
				{else}
				{/if}
				<a href="mailto:{$data[db].oxusername}"><span style="text-decoration:underline;">{$data[db].oxfname} {$data[db].oxlname}</span></a>
			</td>
			<td>{$data[db].userage}</td>
		</tr>
	{sectionelse}
		<tr class="subDataTable" colspan="2">
			<td>{'OxidPlugin_NoEntries'|translate}</td>
		</tr>
	{/section}

</table>
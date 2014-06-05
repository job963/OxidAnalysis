<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Days'|translate}</th>
        <th>{'OxidPlugin_OrderNo'|translate}</th>
        <th>{'OxidPlugin_OrderSum'|translate}</th>
        <th>{'OxidPlugin_Name'|translate}</th>
        <th>{'OxidPlugin_City'|translate}</th>
    </tr>

    {section name=db loop=$data}
        <tr class="subDataTable">
            <td title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}">{$data[db].days}</td>
            <td title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}">{$data[db].oxordernr}</td>
            <td align="right" title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}">{$data[db].oxtotalordersum|string_format:"%.2f"}&nbsp;{$siteCurrency}</td>
            <td title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}"<a href="mailto:{$data[db].oxbillemail}"><span style="text-decoration:underline;">{$data[db].oxbillfname} {$data[db].oxbilllname}</span></a></td>
            <td title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}">{$data[db].oxbillzip} {$data[db].oxbillcity}</td>
        </tr>
    {sectionelse}
        <tr class="subDataTable" colspan="5">
            <td>{'OxidPlugin_NoEntries'|translate}</td>
        </tr>
    {/section}

</table>
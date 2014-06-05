
<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Days'|translate}</th>
        <th>{'OxidPlugin_ArtTitle'|translate}</th>
        <th>{'OxidPlugin_Remark'|translate}</th>
        <th>{'OxidPlugin_Rating'|translate}</th>
        {*<th>{'OxidPlugin_Name'|translate}</th>*}
    </tr>

    {section name=db loop=$data}
        {capture name="title_article"}{$data[db].oxartnum}{$nl}{$data[db].oxtitle}{$nl}{$data[db].oxprice} {$siteCurrency}{/capture}
        {capture name="title_text"}{$data[db].oxfname} {$data[db].oxlname}:{$nl}{$data[db].oxtext}{/capture}
        {capture name="title_address"}{$data[db].oxfname} {$data[db].oxlname}{$nl}{$data[db].oxstreet} {$data[db].oxstreetnr}{$nl}{$data[db].oxzip} {$data[db].oxcity}{/capture}
        <tr class="subDataTable">
            <td title="{$data[db].oxcreate}"><nobr>{$data[db].oxdays}</nobr></td>
            <td title="{$smarty.capture.title_article}">{$data[db].oxtitle}</td>
            <td title="{$smarty.capture.title_text}">{$data[db].oxtext|truncate:80:"..."}</td>
            <td title="{$smarty.capture.title_address}"><img src="plugins/OxidPlugin/images/rateds{$data[db].oxrating}.png" /></td>
            {*<td title="{$smarty.capture.title_text}">
                <a href="mailto:{$data[db].oxemail}"><span style="text-decoration:underline;"><nobr>{$data[db].oxfname} {$data[db].oxlname}</nobr></span></a>
            </td>*}
        </tr>
    {sectionelse}
        <tr class="subDataTable" colspan="5">
            <td>{'OxidPlugin_NoEntries'|translate}</td>
        </tr>
    {/section}

</table>
   
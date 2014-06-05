
<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Days'|translate}</th>
        <th>{'OxidPlugin_OrderNo'|translate}</th>
        <th>{'OxidPlugin_OrderSum'|translate}</th>
        <th>{'OxidPlugin_Name'|translate}</th>
        <th>{'OxidPlugin_Voucher'|translate}</th>
        <th>{'OxidPlugin_Discount'|translate}</th>
    </tr>

    {section name=db loop=$data}
        {capture name="title_text"}{$data[db].oxstreet} {$data[db].oxstreetnr}{$nl}{$data[db].oxzip} {$data[db].oxcity}{/capture}
        <tr class="subDataTable">
            <td title="{$data[db].oxdateused}">{$data[db].days}</td>
            <td>{$data[db].oxordernr}</td>
            <td align="right" title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}">{$data[db].oxtotalordersum|string_format:"%.2f"}&nbsp;{$siteCurrency}</td>
            <td title="{$smarty.capture.title_text}">
                <a href="mailto:{$data[db].oxusername}"><span style="text-decoration:underline;">{$data[db].oxbillfname} {$data[db].oxbilllname}</span></a>
            </td>
            <td title="{$data[db].oxseriedescription}">{$data[db].oxvouchernr}</td>
            <td>{$data[db].oxdiscount|string_format:"%.2f"}&nbsp;{if $data[db].oxdiscounttype == "absolute"}{$siteCurrency}{else}%{/if}</td>
        </tr>
    {sectionelse}
        <tr class="subDataTable" colspan="2">
            <td>{'OxidPlugin_NoEntries'|translate}</td>
        </tr>
    {/section}

</table>

<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Voucher'|translate}</th>
        <th>{'OxidPlugin_Used'|translate}</th>
        <th>{'OxidPlugin_Days'|translate}</th>
        <th>{'OxidPlugin_OrderSum'|translate}</th>
        <th>{'OxidPlugin_Discount'|translate}</th>
    </tr>

    {section name=db loop=$data}
        {capture name="title_total"}{'OxidPlugin_Count'|translate}: {$data[db].totalcount}{/capture}
        {capture name="title_average"}{'OxidPlugin_Average'|translate}: {$data[db].average|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
        <tr class="subDataTable">
            <td title="{$data[db].oxseriedescription}">{$data[db].oxvouchernr}</td>
            <td title="{$smarty.capture.title_total}">{$data[db].usedcount}</td>
            <td>{if $data[db].days != 0}{$data[db].days}{else}0{/if}</td>
            <td align="right" title="{$smarty.capture.title_average}">{$data[db].ordersum|string_format:"%.2f"}&nbsp;{$siteCurrency}</td>
            <td align="right">{$data[db].oxdiscount|string_format:"%.2f"}&nbsp;{if $data[db].oxdiscounttype == "absolute"}{$siteCurrency}{else}%{/if}</td>
        </tr>
    {sectionelse}
        <tr class="subDataTable" colspan="2">
            <td>{'OxidPlugin_NoEntries'|translate}</td>
        </tr>
    {/section}

</table>
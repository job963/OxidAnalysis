
<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Days'|translate}</th>
        <th>{'OxidPlugin_Name'|translate}</th>
        <th>{'OxidPlugin_State'|translate}</th>
        <th>{'OxidPlugin_Revenue'|translate}</th>
    </tr>

    {section name=db loop=$data}
        {capture name="title_address"}{$data[db].oxstreet} {$data[db].oxstreetnr}{$nl}{$data[db].oxzip} {$data[db].oxcity}{/capture}
        {capture name="title_registration"}{$data[db].oxsubscribed}{/capture}
        {capture name="title_orders"}{$data[db].ordercount} {'OxidPlugin_Orders'|translate}{/capture}
        <tr class="subDataTable">
            <td title="{$smarty.capture.title_registration}">{$data[db].oxdays}</td>
            <td title="{$smarty.capture.title_address}">
                <a href="mailto:{$data[db].oxemail}"><span style="text-decoration:underline;">{$data[db].oxfname} {$data[db].oxlname}</span></a>
            </td>
            <td title="{$smarty.capture.title_registration}">
                {if $data[db].oxdboptin == 0}
                    <img src="plugins/OxidPlugin/images/NewsInfoRed.jpg" alt="{'OxidPlugin_NewsDenied'|translate}" />
                    {'OxidPlugin_NewsDenied'|translate}
                {elseif $data[db].oxdboptin == 1}
                    <img src="plugins/OxidPlugin/images/NewsInfoGreen.jpg" alt="{'OxidPlugin_NewsAllowed'|translate}" />
                    {'OxidPlugin_NewsAllowed'|translate}
                {elseif $data[db].oxdboptin ==  2}
                    <img src="plugins/OxidPlugin/images/NewsInfoAmber.jpg" alt="{'OxidPlugin_NewsNotConfirmed'|translate}" />
                    {'OxidPlugin_NewsNotConfirmed'|translate}
                {else}
                {/if}
            </td>
            <td align="right" title="{$smarty.capture.title_orders}">{if $data[db].ordersum != 0}{$data[db].ordersum|string_format:"%.2f"}{else}0.00{/if}&nbsp;{$siteCurrency}</td>
        </tr>
    {sectionelse}
        <tr class="subDataTable" colspan="2">
            <td>{'OxidPlugin_NoEntries'|translate}</td>
        </tr>
    {/section}

</table>
{*
<script type="text/javascript">

var idSite = {$idSite};
 
function initFeedburner()
{
 
    function getName()
    {
        return $("#feedburnerName").val();
    }
    $("#feedburnerName").on("keyup", function(e) {
        if(isEnterKey(e)) {
            $("#feedburnerSubmit").click();
        }
    });
    $("#feedburnerSubmit").click( function(){
        var feedburnerName = getName();
        $.get('?module=ExampleFeedburner&action=saveFeedburnerName&idSite='+idSite+'&name='+feedburnerName);
        $(this).parents('[widgetId]').dashboardWidget('reload');
        initFeedburner();
    });
}
    
$(document).ready(function(){
    initFeedburner();
});
    
</script>
*}

<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Day'|translate}/{'OxidPlugin_Time'|translate}</th>
        <th>{'OxidPlugin_Name'|translate}</th>
        <th>{'OxidPlugin_PayType'|translate}</th>
        <th>{'OxidPlugin_Revenue'|translate}</th>
    </tr>

    {assign var="actdate" value="0000-00-00"}
    {section name=db loop=$data}
        {capture name="title_date"}{$data[db].orderdate}{/capture}
        {capture name="title_address"}{$data[db].oxbillstreet} {$data[db].oxbillstreetnr}{$nl}{$data[db].oxbillzip} {$data[db].oxbillcity}{/capture}
        {if $actdate == "0000-00-00"}
            {assign var="actdate" value=$data[db].orderdate}
            {assign var="cellClass" value="odd"}
        {/if}
        {if $data[db].orderdate != $actdate}
            {assign var="actdate" value=$data[db].orderdate}
            {if $cellClass == "even"}
                {assign var="cellClass" value="odd"}
            {else}
                {assign var="cellClass" value="even"}
            {/if}
        {/if}
        <tr class="subDataTable">
            <td title="{$smarty.capture.title_date}" class="label{$cellClass}">{$data[db].ordertime}</td>
            <td title="{$smarty.capture.title_address}" class="column{$cellClass}">{$data[db].oxbillfname} {$data[db].oxbilllname}</td>
            <td title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}" class="column{$cellClass}">{$data[db].paydesc}</td>
            <td align="right" title="{'OxidPlugin_Order'|translate}{$data[db].oxdetails}" class="column{$cellClass}">{if $data[db].oxtotalordersum != 0}{$data[db].oxtotalordersum|string_format:"%.2f"}{else}0.00{/if}&nbsp;{$siteCurrency}</td>
        </tr>
    {sectionelse}
        <tr class="subDataTable" colspan="2">
            <td>{'OxidPlugin_NoEntries'|translate}</td>
        </tr>
    {/section}

</table>
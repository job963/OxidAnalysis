{capture name="title_year"}{'OxidPlugin_Trend'|translate}: {if $trendYear>0}+{/if}{$trendYear|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$forecastYear|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
{capture name="title_month"}{'OxidPlugin_Trend'|translate}: {if $trendMonth>0}+{/if}{$trendMonth|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$forecastMonth|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
{capture name="title_week"}{'OxidPlugin_Trend'|translate}: {if $trendWeek>0}+{/if}{$trendWeek|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$forecastWeek|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
{capture name="title_day"}{'OxidPlugin_Trend'|translate}: {if $trendDay>0}+{/if}{$trendDay|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$forecastDay|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}

{assign var="actCellStyle" value="font-weight:bold;font-size:1.2em;"}
{assign var="actCellFooterStyle" value="font-size:0.71em;"}
{assign var="prevCellStyle" value="font-size:0.9em;"}
{assign var="prevCellFooterStyle" value="font-size:0.85em;"}

{if $period == "day"}
    {capture name="actRange"}{'OxidPlugin_Today'|translate}{/capture}
    {capture name="prevRange"}{'OxidPlugin_Yesterday'|translate}{/capture}
{elseif $period == "week"}
    {capture name="actRange"}{'OxidPlugin_ThisWeek'|translate}{/capture}
    {capture name="prevRange"}{'OxidPlugin_PrevWeek'|translate}{/capture}
{elseif $period == "month"}
    {capture name="actRange"}{'OxidPlugin_ThisMonth'|translate}{/capture}
    {capture name="prevRange"}{'OxidPlugin_PrevMonth'|translate}{/capture}
{elseif $period == "year"}
    {capture name="actRange"}{'OxidPlugin_ThisYear'|translate}{/capture}
    {capture name="prevRange"}{'OxidPlugin_PrevYear'|translate}{/capture}
{elseif $period == "range"}
    {capture name="actRange"}{'OxidPlugin_ThisRange'|translate}{/capture}
    {capture name="prevRange"}{'OxidPlugin_PrevRange'|translate}{/capture}
{elseif $period == "last30"}
    {capture name="actRange"}{'OxidPlugin_Last30'|translate}{/capture}
    {capture name="prevRange"}{'OxidPlugin_PrevRange'|translate}{/capture}
{/if}

<table border="0" width="100%">
    <tr>
        <td colspan="5" height="6"></td>
    </tr>
    <tr>
        <td width="6"></td>
        <td width="30%">
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_Ordered'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>{$countThisOrder}<br /><span style="{$actCellFooterStyle}">{$smarty.capture.actRange}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td><strong>{$countPrevOrder}</strong><br /><span style="{$prevCellFooterStyle};">{$smarty.capture.prevRange}</span></td>
                </tr>
            </table>
        </td>
        <td width="20" style="text-align:center;vertical-align:middle;">&#10152;</td>
        <td width="30%">
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_Ready2Send'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>{$countReady}<br /><span style="{$actCellFooterStyle}">{'OxidPlugin_Total'|translate}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td>{$sumReady|string_format:"%.2f"}&nbsp;{$siteCurrency}<br /><span style="{$prevCellFooterStyle};">{'OxidPlugin_GoodsValue'|translate}</span></td>
                </tr>
            </table>
        </td>
        <td width="20" style="text-align:center;vertical-align:middle;">&#10152;</td>
        <td width="30%">
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_Send'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>{$countThisSent}<br /><span style="{$actCellFooterStyle}">{$smarty.capture.actRange}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td>{$countPrevSent}<br /><span style="{$prevCellFooterStyle};">{$smarty.capture.prevRange}</span></td>
                </tr>
            </table>
        </td>
        <td width="6"></td>
    </tr>
    <tr>
        <td colspan="5" height="6"></td>
    </tr>
    
    {*
    <tr>
        <td width="6"></td>
        <td>
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_Ready2Send'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>0<br /><span style="{$actCellFooterStyle}">{$smarty.capture.actRange}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td>3<br /><span style="{$prevCellFooterStyle};">{$smarty.capture.prevRange}</span></td>
                </tr>
            </table>
        </td>
        <td width="12"></td>
        <td>
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_Cancelled'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>0<br /><span style="{$actCellFooterStyle}">{$smarty.capture.actRange}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td>0<br /><span style="{$prevCellFooterStyle};">{$smarty.capture.prevRange}</span></td>
                </tr>
            </table>
        </td>
        <td width="6"></td>
    </tr>
    <tr>
        <td colspan="5" height="6"></td>
    </tr>
    <tr>
        <td width="6"></td>
        <td>
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_Paid'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>{'OxidPlugin_ThisYear'|translate}<br /><span style="{$actCellFooterStyle}">{$smarty.capture.actRange}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td>{'OxidPlugin_PrevYear'|translate}<br /><span style="{$prevCellFooterStyle};">{$smarty.capture.prevRange}</span></td>
                </tr>
            </table>
        </td>
        <td width="12"></td>
        <td>
            <table class="dataTable" style="border-right:1px solid #e7e7e7;">
                <tr>
                    <th><strong>{'OxidPlugin_NotPaid'|translate}</stong></th>
                </tr>
                <tr class="subDataTable" align="left" style="{$actCellStyle}">
                    <td>{'OxidPlugin_ThisYear'|translate}<br /><span style="{$actCellFooterStyle}">{$smarty.capture.actRange}</span></td>
                </tr>
                <tr class="subDataTable" align="right" style="{$prevCellStyle}">
                    <td>{'OxidPlugin_PrevYear'|translate}<br /><span style="{$prevCellFooterStyle};">{$smarty.capture.prevRange}</span></td>
                </tr>
            </table>
        </td>
        <td width="6"></td>
    </tr>
    <tr>
        <td colspan="5" height="6"></td>
    </tr>
    *}
    
</table>
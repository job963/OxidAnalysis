{capture name="title_year"}{'OxidPlugin_Trend'|translate}: {if $revenueTrendYear>0}+{/if}{$revenueTrendYear|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$revenueForecastYear|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
{capture name="title_month"}{'OxidPlugin_Trend'|translate}: {if $revenueTrendMonth>0}+{/if}{$revenueTrendMonth|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$revenueForecastMonth|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
{capture name="title_week"}{'OxidPlugin_Trend'|translate}: {if $revenueTrendWeek>0}+{/if}{$revenueTrendWeek|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$revenueForecastWeek|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}
{capture name="title_day"}{'OxidPlugin_Trend'|translate}: {if $revenueTrendDay>0}+{/if}{$revenueTrendDay|string_format:"%d"}&nbsp;%{$nl}{'OxidPlugin_Forecast'|translate}: {$revenueForecastDay|string_format:"%.2f"}&nbsp;{$siteCurrency}{/capture}

<table class="dataTable">

    <tr>
        <th>{'OxidPlugin_Period'|translate}</th>
        <th>{'OxidPlugin_Count'|translate}</th>
        <th>{'OxidPlugin_Margin'|translate}</th>
        <th>{'OxidPlugin_Revenue'|translate}</th>
    </tr>

    <tr class="subDataTable">
        <td class="labelodd">{'OxidPlugin_PrevYear'|translate}</td>
        <td class="columnodd">{$countPrevYear}</td>
        <td class="columnodd" align="right">
            {$marginPrevYear|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
        <td class="columnodd" align="right">
            {$revenuePrevYear|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labeleven" title="{$smarty.capture.title_year}">{'OxidPlugin_ThisYear'|translate}</td>
        <td class="columneven" title="{$smarty.capture.title_year}">{$countThisYear}</td>
        <td class="columneven" align="right">
            {$marginThisYear|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $marginForecastYear > $marginPrevYear}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
        <td class="columneven" align="right" title="{$smarty.capture.title_year}">
            {$revenueThisYear|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $revenueForecastYear > $revenuePrevYear}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labelodd">{'OxidPlugin_PrevMonth'|translate}</td>
        <td class="columnodd">{$countPrevMonth}</td>
        <td class="columnodd" align="right">
            {$marginPrevMonth|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
        <td class="columnodd" align="right">
            {$revenuePrevMonth|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labeleven" title="{$smarty.capture.title_month}">{'OxidPlugin_ThisMonth'|translate}</td>
        <td class="columneven" title="{$smarty.capture.title_month}">{$countThisMonth}</td>
        <td class="columneven" align="right">
            {$marginThisMonth|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $marginForecastMonth > $marginPrevMonth}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
        <td class="columneven" align="right" title="{$smarty.capture.title_month}">
            {$revenueThisMonth|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $revenueForecastMonth > $revenuePrevMonth}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labelodd">{'OxidPlugin_PrevWeek'|translate}</td>
        <td class="columnodd">{$countPrevWeek}</td>
        <td class="columnodd" align="right">
            {$marginPrevWeek|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
        <td class="columnodd" align="right">
            {$revenuePrevWeek|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labeleven" title="{$smarty.capture.title_week}">{'OxidPlugin_ThisWeek'|translate}</td>
        <td class="columneven" title="{$smarty.capture.title_week}">{$countThisWeek}</td>
        <td class="columneven" align="right">
            {$marginThisWeek|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $marginForecastWeek > $marginPrevWeek}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
        <td class="columneven" align="right" title="{$smarty.capture.title_week}">
            {$revenueThisWeek|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $revenueForecastWeek > $revenuePrevWeek}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labelodd">{'OxidPlugin_Yesterday'|translate}</td>
        <td class="columnodd">{$countYesterday}</td>
        <td class="columnodd" align="right">
            {$marginYesterday|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
        <td class="columnodd" align="right">
            {$revenueYesterday|string_format:"%.2f"}&nbsp;{$siteCurrency}
            <img src="plugins/OxidPlugin/images/blank.png" />
        </td>
    </tr>

    <tr class="subDataTable">
        <td class="labeleven" title="{$smarty.capture.title_day}">{'OxidPlugin_Today'|translate}</td>
        <td class="columneven" title="{$smarty.capture.title_day}">{$countToday}</td>
        <td class="columneven" align="right">
            {$marginToday|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $marginForecastDay > $marginYesterday}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
        <td class="columneven" align="right" title="{$smarty.capture.title_day}">
            {$revenueToday|string_format:"%.2f"}&nbsp;{$siteCurrency}
            {if $revenueForecastDay > $revenueYesterday}
                <img src="plugins/OxidPlugin/images/up.png" />
            {else}
                <img src="plugins/OxidPlugin/images/down.png" />
            {/if}
        </td>
    </tr>
</table>

<div class="dataTableFeatures">
    <div class="datatableFooterMessage" style="margin-top:0px;">
        {$footerMessage}
    </div>
</div>

<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @copyright (c) 2011-2015, Joachim Barthel
 * @author Joachim Barthel <jobarthel@gmail.com>
 * @category Piwik_Plugins
 * @package OXID_Analysis
 */

namespace Piwik\Plugins\OxidAnalysis;

use Piwik\API\Request;
use Piwik\Common;
use Piwik\MetricsFormatter;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Site;
use Piwik\View;
use Piwik\ViewDataTable\Factory as ViewDataTableFactory;


class Controller extends \Piwik\Plugin\Controller
{

    /**
     * This widget shows the yearly, monthly and daily revenue of an OXID eShop
     **/
    function widgetRevenue()
    {

        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getRevenueSummary';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
	
        $view->config->columns_to_display = array('period', 'count', 'margin', 'revenue23');
        $view->config->translations['period'] = Piwik::translate('OxidAnalysis_Period');
        $view->config->translations['count'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['margin'] = Piwik::translate('OxidAnalysis_Margin');
        $view->config->translations['revenue23'] = Piwik::translate('OxidAnalysis_Revenue');

        $view->requestConfig->filter_sort_column = 'num';
        $view->requestConfig->filter_sort_order = 'asc';
        
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportRevenue\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_RevenueTitle')
                        . '</a>';
        
        return $view->render();
        
    }

        
    /**
     * Container for the Live Revenue widget, which add a scheduled refreshing
     **/
    function widgetLiveRevenue()
    {
        $output = '';
        $output .= "<SCRIPT LANGUAGE='javascript'>
                var myreload;
                $('document').ready(function(){
                    if (typeof myreload === 'undefined')
                        myreload = setInterval(myrefresh,60000);
                });
                function myrefresh () {
                    $('[widgetid=widgetOxidAnalysiswidgetLiveRevenue]').dashboardWidget('reload', false, true);
                }
            </SCRIPT>";

        $output .= $this->widgetLiveRevenueTable();

        return $output;
    }
    

    /**
     * This widget shows a table which displays the last orders
     **/
    function widgetLiveRevenueTable()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getLiveRevenue';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('oxbillname', 'orderdate', 'ordertime', 'oxtotalordersum');
        $view->config->translations['oxbillname'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['orderdate'] = Piwik::translate('OxidAnalysis_Date');
        $view->config->translations['ordertime'] = Piwik::translate('OxidAnalysis_Time');
        $view->config->translations['oxtotalordersum'] = Piwik::translate('OxidAnalysis_OrderSum');

        $view->config->enable_sort = false;
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportRevenue\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_RevenueTitle')
                        . '</a>';
        
        return $view->render();
    }


    /**
     * This widget compares the revenue of the last 180 days with the last 30 days
     **/
    function widgetRevenueAlert()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getRevenueAlerts';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('title', 'longrev', 'shortrev', 'missedtext', 'evolutiontext');
        $view->config->translations['title'] = Piwik::translate('OxidAnalysis_Manufacturer');
        $view->config->translations['longrev'] = Piwik::translate('OxidAnalysis_LastXDays',180);
        $view->config->translations['shortrev'] = Piwik::translate('OxidAnalysis_LastXDays',30);
        $view->config->translations['missedtext'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['evolutiontext'] = Piwik::translate('OxidAnalysis_OrderSum');

        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportRevenue\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_RevenueTitle')
                        . '</a>';
        
        return $view->render();
    }

        
    /**
     * This widget shows all customer have their birthday today
     **/
    function widgetBirthday()
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getBirthdayUsers';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('username', 'userage', 'ordercount', 'ordersum');
        $view->config->translations['username'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['userage'] = Piwik::translate('OxidAnalysis_Age');
        $view->config->translations['ordercount'] = Piwik::translate('OxidAnalysis_Orders');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_Sum');

        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportCODnotReceived\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_CODnotReceived')
                        . '</a>';*/
        
        return $view->render();
    }

        
    /**
     * This widget shows a comparison of the orders and shipping in the last 30 and 60 days
     **/
    function widgetLogistics()
    {
        $result = Request::processRequest('OxidAnalysis.getLogisticsData');

        $view = new View('@OxidAnalysis/widgetLogistics.twig');
        $this->setBasicVariablesView($view);
        
        $period = 'last30'; //Common::getRequestVar('period');
        switch ($period) {
            case 'day':
                $view->txtThisRange = Piwik::translate('OxidAnalysis_Today');
                $view->txtPrevRange = Piwik::translate('OxidAnalysis_Yesterday');
                break;

            case 'week':
                $view->txtThisRange = Piwik::translate('OxidAnalysis_ThisWeek');
                $view->txtPrevRange = Piwik::translate('OxidAnalysis_PrevWeek');
                break;

            case 'month':
                $view->txtThisRange = Piwik::translate('OxidAnalysis_ThisMonth');
                $view->txtPrevRange = Piwik::translate('OxidAnalysis_PrevMonth');
                break;

            case 'year':
                $view->txtThisRange = Piwik::translate('OxidAnalysis_ThisYear');
                $view->txtPrevRange = Piwik::translate('OxidAnalysis_PrevYear');
                break;

            case 'range':
                $view->txtThisRange = Piwik::translate('OxidAnalysis_ThisRange');
                $view->txtPrevRange = Piwik::translate('OxidAnalysis_PrevRange');
                break;

            case 'last30':
                $view->txtThisRange = Piwik::translate('OxidAnalysis_Last30');
                $view->txtPrevRange = Piwik::translate('OxidAnalysis_PrevRange');
                break;

            default:
                break;
        }
        
        $idSite = Common::getRequestVar('idSite');
        $view->siteCurrency = MetricsFormatter::getCurrencySymbol($idSite);
        
        $view->countThisOrder = $result['this']['ordered'];
        $view->countPrevOrder = $result['prev']['ordered'];
        $view->countThisSent = $result['this']['sent'];
        $view->countPrevSent = $result['prev']['sent'];
        $view->countReady = $result['open']['count'];
        $view->sumReady = $result['open']['sum'];

        return $view->render();
    }

        
    /**
     * This widget shows a table with onetime and returning customers analysis
     **/
    function widgetReturningCustomers()
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getReturningCustomers';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('ordercount', 'customercount');
        $view->config->translations['ordercount'] = Piwik::translate('OxidAnalysis_Orders');
        $view->config->translations['customercount'] = Piwik::translate('OxidAnalysis_Customers');

        $view->requestConfig->filter_sort_column = 'ordercount';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        //$view->config->show_all_views_icons = false;
        $view->config->show_insights = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportCODnotReceived\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_CODnotReceived')
                        . '</a>';*/
        
        return $view->render();
    }

        
    /**
     * This widget shows a table with referrers and their revenue
     **/
    function widgetRefererSummary()
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getRefererSummary';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('referername', 'ordercount', 'revenuesum');
        $view->config->translations['referername'] = Piwik::translate('OxidAnalysis_Referer');
        $view->config->translations['ordercount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['revenuesum'] = Piwik::translate('OxidAnalysis_Revenue');

        $view->requestConfig->filter_sort_column = 'referercount';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        //$view->config->show_all_views_icons = false;
        $view->config->show_insights = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportCODnotReceived\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_CODnotReceived')
                        . '</a>';*/
        
        return $view->render();
    }

        
	/*
	 * See the result on piwik/?module=OxidPlugin&action=feedbackWidget 
	 * or in the dashboard > Add a new widget. 
	 * This widget shows all customer have their birthday today
	 */
	function feedbackWidget()
	{
		include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
				
		$siteCurrency = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));
                $this->SiteID = Piwik_Common::getRequestVar('idSite');

                $view = Piwik_ViewDataTable::factory('table');
                $view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getFeedbackList' );

                $cols = array('senddate', 'days', 'orderno', 'name', 'ordersum');
                $view->setColumnsToDisplay($cols);
                $view->setColumnTranslation('senddate', Piwik_Translate('OxidPlugin_SendDate'));
                $view->setColumnTranslation('days', Piwik_Translate('OxidPlugin_Days'));
                $view->setColumnTranslation('orderno', Piwik_Translate('OxidPlugin_OrderNo'));
                $view->setColumnTranslation('name', Piwik_Translate('OxidPlugin_Name'));
                $view->setColumnTranslation('ordersum', Piwik_Translate('OxidPlugin_OrderSum'));
                $view->setSortedColumn('senddate', 'asc');

                $view->setLimit( 5 );
                $view->disableExcludeLowPopulation();
                //$view->disableFooterIcons();
                $view->alwaysShowLimitDropdown();
                $view->showLimitDropdown();
                $view->disableSearchBox();
                $view->disableRowEvolution();
                // hide metrics icons
                $view->disableShowAllColumns();
                // hide all chart icons
                $view->disableShowAllViewsIcons();
                $view->disableShowExportAsRssFeed();
                $linkText = ' <a href="javascript:broadcast.propagateAjax(\'module=OxidPlugin&action=oxidMenuFeedbackList\')">'
                            . Piwik_Translate('OxidPlugin_oxidWidgets') . ' - ' . Piwik_Translate('OxidPlugin_Feedback')
                            . '</a>';
                $view->setFooterMessage(Piwik_Translate('General_GoTo', $linkText));
                
                return $this->renderView($view);

	}

        
    /**
     * This widget shows the revenue sums for each country
     **/
    function widgetCountryRevenue()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getCountryRevenue';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('country', 'revenue');

        $cols = array('country', 'revenue');
        $view->config->translations['country'] = Piwik::translate('OxidAnalysis_Country');
        $view->config->translations['revenue'] = Piwik::translate('OxidAnalysis_Revenue');
        
        $view->requestConfig->filter_sort_column = 'revenue';
        $view->requestConfig->filter_sort_order = 'desc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_InvoiceNotPaid')
                        . '</a>';*/
        
        return $view->render();
    }

        
    /**
     * This widget shows the usage of the defined vouchers
     **/
    function widgetVoucherUse()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getVoucherUse';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('name', 'days', 'oxordernr', 'oxtotalordersum', 'oxvouchernr', 'oxdiscount');

        $cols = array('name', 'days', 'oxordernr', 'oxtotalordersum', 'oxvouchernr', 'oxdiscount');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['oxordernr'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['oxtotalordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['oxvouchernr'] = Piwik::translate('OxidAnalysis_Voucher');
        $view->config->translations['oxdiscount'] = Piwik::translate('OxidAnalysis_Discount');
        
        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_InvoiceNotPaid')
                        . '</a>';*/
        
        return $view->render();
    }

        
    /**
     * This widget shows all defined vouchers
     **/
    function widgetVoucherOverview()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getVoucherOverview';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('oxvouchernr', 'usedcount', 'totalcount', 'days', 'ordersum', 'oxdiscount');
        $view->config->translations['oxvouchernr'] = Piwik::translate('OxidAnalysis_Voucher');
        $view->config->translations['usedcount'] = Piwik::translate('OxidAnalysis_Used');
        $view->config->translations['totalcount'] = Piwik::translate('OxidAnalysis_Total');
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['oxdiscount'] = Piwik::translate('OxidAnalysis_Discount');

        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_InvoiceNotPaid')
                        . '</a>';*/
        
        return $view->render();
    }

        
    /**
     * This widget shows all customer haven't paid their cash in advance yet
     **/
    function widgetCIAnotPaid()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getOpenPayInAdvance';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
	
        $view->config->columns_to_display = array('days', 'oxordernr', 'oxtotalordersum', 'oxbillname', 'oxbilladdress');

        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['oxordernr'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['oxtotalordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['oxbillname'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['oxbilladdress'] = Piwik::translate('OxidAnalysis_City');

        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportPaidInAdvance\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_CIAnotPaidTitle')
                        . '</a>';
        
        return $view->render();
    }

	
    /**
     * This widget shows all customer where the cash on delivery isn't paid yet
     **/
    function widgetCODnotPaid()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getOpenCashOnDelivery';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
	
        $view->config->columns_to_display = array('days', 'oxordernr', 'oxtotalordersum', 'oxbillname', 'oxbilladdress');
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['oxordernr'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['oxtotalordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['oxbillname'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['oxbilladdress'] = Piwik::translate('OxidAnalysis_City');

        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportCODnotReceived\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_CODnotReceivedTitle')
                        . '</a>';
        
        return $view->render();
    }
	
	
    /**
     * This widget shows all customer haven't paid their invoice yet
     **/
    function widgetInvoiceNotPaid()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getOpenInvoices';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
	
        $view->config->columns_to_display = array('days', 'oxordernr', 'oxtotalordersum', 'oxbillname', 'oxbilladdress');
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['oxordernr'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['oxtotalordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['oxbillname'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['oxbilladdress'] = Piwik::translate('OxidAnalysis_City');

        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_InvoiceNotPaidTitle')
                        . '</a>';
        
        return $view->render();
    }
	
	
    /**
     * This widget shows the sums of each payment type of the actual year
     **/
    function widgetPaytypeSums()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getPayTypeSums';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('oxdesc', 'ordercount', 'totalordersum', 'percentage');
        $view->config->translations['oxdesc'] = Piwik::translate('OxidAnalysis_PayType');
        $view->config->translations['ordercount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['totalordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['percentage'] = Piwik::translate('OxidAnalysis_Percentage');

        $view->requestConfig->filter_sort_column = 'oxdesc';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 10;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;

        return $view->render();
    }
	
	
    /**
     * This widget shows the sums of the open payments for each payment type (all time)
     **/
    function widgetOpenPaytypeSums()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getOpenPaytypeSums';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('paytpe', 'count', 'open');
        $view->config->translations['paytpe'] = Piwik::translate('OxidAnalysis_PayType');
        $view->config->translations['count'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['open'] = Piwik::translate('OxidAnalysis_OrderSum');

        $view->requestConfig->filter_sort_column = 'paytpe';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 10;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        return $view->render();
    }

	
    /**
     * This widget shows the newest ratings and reviews
     **/
    function widgetRateAndReview()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getRatingsAndReviews';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('oxdays', 'oxtitle', 'shorttext', 'oxrating');

        $view->config->translations['oxdays'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['oxtitle'] = Piwik::translate('OxidAnalysis_ArtTitle');
        $view->config->translations['shorttext'] = Piwik::translate('OxidAnalysis_Remark');
        $view->config->translations['oxrating'] = Piwik::translate('OxidAnalysis_Rating');
        
        $view->requestConfig->filter_sort_column = 'oxdays';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_InvoiceNotPaid')
                        . '</a>';*/
        
        return $view->render();
    }

	
    /**
     * This widget shows the newest newsletter registrations
     **/
    function widgetNewsReg()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getNewsRegs';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
        
        $view->config->columns_to_display = array('oxname', 'oxdays', 'oxdboptin', 'ordercount', 'ordersum');

        $view->config->translations['oxdays'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['oxname'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['oxdboptin'] = Piwik::translate('OxidAnalysis_State');
        $view->config->translations['ordercount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        
        $view->requestConfig->filter_sort_column = 'oxdays';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
        
        /*$view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_InvoiceNotPaid')
                        . '</a>';*/
        
        return $view->render();
    }

	
    /**
     * This widget shows a matrix user-age x male/female x payment type
     **/
    function widgetUserAge()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getUserAge';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);
	
        $view->config->columns_to_display = array('ageclass', 'malecount', 'femalecount', 'malerevenue', 'femalerevenue');
        $view->config->translations['ageclass'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['malecount'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['femalecount'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['malerevenue'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['femalerevenue'] = Piwik::translate('OxidAnalysis_OrderNo');
        
        $view->requestConfig->filter_sort_column = 'ageclass';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 5;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        $view->config->enable_sort = false;
        $view->config->show_search = false;
        
        $view->config->show_footer_message = Piwik::translate('OxidAnalysis_More')
                        . ' <a href="javascript:broadcast.propagateAjax(\'module=OxidAnalysis&action=reportInvoiceNotPaid\')">'
                        . Piwik::translate('OxidAnalysis_OxidAnalysis') . ' - ' . Piwik::translate('OxidAnalysis_AgeAnalysisTitle')
                        . '</a>';
        
        return $view->render();
    }

    /**
     * Full page report which combines the rvenue graph and the revenue table
     **/
    function reportRevenue()
    {
        $output = '';
        $output .= '<h2>' . Piwik::translate('OxidAnalysis_RevenueTitle') . '</h2>';
        $output .= '<table width="100%">';
        
        $output .= '<tr><td>';
        //$output .= $this->graphRevenue(); //echoRevenueGraph()
        $output .= '</td></tr>';
        
        $output .= '<tr><td>';
        $output .= $this->tableRevenue();
        $output .= '</td></tr>';
        
        $output .= '</table>';
        return $output;
    }
	
	
    /**
     * Returns a table which contains the revenue of the selected period (for full page display)
     **/
    function tableRevenue()
    {
        $periodRange = Common::getRequestVar('period');
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getRevenue';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('artno', 'arttitle', 'artcount', 'artrev');
		
		 
        switch ($periodRange) {
            case 'day':
            case 'range':
                    $view->config->columns_to_display = array('dateval', 'count', 'custname', 'custdeladdr', 'average', 'netmargin', 'revsum', 'stornosum', 'payment', 'sendstate', 'remark');
                    $view->config->translations['dateval'] = Piwik::translate('OxidAnalysis_Time');
                    $view->config->translations['count'] = Piwik::translate('OxidAnalysis_OrderNo');
                    $view->config->translations['average'] = Piwik::translate('OxidAnalysis_ArtTitle');
                    $view->config->translations['custname'] = Piwik::translate('OxidAnalysis_Name');
                    $view->config->translations['custdeladdr'] = Piwik::translate('OxidAnalysis_City');
                    $view->config->translations['netmargin'] = Piwik::translate('OxidAnalysis_Margin');
                    $view->config->translations['revsum'] = Piwik::translate('OxidAnalysis_Revenue');
                    $view->config->translations['stornosum'] = Piwik::translate('OxidAnalysis_Storno');
                    $view->config->translations['payment'] = Piwik::translate('OxidAnalysis_Payment');
                    $view->config->translations['sendstate'] = Piwik::translate('OxidAnalysis_SendState');
                    $view->config->translations['remark'] = Piwik::translate('OxidAnalysis_Remark');
                    break;
                
            case 'week':
                    $view->config->columns_to_display = array('dateval', 'count', 'average', 'revsum', 'netmargin', 'netmarginperc', 'stornosum');
                    $view->config->translations['dateval'] = Piwik::translate('OxidAnalysisn_Week');
                    $view->config->translations['count'] = Piwik::translate('OxidAnalysis_Count');
                    $view->config->translations['average'] = Piwik::translate('OxidAnalysis_Average');
                    $view->config->translations['revsum'] = Piwik::translate('OxidAnalysis_Revenue');
                    $view->config->translations['netmargin'] = Piwik::translate('OxidAnalysis_Margin');
                    $view->config->translations['netmarginperc'] = Piwik::translate('OxidAnalysis_MarginPerc');
                    $view->config->translations['stornosum'] = Piwik::translate('OxidAnalysis_Storno');
                    break;
                
            case 'month':
                    $view->config->columns_to_display = array('dateval', 'count', 'average', 'revsum', 'netmargin', 'netmarginperc', 'stornosum');
                    $view->config->translations['dateval'] = Piwik::translate('OxidAnalysis_Month');
                    $view->config->translations['count'] = Piwik::translate('OxidAnalysis_Count');
                    $view->config->translations['average'] = Piwik::translate('OxidAnalysis_Average');
                    $view->config->translations['revsum'] = Piwik::translate('OxidAnalysis_Revenue');
                    $view->config->translations['netmargin'] = Piwik::translate('OxidAnalysis_Margin');
                    $view->config->translations['netmarginperc'] = Piwik::translate('OxidAnalysis_MarginPerc');
                    $view->config->translations['stornosum'] = Piwik::translate('OxidAnalysis_Storno');
                    break;
                
            case 'year':
                    $view->config->columns_to_display = array('dateval', 'count', 'average', 'revsum', 'netmargin', 'netmarginperc', 'stornosum');
                    $view->config->translations['dateval'] = Piwik::translate('OxidAnalysis_Year');
                    $view->config->translations['count'] = Piwik::translate('OxidAnalysis_Count');
                    $view->config->translations['average'] = Piwik::translate('OxidAnalysis_Average');
                    $view->config->translations['revsum'] = Piwik::translate('OxidAnalysis_Revenue');
                    $view->config->translations['netmargin'] = Piwik::translate('OxidAnalysis_Margin');
                    $view->config->translations['netmarginperc'] = Piwik::translate('OxidAnalysis_MarginPerc');
                    $view->config->translations['stornosum'] = Piwik::translate('OxidAnalysis_Storno');
                    break;
        }
        
        $view->requestConfig->filter_sort_column = 'dateval';
        $view->requestConfig->filter_sort_order = 'desc';
        
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
        return $view->render();
    }
	
	
    /**
     * Full page report which loads the table with all ready to send orders
     **/
    function reportReadyToSend()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_ReadyToSendTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableReadyToSend();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows all ready to send orders
     **/
    function tableReadyToSend()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getReadyToSend';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

        $view->config->columns_to_display  = array('days', 'orderdate', 'orderno', 'company', 'name', 'custdeladdr', 'orderlist', 'ordersum', 'paytype', 'remark');
        
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['orderdate'] = Piwik::translate('OxidAnalysis_OrderDate');
        $view->config->translations['orderno'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['company'] = Piwik::translate('OxidAnalysis_Company');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['custdeladdr'] = Piwik::translate('OxidAnalysis_City');
        $view->config->translations['orderlist'] = Piwik::translate('OxidAnalysis_OrderList');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['paytype'] = Piwik::translate('OxidAnalysis_PayType');
        $view->config->translations['remark'] = Piwik::translate('OxidAnalysis_Remark');
        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
        return $view->render();
    }
	
	
    /**
     * Full page report which loads the table containing the latest ratings and reviews
     **/
    function reportFeedback()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_FeedbackTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableFeedback();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows the latest ratings and reviews
     **/
    function tableFeedback()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getFeedbackList';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

        $view->config->columns_to_display  = array('orderdate', 'days', 'senddate', 'orderno', 'trackcode', 'company', 'name', 'email', 'city', 'ordersum' );
        
        $view->config->translations['orderdate'] = Piwik::translate('OxidAnalysis_OrderDate');
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['senddate'] = Piwik::translate('OxidAnalysis_SendDate');
        $view->config->translations['orderno'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['trackcode'] = Piwik::translate('OxidAnalysis_TrackingCode');
        $view->config->translations['company'] = Piwik::translate('OxidAnalysis_Company');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['email'] = Piwik::translate('OxidAnalysis_EMail');
        $view->config->translations['city'] = Piwik::translate('OxidAnalysis_City');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->requestConfig->filter_sort_column = 'orderdate';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
    }
	
	
    /**
     * Full page report which loads the table with all not paid CIA orders
     **/
    function reportCIAnotPaid()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_CIAnotPaidTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableCIAnotPaid();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows all not paid CIA orders
     **/
    function tableCIAnotPaid()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getCIAnotPaid';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('days', 'orderdate', 'orderno', 'company', 'name', 'custdeladdr', 'orderlist', 'ordersum', 'remark');
        
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['orderdate'] = Piwik::translate('OxidAnalysis_OrderDate');
        $view->config->translations['orderno'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['company'] = Piwik::translate('OxidAnalysis_Company');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['custdeladdr'] = Piwik::translate('OxidAnalysis_City');
        $view->config->translations['orderlist'] = Piwik::translate('OxidAnalysis_OrderList');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['remark'] = Piwik::translate('OxidAnalysis_Remark');
        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
	return $view->render();
    }
	
	
    /**
     * Full page report which loads the table with all not received COD orders
     **/
    function reportCODnotReceived()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_CODnotReceivedTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableCODnotReceived();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows all not paid COD orders
     **/
    function tableCODnotReceived()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getCODnotReceived';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('days', 'senddate', 'orderno', 'trackcode', 'company', 'name', 'custdeladdr', 'orderlist', 'ordersum', 'remark');
		
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['senddate'] = Piwik::translate('OxidAnalysis_SendDate');
        $view->config->translations['orderno'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['trackcode'] = Piwik::translate('OxidAnalysis_TrackingCode');
        $view->config->translations['company'] = Piwik::translate('OxidAnalysis_Company');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['custdeladdr'] = Piwik::translate('OxidAnalysis_City');
        $view->config->translations['orderlist'] = Piwik::translate('OxidAnalysis_OrderList');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['remark'] = Piwik::translate('OxidAnalysis_Remark');
        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
	return $view->render();
    }
	
	
    /**
     * Full page report which loads the table with all not paid invoice orders
     **/
    function reportInvoiceNotPaid()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_InvoiceNotPaidTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableInvoiceNotPaid();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows all not paid invoice orders
     **/
    function tableInvoiceNotPaid()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getInvoiceNotPaid';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('days', 'senddate', 'orderno', 'trackcode', 'company', 'name', 'custdeladdr', 'orderlist', 'ordersum', 'remark');
		
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['senddate'] = Piwik::translate('OxidAnalysis_SendDate');
        $view->config->translations['orderno'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['trackcode'] = Piwik::translate('OxidAnalysis_TrackingCode');
        $view->config->translations['company'] = Piwik::translate('OxidAnalysis_Company');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['custdeladdr'] = Piwik::translate('OxidAnalysis_City');
        $view->config->translations['orderlist'] = Piwik::translate('OxidAnalysis_OrderList');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['remark'] = Piwik::translate('OxidAnalysis_Remark');
        
        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
	return $view->render();
    }
	
	
    /**
     * Full page report which loads the table with all yet paid CIA orders
     **/
    function reportPaidInAdvance()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_PaidInAdvanceTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tablePaidInAdvance();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows all yet paid CIA orders
     **/
    function tablePaidInAdvance()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getPrePaid';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('days', 'orderdate', 'orderno', 'company', 'name', 'custdeladdr', 'orderlist', 'ordersum', 'remark');
		
        $view->config->translations['days'] = Piwik::translate('OxidAnalysis_Days');
        $view->config->translations['orderdate'] = Piwik::translate('OxidAnalysis_OrderDate');
        $view->config->translations['orderno'] = Piwik::translate('OxidAnalysis_OrderNo');
        $view->config->translations['company'] = Piwik::translate('OxidAnalysis_Company');
        $view->config->translations['name'] = Piwik::translate('OxidAnalysis_Name');
        $view->config->translations['custdeladdr'] = Piwik::translate('OxidAnalysis_City');
        //$view->config->translations['paytype'] = Piwik::translate('OxidAnalysis_PayType');
        $view->config->translations['orderlist'] = Piwik::translate('OxidAnalysis_OrderList');
        $view->config->translations['ordersum'] = Piwik::translate('OxidAnalysis_OrderSum');
        $view->config->translations['remark'] = Piwik::translate('OxidAnalysis_Remark');
        $view->requestConfig->filter_sort_column = 'days';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
	return $view->render();
    }
	
	
    /**
     * Full page report which loads two graphs and two tables for time analysis
     **/
    function reportTimeRevenue()
    {
            /*$view = Piwik_View::factory('eshopTimeRevenue');
            $view->daytimeRevenueGraph = $this->echoDaytimeRevenueGraph(true) ;
            $view->weekdayRevenueGraph = $this->echoWeekdayRevenueGraph(true);
            $view->daytimeRevenueTable = $this->oxidGetDaytimeRevenue(true) ;
            $view->weekdayRevenueTable = $this->oxidGetWeekdayRevenue(true);
            echo $view->render();*/
        $output = '<h2>' . Piwik::translate('OxidAnalysis_TimeRevenueTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= '<table width="100%">';
        $output .= '<tr><td>';
        //$output .= $this->graphDaytimeRevenue();
        $output .= '</td>';
        $output .= '<td>';
        //$output .= $this->graphWeekdayRevenue();
        $output .= '</td></tr>';
        $output .= '<tr><td>';
        $output .= $this->tableDaytimeRevenue();
        $output .= '</td>';
        $output .= '<td>';
        $output .= $this->tableWeekdayRevenue();
        $output .= '</td></tr>';
        $output .= '<table>';
        
        return $output;
    }
	
	

        
        function echoDaytimeRevenueGraph( $fetch = false )
	{
            
            $view = Piwik_ViewDataTable::factory('graphEvolution');
            $view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getDaytimeGraph' );
            
            $view->setColumnTranslation('revenue', Piwik_Translate('OxidPlugin_Revenue'));
            $view->setColumnTranslation('cancel', Piwik_Translate('OxidPlugin_Storno'));
            $view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite')));
            return $this->renderView($view, $fetch);
            
	}
	
	
        function echoWeekdayRevenueGraph( $fetch = false )
	{

            $view = Piwik_ViewDataTable::factory('graphEvolution');
            $view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getWeekdayGraph' );
            
            $view->setColumnTranslation('revenue', Piwik_Translate('OxidPlugin_Revenue'));
            $view->setColumnTranslation('cancel', Piwik_Translate('OxidPlugin_Storno'));
            $view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite')));
            return $this->renderView($view, $fetch);
            
	}

        
        
    /**
     * Returns a table which shows the revenues sums per daytime
     **/
    function tableDaytimeRevenue( $fetch = false )
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getDaytimeAnalysis';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('daytime', 'revenue23', 'revshare', 'cancel', 'cancshare', 'cancpercent');
		
        $view->config->translations['daytime'] = Piwik::translate('OxidAnalysis_Time');
        $view->config->translations['revenue23'] = Piwik::translate('OxidAnalysis_Revenue');
        $view->config->translations['revshare'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->config->translations['cancel'] = Piwik::translate('OxidAnalysis_Storno');
        $view->config->translations['cancshare'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->config->translations['cancpercent'] = Piwik::translate('OxidAnalysis_StornoPercentage');
        $view->requestConfig->filter_sort_column = 'daytime';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
    }
	
	
    /**
     * Returns a table which shows the revenues sums per weekday
     **/
    function tableWeekdayRevenue( $fetch = false )
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getWeekdayAnalysis';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('dayname', 'revenue23', 'revshare', 'cancel', 'cancshare', 'cancpercent');

        $view->config->translations['dayname'] = Piwik::translate('OxidAnalysis_Weekday');
        $view->config->translations['revenue23'] = Piwik::translate('OxidAnalysis_Revenue');
        $view->config->translations['revshare'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->config->translations['cancel'] = Piwik::translate('OxidAnalysis_Storno');
        $view->config->translations['cancshare'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->config->translations['cancpercent'] = Piwik::translate('OxidAnalysis_StornoPercentage');
        $view->requestConfig->filter_sort_column = 'dayname';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
    }

    
    /**
     * Full page report which loads two tables of Tops and Flops
     **/
    function reportTopsnFlops()
    {
        $output = '';
        $output .= '<table width="100%"><tr>';
        
        $output .= '<td>'.'<h3>' . Piwik::translate('OxidAnalysis_TopSeller') . '</h3>';
        $output .= $this->tableTopSeller();
        $output .= '</td>';
        
        $output .= '<td>'.'<h3>' . Piwik::translate('OxidAnalysis_TopCancels') . '</h3>';
        $output .= $this->tableTopCancels();
        $output .= '</td>';
        
        $output .= '</tr></table>';
        return $output;
    }
        
    
    /**
     * Returns a table which shows the top seller
     **/
    function tableTopSeller()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getTopSeller';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('artno', 'arttitle', 'artcount', 'artrev', 'artmargin');
		
        $view->config->translations['artno'] = Piwik::translate('OxidAnalysis_ArtNo');
        $view->config->translations['arttitle'] = Piwik::translate('OxidAnalysis_ArtTitle');
        $view->config->translations['artcount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['artrev'] = Piwik::translate('OxidAnalysis_Revenue');
        $view->config->translations['artmargin'] = Piwik::translate('OxidAnalysis_Margin');
        $view->requestConfig->filter_sort_column = 'artcount';
        $view->requestConfig->filter_sort_order = 'desc';
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
        return $view->render();
    }
	
	
    /**
     * Returns a table which shows the flop seller
     **/
    function tableTopCancels()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getTopCancels';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

	$view->config->columns_to_display = array('artno', 'arttitle', 'artcount', 'artrev', 'artmargin');
		
        $view->config->translations['artno'] = Piwik::translate('OxidAnalysis_ArtNo');
        $view->config->translations['arttitle'] = Piwik::translate('OxidAnalysis_ArtTitle');
        $view->config->translations['artcount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['artrev'] = Piwik::translate('OxidAnalysis_Revenue');
        $view->config->translations['artmargin'] = Piwik::translate('OxidAnalysis_Margin');
        $view->requestConfig->filter_sort_column = 'artcount';
        $view->requestConfig->filter_sort_order = 'desc';
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
	}
	
	
    /**
     * Full page report which loads age analysis table
     **/
    function reportAgeAnalysis()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_AgeAnalysisTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableAgeAnalysis();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows a matrix age class x mewn/women x payment type
     **/
    function tableAgeAnalysis()
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
		
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getAgeAnalysis';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

        $aDispCols = array('ageclass', 'mencount', 'womencount', 'menrevenue', 'womenrevenue');
        $payments = getOxPayments($this);
        foreach ($payments as $payment)
            array_push($aDispCols, $payment);
        $view->config->columns_to_display  = $aDispCols;
		//$view->setColumnsToDisplay($cols);
        $view->config->translations['ageclass'] = Piwik::translate('OxidAnalysis_AgeClass');
        $view->config->translations['mencount'] = Piwik::translate('OxidAnalysis_MenCount2');
        $view->config->translations['womencount'] = Piwik::translate('OxidAnalysis_WomenCount2');
        $view->config->translations['menrevenue'] = Piwik::translate('OxidAnalysis_MenRevenue2');
        $view->config->translations['womenrevenue'] = Piwik::translate('OxidAnalysis_WomenRevenue2');
        $view->requestConfig->filter_sort_column = 'ageclass';
        $view->requestConfig->filter_sort_order = 'asc';

        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;
        
        return $view->render();
    }
	
	
    /**
     * Full page report which loads store status table
     **/
    function reportStoreStatus()
    {
        $output = '<h2>' . Piwik::translate('OxidAnalysis_StoreStatusTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= $this->tableStoreStatus();
        
        return $output;
    }
	
	
    /**
     * Returns a table which shows the stock status of the products
     **/
    function tableStoreStatus()
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getStoreStatus';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

        $view->config->columns_to_display = array('oxartnum', 'oxtitle', 'oxvarselect', 'oxdelivery', 'oxnostocktext');

        $view->config->translations['oxartnum'] = Piwik::translate('OxidAnalysis_ArtNo');
        $view->config->translations['oxtitle'] = Piwik::translate('OxidAnalysis_ArtTitle');
        $view->config->translations['oxvarselect'] = Piwik::translate('OxidAnalysis_VarTitle');
        $view->config->translations['oxdelivery'] = Piwik::translate('OxidAnalysis_DeliveryDate');
        $view->config->translations['oxnostocktext'] = Piwik::translate('OxidAnalysis_DeliveryInfo');
        $view->requestConfig->filter_sort_column = 'oxartnum';
        $view->requestConfig->filter_sort_order = 'asc';
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
    }
	
	
    function oxidMenuManufacturerRevenue()
    {

        $output = '<h2>' . Piwik::translate('OxidAnalysis_ManufacturerRevenueTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= '<table width="100%">';
        $output .= '<tr><td colspan="2">';
                // $this->echoTop5DelivererRevenueGraph();
        $output .= '</td></tr>';
        $output .= '<tr><td style="vertical-align:top;" width="50%"><p> </p>';
		// $this->echoManuRevenueGraph();
        $output .= '</td><td style="vertical-align:top;" width="50%"><p></p><p>';
        $output .= $this->tableTopManufacturerRevenue();
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        
        return $output;
    }
    
    
    function tableTopManufacturerRevenue()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getManufacturerRevenue';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

        $view->config->columns_to_display = array('deliverer', 'totalcount', 'netbuysum', 'netmargin', 'percentnet', 'brutsum', 'percentbrut');
        $view->config->translations['deliverer'] = Piwik::translate('OxidAnalysis_Manufacturer');
        $view->config->translations['totalcount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['netbuysum'] = Piwik::translate('OxidAnalysis_NetBuy');
        $view->config->translations['netmargin'] = Piwik::translate('OxidAnalysis_Margin');
        $view->config->translations['percentnet'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->config->translations['brutsum'] = Piwik::translate('OxidAnalysis_Revenue');
        $view->config->translations['percentbrut'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->requestConfig->filter_sort_column = 'brutsum';
        $view->requestConfig->filter_sort_order = 'desc';
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
        
    }
	
	
    function oxidMenuVendorRevenue()
    {

        $output = '<h2>' . Piwik::translate('OxidAnalysis_VendorRevenueTitle') . '</h2>';
        $output .= '<div style="height:12px;"></div>';
        $output .= '<table width="100%">';
        $output .= '<tr><td colspan="2">';
                // $this->echoTop5DelivererRevenueGraph();
        $output .= '</td></tr>';
        $output .= '<tr><td style="vertical-align:top;" width="50%"><p> </p>';
		// $this->echoVendorRevenueGraph();
        $output .= '</td><td style="vertical-align:top;" width="50%"><p></p><p>';
        $output .= $this->tableTopVendorRevenue();
        $output .= '</td>';
        $output .= '</tr>';
        $output .= '</table>';
        
        return $output;
    }
    
    
    function tableTopVendorRevenue()
    {
        $controllerAction = $this->pluginName . '.' . __FUNCTION__;
        $apiAction = 'OxidAnalysis.getVendorRevenue';

        $view = ViewDataTableFactory::build('table', $apiAction, $controllerAction);

        $view->config->columns_to_display = array('deliverer', 'totalcount', 'netbuysum', 'netmargin', 'percentnet', 'brutsum', 'percentbrut');
        $view->config->translations['deliverer'] = Piwik::translate('OxidAnalysis_Vendor');
        $view->config->translations['totalcount'] = Piwik::translate('OxidAnalysis_Count');
        $view->config->translations['netbuysum'] = Piwik::translate('OxidAnalysis_NetBuy');
        $view->config->translations['netmargin'] = Piwik::translate('OxidAnalysis_Margin');
        $view->config->translations['percentnet'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->config->translations['brutsum'] = Piwik::translate('OxidAnalysis_Revenue');
        $view->config->translations['percentbrut'] = Piwik::translate('OxidAnalysis_Percentage');
        $view->requestConfig->filter_sort_column = 'brutsum';
        $view->requestConfig->filter_sort_order = 'desc';
        $view->config->disable_row_evolution  = true;
        $view->config->show_search = false;
		
        $view->requestConfig->filter_limit = 25;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_all_views_icons = false;
        $view->config->disable_row_evolution  = true;

        return $view->render();
        
    }
	
	
	function echoRevenueGraph()
	{
            $view = Piwik_ViewDataTable::factory('graphEvolution');
            $view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getRevenueEvolution' );
            
            /**/
            $view->setColumnTranslation('revenue', Piwik_Translate('OxidPlugin_Revenue'));
            $view->setColumnTranslation('netmargin', Piwik_Translate('OxidPlugin_Margin'));
            //$view->setColumnTranslation('netmarginperc', Piwik_Translate('OxidPlugin_MarginPerc'));
            $view->setColumnTranslation('canceled', Piwik_Translate('OxidPlugin_Storno'));
            //$view->setColumnTranslation('canceledperc', Piwik_Translate('OxidPlugin_StornoPerc'));
            
            $view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite')));
            /**/
            
            //$view->initChartObjectData();
            
            /*$columnNames = array('revenue', 'netmargin', 'canceled');
            foreach ($columnNames as $columnName) {
                $columnNameTranslations[$columnName] = Piwik_Translate($columnName);
                $columnNameValues[$columnName] = $columnName;
            }
            //$columnNameTranslations = array('revenue'=>Piwik_Translate('OxidPlugin_Revenue'), 'netmargin'=>Piwik_Translate('OxidPlugin_Margin'), 'canceled'=>Piwik_Translate('OxidPlugin_Storno'));
            //$columnNameValues = array('revenue'=>'revenue', 'netmargin'=>'netmargin', 'canceled'=>'canceled');
            
            $view->setAxisYValues($columnNameValues);
            $view->setAxisYLabels($columnNameTranslations);
            
            $view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite')));
            $aUnits[] = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));
            $aUnits[] = '%';
            $aUnits[] = '%';
            $view->setAxisYUnits($aUnits);/**/
            
            //$view->addSeriesPickerToView();
            /**/
            return $this->renderView($view);
	}
	
	
	function echoTop5DelivererRevenueGraph()
	{
            include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
            $view = Piwik_ViewDataTable::factory('graphEvolution');
            $view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getTop5DelivererRevenueGraph' );
            
            $labelNames = $this->getTop5DelivererRevenueNames();
            for ($i=0; $i<count($labelNames); $i++)
                $view->setColumnTranslation("revenue{$i}", $labelNames[$i]['oxtitle']);

            $view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'))); 
            return $this->renderView($view);
	}
        
        
        //
        public function getTop5DelivererRevenueNames()
        {
            include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

            if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueNames(): WELCOME');
            $this->SiteID = Piwik_Common::getRequestVar('idSite');
            $db = openDB($this);

            $date = Piwik_Common::getRequestVar('date');
            $period = Piwik_Common::getRequestVar('period');
            $this->Currency = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));
            if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueNames(): date='.$date);
            if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueNames(): period='.$period);
            
            if ($period == 'range') {
                $dateStart = substr($date, 0, strpos($date, ',')); 
                $dateEnd = substr($date, strpos($date, ',')+1);  
            } else {
                $timePeriod = new Piwik_Period_Range($period, 'last3');
                $dateStart = $timePeriod->getDateStart()->toString('Y-m-d'); 
                $dateEnd = $timePeriod->getDateEnd()->toString('Y-m-d');
            }
            if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueNames: AFTER PERIOD  ');
            
            // retrieve the top5 deliverer
            //$sql = "SELECT m.oxid, m.oxtitle, SUM(d.oxamount) AS artcount, SUM(d.oxbrutprice) AS sumprice "
            $sql = "SELECT  m.oxtitle "
                 . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
                 . "WHERE d.oxartid = a.oxid "
                        . "AND IF(a.oxmanufacturerid!='', "
                            . "a.oxmanufacturerid, "
                            . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                            . "= m.oxid "
                        . "AND d.oxstorno = 0 "
                        . "AND d.oxorderid = o.oxid "
                        . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                        . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                 . "GROUP BY m.oxtitle "
                 . "ORDER BY SUM(d.oxbrutprice) DESC "
                 . "LIMIT 0, 5 ";

            if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueNames: '.$sql);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $dbData = $stmt->fetchAll(PDO::FETCH_NAMED);
            if ($this->DebugMode) logfile('debug', $dbData);
            
            return $dbData;
        }
	
	
	function echoManuRevenueGraph()
	{
		$view = Piwik_ViewDataTable::factory('graphVerticalBar');
		$view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getManufacturerRevenueGraph' );
		$view->setColumnTranslation('value', Piwik_Translate('OxidPlugin_Revenue'));
		$view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite')));        // useful if the user requests the bar graph
		$view->setGraphLimit( 100 );
		$view->disallowPercentageInGraphTooltip();
		return $this->renderView($view);
	}
	
	
	function echoVendorRevenueGraph()
	{
		$view = Piwik_ViewDataTable::factory('graphVerticalBar');
		$view->init( $this->pluginName,  __FUNCTION__, 'OxidPlugin.getVendorRevenueGraph' );
		$view->setColumnTranslation('value', Piwik_Translate('OxidPlugin_Revenue'));
		$view->setAxisYUnit(Piwik::getCurrency(Piwik_Common::getRequestVar('idSite')));        // useful if the user requests the bar graph
		$view->setGraphLimit( 100 );
		$view->disallowPercentageInGraphTooltip();
		return $this->renderView($view);
	}
	
	
}

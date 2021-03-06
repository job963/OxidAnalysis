<?php
/**
 * Piwik - Open source web analytics
 * 
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * 
 * @copyright (c) 2011-2014, Joachim Barthel
 * @author Joachim Barthel <jobarthel@gmail.com>
 * @category Piwik_Plugins
 * @package OXID_Analysis
 */

namespace Piwik\Plugins\OxidAnalysis;
use Piwik\Menu\MenuMain;
use Piwik\Menu\MenuTop;
use Piwik\Piwik;
use Piwik\WidgetsList;


require_once PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/functions.php';


/**
 *
 * @package Piwik_OxidPlugin
 */
class OxidAnalysis extends \Piwik\Plugin
{
    
    public function getListHooksRegistered()
    {
        return array(
            'Menu.Reporting.addItems'  => 'addReportingMenuItems',
            'WidgetsList.addWidgets'   => 'addWidgets'
            /* not used yet 'Menu.Top.addItems'       => 'addTopMenuItems', */
        );
    }
    
    function addReportingMenuItems()
    {
        MenuMain::getInstance()->add(Piwik::translate('OxidAnalysis_OxidAnalysis'), '', array('module' => 'OxidAnalysis', 'action' => 'dataTables'), true, 30);

        $i = 1;
        $this->addSubMenu(Piwik::translate('OxidAnalysis_Revenue'), 'reportRevenue', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_TimeRevenue'), 'reportTimeRevenue', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_ReadyToSend'), 'reportReadyToSend', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_CIAnotPaid'), 'reportCIAnotPaid', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_CODnotReceived'), 'reportCODnotReceived', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_InvoiceNotPaid'), 'reportInvoiceNotPaid', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_PaidInAdvance'), 'reportPaidInAdvance', $i++);
        //--$this->addSubMenu(Piwik::translate('OxidAnalysis_TopSeller'), 'oxidMenuTopSeller', $i++);
        //--$this->addSubMenu(Piwik::translate('OxidAnalysis_TopCancels'), 'oxidMenuTopCancels', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_TopsnFlops'), 'reportTopsnFlops', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_AgeAnalysis'), 'reportAgeAnalysis', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_StoreStatus'), 'reportStoreStatus', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_ManufacturerRevenue'), 'oxidMenuManufacturerRevenue', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_VendorRevenue'), 'oxidMenuVendorRevenue', $i++);
        $this->addSubMenu(Piwik::translate('OxidAnalysis_Feedback'), 'reportFeedback', $i++);
    }	

    
    function addTopMenuItems()
    {
        $urlParams = array('module' => 'OxidAnalysis1', 'action' => 'notifications');
        MenuTop::getInstance()->addEntry('OXID Analysis', $urlParams, $displayedForCurrentUser = true, $order = 3);
    }

    
    private function addSubMenu($subMenu, $action, $order)
    {
        MenuMain::getInstance()->add(Piwik::translate('OxidAnalysis_OxidAnalysis'), $subMenu, array('module' => 'OxidAnalysis', 'action' => $action), true, $order);
    }


    public function  addWidgets()
    {
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetRevenue', 'OxidAnalysis', 'widgetRevenue');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetCIAnotPaid', 'OxidAnalysis', 'widgetCIAnotPaid');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetCODnotPaid', 'OxidAnalysis', 'widgetCODnotPaid');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetInvoiceNotPaid', 'OxidAnalysis', 'widgetInvoiceNotPaid');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetBirthday', 'OxidAnalysis', 'widgetBirthday');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetPaytypeSums', 'OxidAnalysis', 'widgetPaytypeSums');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetPayment', 'OxidAnalysis', 'widgetOpenPaytypeSums');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetUserAge', 'OxidAnalysis', 'widgetUserAge');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetLiveRevenue', 'OxidAnalysis', 'widgetLiveRevenue');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetNewsReg', 'OxidAnalysis', 'widgetNewsReg');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetRateAndReview', 'OxidAnalysis', 'widgetRateAndReview');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetLogistice', 'OxidAnalysis', 'widgetLogistics'); // nicht fertig
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetVoucherUse', 'OxidAnalysis', 'widgetVoucherUse');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetVoucherOverview', 'OxidAnalysis', 'widgetVoucherOverview');
        WidgetsList::add('OXID Analysis', 'OxidAnalysis_widgetRevenueAlert', 'OxidAnalysis', 'widgetRevenueAlert');
    }
    
    
    function postLoad()
    {
            // we register the widgets so they appear in the "Add a new widget" window in the dashboard
            // Note that the first two parameters can be either a normal string, or an index to a translation string
            include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

    }
}


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

use Piwik\Menu\MenuReporting;
use Piwik\Menu\MenuUser;
use Piwik\Piwik;
use Piwik\Plugin\Manager as PluginManager;

class Menu extends \Piwik\Plugin\Menu
{
    private function addSubMenu(MenuReporting $menu, $subMenu, $action, $order)
    {
        logfile('debug', '*** Function Menu//addSubMenu called ***');
        $menu->add(Piwik::translate('OxidAnalysis_OxidAnalysis'), $subMenu, array('module' => 'OxidAnalysis', 'action' => $action), true, $order);
    }
    
    
    public function configureReportingMenu(MenuReporting $menu)
    {
        logfile('debug', '*** Function Menu//configureReportingMenu called ***');
        $menu->add(Piwik::translate('OxidAnalysis_OxidAnalysis'), '', array('module' => 'OxidAnalysis', 'action' => 'reportRevenue'), true, 30);

        $i = 1;
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_Revenue'), 'reportRevenue', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_TimeRevenue'), 'reportTimeRevenue', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_ReadyToSend'), 'reportReadyToSend', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_CIAnotPaid'), 'reportCIAnotPaid', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_CODnotReceived'), 'reportCODnotReceived', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_InvoiceNotPaid'), 'reportInvoiceNotPaid', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_PaidInAdvance'), 'reportPaidInAdvance', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_TopsnFlops'), 'reportTopsnFlops', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_AgeAnalysis'), 'reportAgeAnalysis', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_StoreStatus'), 'reportStoreStatus', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_ManufacturerRevenue'), 'oxidMenuManufacturerRevenue', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_VendorRevenue'), 'oxidMenuVendorRevenue', $i++);
        $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_Feedback'), 'reportFeedback', $i++);
    }
    
}

?>
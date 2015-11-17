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

use Piwik\Common;
use Piwik\Menu\MenuReporting;
use Piwik\Menu\MenuUser;
use Piwik\Piwik;
use Piwik\Plugin\Manager as PluginManager;

class Menu extends \Piwik\Plugin\Menu
{
    private function addSubMenu(MenuReporting $menu, $subMenu, $action, $order)
    {
        //logfile('debug', '*** Function Menu//addSubMenu called ***');
        $menu->registerMenuIcon(Piwik::translate('OxidAnalysis_OxidAnalysis'), 'icon-ecommerce-order');
        $menu->add(Piwik::translate('OxidAnalysis_OxidAnalysis'), $subMenu, array('module' => 'OxidAnalysis', 'action' => $action), true, $order);
    }
    
    
    public function configureReportingMenu(MenuReporting $menu)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $this->SiteID = Common::getRequestVar('idSite');

        //logfile('debug', '*** Function Menu//configureReportingMenu called ***');
        $menu->add(Piwik::translate('OxidAnalysis_OxidAnalysis'), '', array('module' => 'OxidAnalysis', 'action' => 'reportRevenue'), true, 30);

        $i = 1;
        
        if ($this->EnableMenuRevenue[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_Revenue'), 'reportRevenue', $i++);
        
        if ($this->EnableMenuTimeRevenue[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_TimeRevenue'), 'reportTimeRevenue', $i++);
        
        if ($this->EnableMenuReadyToSend[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_ReadyToSend'), 'reportReadyToSend', $i++);
        
        if ($this->EnableMenuCIAnotPaid[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_CIAnotPaid'), 'reportCIAnotPaid', $i++);
        
        if ($this->EnableMenuCODnotReceived[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_CODnotReceived'), 'reportCODnotReceived', $i++);
        
        if ($this->EnableMenuInvoiceNotPaid[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_InvoiceNotPaid'), 'reportInvoiceNotPaid', $i++);
        
        if ($this->EnableMenuPaidInAdvance[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_PaidInAdvance'), 'reportPaidInAdvance', $i++);
        
        if ($this->EnableMenuTopsnFlops[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_TopsnFlops'), 'reportTopsnFlops', $i++);
        
        if ($this->EnableMenuAgeAnalysis[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_AgeAnalysis'), 'reportAgeAnalysis', $i++);
        
        if ($this->EnableMenuStoreStatus[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_StoreStatus'), 'reportStoreStatus', $i++);
        
        if ($this->EnableMenuManufacturerRevenue[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_ManufacturerRevenue'), 'oxidMenuManufacturerRevenue', $i++);
        
        if ($this->EnableMenuVendorRevenue[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_VendorRevenue'), 'oxidMenuVendorRevenue', $i++);
        
        if ($this->EnableMenuFeedback[$this->SiteID])
            $this->addSubMenu($menu, Piwik::translate('OxidAnalysis_Feedback'), 'reportFeedback', $i++);
    }
    
}

?>
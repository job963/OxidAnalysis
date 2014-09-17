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

use PDO;
use PDOException;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\MetricsFormatter;
use Piwik\Period;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Site;


class API extends \Piwik\Plugin\API
{
	
    // Retrieving the revenue values from OXID eSales
    public function getRevenue($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        if ($this->DebugMode) {
            $payments = getOxPayments($this);
            foreach ($payments as $payment)
                logfile('debug', $payment);
        }

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        if ($this->DebugMode) logfile('debug', 'period='.$period);
        switch ($period) {
            case 'range':
                    // daily revenue
                    if ($this->CheckInventory[$this->SiteID])
                        $sStockInfo = "'<span style=\"color:#000;background-color:', IF(IFNULL((SELECT j.jxinvstock FROM jxinvarticles j WHERE j.jxartid=a.oxartid),0)<a.oxamount,'#ffcd7d','#9de49c'), ';height:15px;width:20px;border-radius:3px;text-align:center;float:left;\">', IFNULL((SELECT j.jxinvstock FROM jxinvarticles j WHERE j.jxartid=a.oxartid),0), '</span>&nbsp;', ";
                    else 
                        $sStockInfo = "";
                    $sql = "SELECT o.oxorderdate AS dateval, o.oxordernr as count, "
                             . "GROUP_CONCAT(CONCAT('<nobr>', {$sStockInfo} a.oxamount,' x ', REPLACE(REPLACE(REPLACE(REPLACE(a.oxtitle,'&','&amp;'),'<','&lt;'),'>','&gt;'),'\"','&quot;'), IF (a.oxselvariant != '', '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', ''), a.oxselvariant, '</nobr>') SEPARATOR '<br>') AS average, "
                             . "o.oxtotalordersum AS @SUMVAL@, p.oxdesc AS payment, "
                             . "@MARGIN@"
                             . "CONCAT('<a href=\"mailto:', o.oxbillemail,'\"><u><nobr>', o.oxbillfname, ' ', o.oxbilllname, '</nobr></u></a>') AS custname, "
                             . "IF (o.oxdelcity = '', "
                                . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                                . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                                . ") AS  custdeladdr, "
                             . "IF (o.oxsenddate != '0000-00-00 00:00:00', "
                                . "IF(o.oxtrackcode != '', "
                                     . "CONCAT(DATE(o.oxsenddate), ' <a href=\"',"
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE(o.oxtrackcode,"
                                            . "'DHL',CONCAT('http://nolp.dhl.de/nextt-online-public/set_identcodes.do?extendedSearch=false&rfn=&searchQuick=Suchen&idc=')),"
                                            . "'ILX','https://www.iloxx.de/net/popup/trackpop.aspx?id='),"
                                            . "'UPS','http://wwwapps.ups.com/WebTracking/processRequest?HTMLVersion=5.0&Requester=NES&AgreeToTermsAndConditions=yes&loc=de_DE&tracknum='),"
                                            . "'DPD','http://extranet.dpd.de/cgi-bin/delistrack?typ=1&lang=de&pknr='),"
                                            . "'GLS','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/DE03/DE/5004.htm?txtAction=71000&txtRefNo='),"
                                            . "'HMS','http://tracking.hlg.de/Tracking.jsp?TrackID='),"
                                            . "'\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxtrackcode, '</a>'), "
                                            . "DATE(o.oxsenddate) "
                                    . "), "
                                . "'-' "
                                . ") AS sendstate, "
                                . "IF(o.oxremark!='', "
                                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                                        . "''"
                                    . "), "
                                    . "''"
                                . ") AS remark "
                             . "FROM oxorder o, oxorderarticles a, oxpayments p " 
                             . "WHERE  o.oxid = a.oxorderid  "
                                . "AND o.oxpaymenttype = p.oxid "
                                . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                                . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                                . "AND o.oxstorno = @STORNOVAL@ "
                                . "AND a.oxstorno = @STORNOVAL@ "
                             . "GROUP BY o.oxordernr "
                             . "ORDER BY o.oxordernr "; 

                    $margin = "(SUM(a.oxnetprice) - "
                                . "(SELECT SUM("
                                    . "IF(a1.oxparentid='', "
                                        . "a1.oxbprice, "
                                        . "IF(a1.oxbprice=0.0, (SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), a1.oxbprice))"
                                    . "*d1.oxamount) "
                                    . "FROM oxarticles a1, oxorderarticles d1 "
                                    . "WHERE d1.oxartid = a1.oxid "
                                        . "AND o.oxid = d1.oxorderid "
                                        . "AND d1.oxstorno = 0)) "
                                . "AS netmargin, ";
                    break;

            case 'day':
                    if ($this->CheckInventory[$this->SiteID])
                        $sStockInfo = "'<span style=\"color:#000;background-color:', IF(IFNULL((SELECT j.jxinvstock FROM jxinvarticles j WHERE j.jxartid=a.oxartid),0)<a.oxamount,'#ffcd7d','#9de49c'), ';height:15px;width:20px;border-radius:3px;text-align:center;float:left;\">', IFNULL((SELECT j.jxinvstock FROM jxinvarticles j WHERE j.jxartid=a.oxartid),0), '</span>&nbsp;', ";
                    else 
                        $sStockInfo = "";
                    $sql = "SELECT TIME(o.oxorderdate) AS dateval, o.oxordernr as count, "
                             . "GROUP_CONCAT('<nobr>', CONCAT({$sStockInfo} a.oxamount,' x ', REPLACE(REPLACE(REPLACE(REPLACE(a.oxtitle,'&','&amp;'),'<','&lt;'),'>','&gt;'),'\"','&quot;'), IF (a.oxselvariant != '', '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', ''), a.oxselvariant, '</nobr>') SEPARATOR '<br>') AS average, "
                             . "o.oxtotalordersum AS @SUMVAL@, p.oxdesc AS payment , "
                             . "@MARGIN@"
                             . "CONCAT('<a href=\"mailto:', o.oxbillemail,'\"><u><nobr>', o.oxbillfname, ' ', o.oxbilllname, '</nobr></u></a>') AS custname, "
                             . "IF (o.oxdelcity = '', "
                                . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                                . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelstreet, '&nbsp;', o.oxdelstreetnr, ', ', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                                . ") AS  custdeladdr, "
                             . "IF (o.oxsenddate != '0000-00-00 00:00:00', "
                                . "IF(o.oxtrackcode != '', "
                                     . "CONCAT(DATE(o.oxsenddate), ' <a href=\"',"
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE("
                                            . "REPLACE(o.oxtrackcode,"
                                            . "'DHL',CONCAT('http://nolp.dhl.de/nextt-online-public/set_identcodes.do?extendedSearch=false&rfn=&searchQuick=Suchen&idc=')),"
                                            . "'ILX','https://www.iloxx.de/net/popup/trackpop.aspx?id='),"
                                            . "'UPS','http://wwwapps.ups.com/WebTracking/processRequest?HTMLVersion=5.0&Requester=NES&AgreeToTermsAndConditions=yes&loc=de_DE&tracknum='),"
                                            . "'DPD','http://extranet.dpd.de/cgi-bin/delistrack?typ=1&lang=de&pknr='),"
                                            . "'GLS','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/DE03/DE/5004.htm?txtAction=71000&txtRefNo='),"
                                            . "'HMS','http://tracking.hlg.de/Tracking.jsp?TrackID='),"
                                            . "'\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxtrackcode, '</a>'), "
                                            . "DATE(o.oxsenddate) "
                                    . "), "
                                . "'-' "
                                . ") AS sendstate, "
                                . "IF(o.oxremark!='', "
                                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                                        . "''"
                                    . "), "
                                    . "''"
                                . ") AS remark "
                             . "FROM oxorder o, oxorderarticles a, oxpayments p " 
                             . "WHERE  o.oxid = a.oxorderid  "
                                . "AND o.oxpaymenttype = p.oxid "
                                . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                                . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                                . "AND o.oxstorno = @STORNOVAL@ "
                                . "AND a.oxstorno = @STORNOVAL@ "
                             . "GROUP BY o.oxordernr "
                             . "ORDER BY o.oxordernr "; 

                    $margin = "(SUM(a.oxnetprice) - "
                                . "(SELECT SUM("
                                    . "IF(a1.oxparentid='', "
                                        . "a1.oxbprice, "
                                        . "IF(a1.oxbprice=0.0, (SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), a1.oxbprice))"
                                    . "*d1.oxamount) "
                                    . "FROM oxarticles a1, oxorderarticles d1 "
                                    . "WHERE d1.oxartid = a1.oxid "
                                        . "AND o.oxid = d1.oxorderid "
                                        . "AND d1.oxstorno = 0)) "
                                . "AS netmargin, ";
                    break;

                    case 'week':
                    // daily revenue
                    $sql = "SELECT "
                             . "CONCAT(CAST(YEAR(o.oxorderdate) AS CHAR(4)), '-', CAST(week(o.oxorderdate,3) AS CHAR(2))) AS dateval, "
                             . "FORMAT(AVG(o.oxtotalordersum),2) AS average, "
                             . "SUM(o.oxtotalordersum) AS @SUMVAL@, "
                             . "@MARGIN@"
                             . "COUNT(*) AS count "
                             . "FROM oxorder o "
                             . "WHERE "
                                . "DATE(o.oxorderdate) >= DATE_SUB('{$dateStart}', INTERVAL 30 WEEK) " 
                                . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                                . "AND o.oxstorno = @STORNOVAL@ "
                             . "GROUP BY "
                                . "dateval "; 

                    $margin = "(SELECT "
                                . "SUM(ROUND(d3.oxnetprice,2)) "
                             . "FROM "
                                . "oxorderarticles d3, oxorder o3 "
                             . "WHERE "
                                . "o3.oxid = d3.oxorderid "
                                . "AND YEAR(o3.oxorderdate) = YEAR(o.oxorderdate) AND WEEK(o3.oxorderdate,3) = WEEK(o.oxorderdate,3) "
                                . "AND d3.oxstorno = 0) "
                             . " - "
                             . "(SELECT "
                                . "SUM(ROUND(IF("
                                    . "a1.oxparentid='', "
                                    . "a1.oxbprice, "
                                    . "IF("
                                        . "a1.oxbprice=0.0, "
                                        . "(SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), "
                                        . "a1.oxbprice)),2) "
                                    . "*d1.oxamount) "
                                . "FROM "
                                    . "oxarticles a1, oxorderarticles d1, oxorder o1 "
                                . "WHERE "
                                    . "d1.oxartid = a1.oxid "
                                    . "AND d1.oxorderid = o1.oxid "
                                    . "AND YEAR(o1.oxorderdate) = YEAR(o.oxorderdate) AND WEEK(o1.oxorderdate,3) = WEEK(o.oxorderdate,3) "
                                    . "AND d1.oxstorno = 0) "
                             . "AS netmargin, ";
                    break;

            case 'month':
                     // weekly revenue
                    $sql = "SELECT "
                             . "CONCAT(CAST(YEAR(o.oxorderdate) AS CHAR(4)), '-', CAST(MONTH(o.oxorderdate) AS CHAR(2))) as dateval, "
                             . "FORMAT(AVG(o.oxtotalordersum),2) AS average, "
                             . "SUM(o.oxtotalordersum) AS @SUMVAL@, "
                             . "@MARGIN@"
                             . "COUNT(*) AS count "
                             . "FROM oxorder  o "
                             . "WHERE "
                                . "DATE(o.oxorderdate) >= DATE_SUB('{$dateStart}', INTERVAL 30 MONTH) " 
                                . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                                . "AND o.oxstorno = @STORNOVAL@ "
                             . "GROUP BY "
                                . "dateval "; 

                    $margin = "(SELECT "
                                . "SUM(ROUND(d3.oxnetprice,2)) "
                             . "FROM "
                                . "oxorderarticles d3, oxorder o3 "
                             . "WHERE "
                                . "o3.oxid = d3.oxorderid "
                                . "AND YEAR(o3.oxorderdate) = YEAR(o.oxorderdate) AND MONTH(o3.oxorderdate) = MONTH(o.oxorderdate) "
                                . "AND d3.oxstorno = 0) "
                             . " - "
                             . "(SELECT "
                                . "SUM(ROUND(IF("
                                    . "a1.oxparentid='', "
                                    . "a1.oxbprice, "
                                    . "IF("
                                        . "a1.oxbprice=0.0, "
                                        . "(SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), "
                                        . "a1.oxbprice)),2) "
                                    . "*d1.oxamount) "
                                . "FROM "
                                    . "oxarticles a1, oxorderarticles d1, oxorder o1 "
                                . "WHERE "
                                    . "d1.oxartid = a1.oxid "
                                    . "AND d1.oxorderid = o1.oxid "
                                    . "AND YEAR(o1.oxorderdate) = YEAR(o.oxorderdate) AND MONTH(o1.oxorderdate) = MONTH(o.oxorderdate) "
                                    . "AND d1.oxstorno = 0) "
                             . "AS netmargin, ";
                    break;

            case 'year':
                     // monthly revenue
                    $sql = "SELECT extract(year FROM o.oxorderdate) AS dateval, "
                             . "AVG(o.oxtotalordersum) AS average, "
                             . "SUM(o.oxtotalordersum) AS @SUMVAL@, "
                             . "@MARGIN@"
                             . "COUNT(*) AS count "
                             . "FROM oxorder o "
                             . "WHERE "
                                . "DATE(o.oxorderdate) >= DATE_SUB('{$dateStart}', INTERVAL 30 YEAR) " 
                                . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                                . "AND o.oxstorno = @STORNOVAL@ "
                             . "GROUP BY "
                                . "extract(year FROM o.oxorderdate) "; 

                    $margin = "(SELECT "
                                . "SUM(ROUND(d3.oxnetprice,2)) "
                             . "FROM "
                                . "oxorderarticles d3, oxorder o3 "
                             . "WHERE "
                                . "o3.oxid = d3.oxorderid "
                                . "AND YEAR(o3.oxorderdate) = dateval "
                                . "AND d3.oxstorno = 0) "
                             . " - "
                             . "(SELECT "
                                . "SUM(ROUND(IF("
                                    . "a1.oxparentid='', "
                                    . "a1.oxbprice, "
                                    . "IF("
                                        . "a1.oxbprice=0.0, "
                                        . "(SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), "
                                        . "a1.oxbprice)),2) "
                                    . "*d1.oxamount) "
                                . "FROM "
                                    . "oxarticles a1, oxorderarticles d1, oxorder o1 "
                                . "WHERE "
                                    . "d1.oxartid = a1.oxid "
                                    . "AND d1.oxorderid = o1.oxid "
                                    . "AND YEAR(o1.oxorderdate) = dateval "
                                    . "AND d1.oxstorno = 0) "
                             . "AS netmargin, ";
                    break;


            case 'old-year':
                    // yearly revenue
                    $sql = "SELECT extract(year FROM oxorderdate) AS dateval, "
                             . "AVG(oxtotalordersum) AS average, "
                             . "SUM(oxtotalordersum) AS @SUMVAL@, "
                             . "COUNT(*) AS count "
                             . "FROM oxorder "
                             . "WHERE "
                                . "DATE(oxorderdate) >= '{$dateStart}' " 
                                . "AND DATE(oxorderdate) <= '{$dateEnd}' "
                                . "AND oxshopid = {$this->ShopID[$this->SiteID]} "
                             . "GROUP BY "
                             . "extract(year FROM oxorderdate) "; 
                     break;

        }

        $sql1 = str_replace('@STORNOVAL@', '0', $sql);
        $sql1 = str_replace('@SUMVAL@', 'revsum', $sql1);
        $sql1 = str_replace('@MARGIN@', $margin, $sql1);
        if ($this->DebugMode) logfile('debug', 'getRevenue: '.$sql1);

        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql1);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getPrePaid: '.$sql1 );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData1 = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getPrePaid: pdo->execute error = '.$e->getMessage() );
            die();
        }
        if (($this->DebugMode) && (is_bool($dbData1)))
                logfile('error', 'getRevenue: [ERROR] '.$sql1);

        $sql2 = str_replace('@STORNOVAL@', '1', $sql);
        $sql2 = str_replace('@SUMVAL@', 'stornosum', $sql2);
        $sql2 = str_replace('@MARGIN@', '\'-\' AS netmargin, ', $sql2);

        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql2);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getPrePaid: '.$sql2 );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData2 = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getPrePaid: pdo->execute error = '.$e->getMessage() );
            die();
        }
        if (is_bool($dbData2))
            logfile('error', 'getRevenue: [ERROR] '.$sql2);

        $db = null;

        $sumRevenue = 0.0;
        $sumMargin = 0.0;
        $i = 0;
        foreach($dbData1 as $value) {
            $sumRevenue += $value['revsum'];
            $sumMargin += $value['netmargin'];
            $dbData1[$i]['count'] = intFormat($dbData1[$i]['count'], $this);
            if (($period != 'day')&&($period != 'range')) {
                $dbData1[$i]['average'] = $this->oaCurrFormat($dbData1[$i]['average'], $this);
                if ( $dbData1[$i]['revsum'] > 0.0 )
                    $dbData1[$i]['netmarginperc'] = $dbData1[$i]['netmargin'] / $dbData1[$i]['revsum'] * 100.0;
                else
                    $dbData1[$i]['netmarginperc'] = 0.0;
                $dbData1[$i]['netmarginperc'] = percFormat($dbData1[$i]['netmarginperc'], $this);
            }
            $dbData1[$i]['revsum'] = $this->oaCurrFormat($dbData1[$i]['revsum'], $this);
            $dbData1[$i]['netmargin'] = $this->oaCurrFormat($dbData1[$i]['netmargin'], $this);
            if (($period == 'day') || ($period == 'range')) {
                if (strpos($dbData1[$i]['sendstate'], 'http') === FALSE) {
                    $newURL = 'href="' . $this->CarrierTrackingUrl[$this->SiteID] ;
                    $dbData1[$i]['sendstate'] = str_replace('href="', $newURL, $dbData1[$i]['sendstate']);
                }
            }
            $i++;
        }
        $sumStorno = 0.0;
        $i = 0;
        foreach($dbData2 as $value) {
            $dbData2[$i]['dateval'] = $dbData2[$i]['dateval'] . ' ';    // just for sorting
            $sumStorno += $value['stornosum'];
            $dbData2[$i]['count'] = intFormat($dbData2[$i]['count'], $this);
            if (($period != 'day')&&($period != 'range')) {
                $dbData2[$i]['average'] = $this->oaCurrFormat($dbData2[$i]['average'], $this);
            }
            $dbData2[$i]['stornosum'] = $this->oaCurrFormat($dbData2[$i]['stornosum'], $this);
            $i++;
        }
        $this->Style = 'font-weight:bold;';
        if ($sumRevenue == 0.0) {
            if ($sumStorno == 0.0) 
                $txtStorno = $this->oaCurrFormat($sumStorno, $this);
            else
                $txtStorno = $this->oaCurrFormat($sumStorno, $this). ' (100.0%)';
        } else {
            $txtStorno = $this->oaCurrFormat($sumStorno, $this);
            $this->Style = '';
            $txtStorno .= percFormat($sumStorno/$sumRevenue*100.0, $this);
        }

        $dbData = array_merge( $dbData1, $dbData2 );
            
        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        $this->Style = 'font-weight:bold;';
        $dataTable->addSummaryRow(new Row(array(
                        Row::COLUMNS => array(
                            'dateval'=>' ', 
                            'count'=>' ', 
                            'average' => '<div style="text-align:right;font-weight:bold;">'.Piwik::translate('OxidAnalysis_Sum').'</div>',  
                            'revsum' => $this->oaCurrFormat($sumRevenue, $this), 
                            'netmargin' => $this->oaCurrFormat($sumMargin, $this), 
                            'stornosum' => $txtStorno 
                            )
                    )));
        
        return $dataTable;
    }


    // Retrieve revenue values for graph
    public function getRevenueEvolution($date, $period)
    {

        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Piwik_Common::getRequestVar('idSite');
        $db = openDB($this);

        $date = Piwik_Common::getRequestVar('date');
        $period = Piwik_Common::getRequestVar('period');

        if ($period == 'range') {
            $dateStart = substr($date, 0, strpos($date, ',')); 
            $dateEnd = substr($date, strpos($date, ',')+1);  
        } else {
            $timePeriod = new Piwik_Period_Range($period, 'last30');
            $dateStart = $timePeriod->getDateStart()->toString('Y-m-d'); 
            $dateEnd = $timePeriod->getDateEnd()->toString('Y-m-d');
        }

        if ($this->DebugMode) logfile('debug', 'period='.$period);
        switch ($period) {

            case 'day':
            case 'range':
                // daily revenue
                $sql = 'SELECT DATE(o.oxorderdate) AS label, '
                        . 'SUM(o.oxtotalordersum) AS @SUMVAL@, '
                        . '@MARGIN@'
                     . 'FROM oxorder o '
                     . 'WHERE DATE(o.oxorderdate) >= \'' . $dateStart . '\' ' 
                        . 'AND DATE(o.oxorderdate) <= \'' . $dateEnd . '\' '
                        . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                        . 'AND o.oxstorno = @STORNOVAL@ '
                     . 'GROUP BY '  
                        . 'extract(year FROM o.oxorderdate), ' 
                        . 'extract(month FROM o.oxorderdate), '
                        . 'extract(day FROM o.oxorderdate) '; 

                $margin = '(SUM(o.oxtotalnetsum) - '
                            . '(SELECT SUM('
                                . 'IF(a1.oxparentid=\'\', '
                                    . 'a1.oxbprice, '
                                    . 'IF(a1.oxbprice=0.0, (SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), a1.oxbprice))'
                                . '*d1.oxamount) '
                                . 'FROM oxarticles a1, oxorderarticles d1 '
                                . 'WHERE d1.oxartid = a1.oxid '
                                    . 'AND o.oxid = d1.oxorderid '
                                    . 'AND d1.oxstorno = 0)) '
                            . 'AS netmargin ';
                break;

            case 'week':
                // weekly revenue
                $sql = 'SELECT '
                        . 'DATE_FORMAT(o.oxorderdate, \'%Y-%v\') AS label, '
                        . 'SUM(o.oxtotalordersum) AS @SUMVAL@, '
                        . '@MARGIN@'
                     . 'FROM oxorder o '
                     . 'WHERE DATE(o.oxorderdate) >= \'' . $dateStart . '\' ' 
                        . 'AND DATE(o.oxorderdate) <= \'' . $dateEnd . '\' '
                        . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                        . 'AND o.oxstorno = @STORNOVAL@ '
                     . 'GROUP BY '
                        . 'label ';

                //$margin = '\'\' AS netmargin ';
                $margin = '(SELECT '
                            . 'SUM(ROUND(d3.oxnetprice,2)) '
                         . 'FROM '
                            . 'oxorderarticles d3, oxorder o3 '
                         . 'WHERE '
                            . 'o3.oxid = d3.oxorderid '
                            . 'AND YEAR(o3.oxorderdate) = YEAR(o.oxorderdate) AND WEEK(o3.oxorderdate,3) = WEEK(o.oxorderdate,3) '
                            . 'AND d3.oxstorno = 0) '
                         . ' - '
                         . '(SELECT '
                            . 'SUM(ROUND(IF('
                                . 'a1.oxparentid=\'\', '
                                . 'a1.oxbprice, '
                                . 'IF('
                                    . 'a1.oxbprice=0.0, '
                                    . '(SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), '
                                    . 'a1.oxbprice)),2) '
                                . '*d1.oxamount) '
                            . 'FROM '
                                . 'oxarticles a1, oxorderarticles d1, oxorder o1 '
                            . 'WHERE '
                                . 'd1.oxartid = a1.oxid '
                                . 'AND d1.oxorderid = o1.oxid '
                                . 'AND YEAR(o1.oxorderdate) = YEAR(o.oxorderdate) AND WEEK(o1.oxorderdate,3) = WEEK(o.oxorderdate,3) '
                                . 'AND d1.oxstorno = 0) '
                         . 'AS netmargin ';
                break;

            case 'month':
                // monthy revenue
                $sql = 'SELECT '
                        . 'DATE_FORMAT(o.oxorderdate, \'%Y-%m\') as label, '
                        . 'SUM(o.oxtotalordersum) AS @SUMVAL@, '
                        . '@MARGIN@'
                     . 'FROM oxorder o '
                     . 'WHERE DATE(o.oxorderdate) >= \'' . $dateStart . '\' ' 
                        . 'AND DATE(o.oxorderdate) <= \'' . $dateEnd . '\' '
                        . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                        . 'AND o.oxstorno = @STORNOVAL@ '
                     . 'GROUP BY '
                        . 'label '; 

                //$margin = '\'\' AS netmargin ';
                $margin = '(SELECT '
                            . 'SUM(ROUND(d3.oxnetprice,2)) '
                         . 'FROM '
                            . 'oxorderarticles d3, oxorder o3 '
                         . 'WHERE '
                            . 'o3.oxid = d3.oxorderid '
                            . 'AND YEAR(o3.oxorderdate) = YEAR(o.oxorderdate) AND MONTH(o3.oxorderdate) = MONTH(o.oxorderdate) '
                            . 'AND d3.oxstorno = 0) '
                         . ' - '
                         . '(SELECT '
                            . 'SUM(ROUND(IF('
                                . 'a1.oxparentid=\'\', '
                                . 'a1.oxbprice, '
                                . 'IF('
                                    . 'a1.oxbprice=0.0, '
                                    . '(SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), '
                                    . 'a1.oxbprice)),2) '
                                . '*d1.oxamount) '
                            . 'FROM '
                                . 'oxarticles a1, oxorderarticles d1, oxorder o1 '
                            . 'WHERE '
                                . 'd1.oxartid = a1.oxid '
                                . 'AND d1.oxorderid = o1.oxid '
                                . 'AND YEAR(o1.oxorderdate) = YEAR(o.oxorderdate) AND MONTH(o1.oxorderdate) = MONTH(o.oxorderdate) '
                                . 'AND d1.oxstorno = 0) '
                         . 'AS netmargin ';
                break;

            case 'year':
                // yearly revenue
                $sql = 'SELECT extract(year FROM oxorderdate) AS label, '
                        . 'SUM(oxtotalordersum) AS @SUMVAL@, '
                        . '@MARGIN@'
                     . 'FROM oxorder '
                     . 'WHERE DATE(oxorderdate) >= \'' . $dateStart . '\' ' 
                        . 'AND DATE(oxorderdate) <= \'' . $dateEnd . '\' '
                        . 'AND oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                        . 'AND oxstorno = @STORNOVAL@ '
                     . 'GROUP BY '
                        . 'extract(year FROM oxorderdate) '; 

                //$margin = '\'\' AS netmargin ';
                $margin = '(SELECT '
                            . 'SUM(ROUND(d3.oxnetprice,2)) '
                         . 'FROM '
                            . 'oxorderarticles d3, oxorder o3 '
                         . 'WHERE '
                            . 'o3.oxid = d3.oxorderid '
                            . 'AND YEAR(o3.oxorderdate) = label '
                            . 'AND d3.oxstorno = 0) '
                         . ' - '
                         . '(SELECT '
                            . 'SUM(ROUND(IF('
                                . 'a1.oxparentid=\'\', '
                                . 'a1.oxbprice, '
                                . 'IF('
                                    . 'a1.oxbprice=0.0, '
                                    . '(SELECT b2.oxbprice FROM oxarticles b2 WHERE b2.oxid = a1.oxparentid), '
                                    . 'a1.oxbprice)),2) '
                                . '*d1.oxamount) '
                            . 'FROM '
                                . 'oxarticles a1, oxorderarticles d1, oxorder o1 '
                            . 'WHERE '
                                . 'd1.oxartid = a1.oxid '
                                . 'AND d1.oxorderid = o1.oxid '
                                . 'AND YEAR(o1.oxorderdate) = label '
                                . 'AND d1.oxstorno = 0) '
                         . 'AS netmargin ';
                break;


            case 'old-year':
                // yearly revenue
                $sql = 'SELECT extract(year FROM oxorderdate) AS label, '
                        . 'SUM(oxtotalordersum) AS @SUMVAL@, '
                        . '@MARGIN@'
                     . 'FROM oxorder '
                     . 'WHERE DATE(oxorderdate) >= \'' . $dateStart . '\' ' 
                        . 'AND DATE(oxorderdate) <= \'' . $dateEnd . '\' '
                        . 'AND oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                        . 'AND oxstorno = @STORNOVAL@ '
                     . 'GROUP BY '
                        . 'extract(year FROM oxorderdate) '; 

                $margin = '\'\' AS netmargin ';
                 break;

        }

//$margin = '';
        $sql1 = str_replace('@STORNOVAL@', '0', $sql);
        $sql1 = str_replace('@SUMVAL@', 'revenue', $sql1);
        $sql1 = str_replace('@MARGIN@', $margin, $sql1);

        if ($this->DebugMode) logfile('debug', 'vor ---sql1---');
        if ($this->DebugMode) logfile('debug', 'getRevenueEvolution: sql1 = '.$sql1);
        $stmt = $db->prepare($sql1);
        $stmt->execute();
        $dbRevData = $stmt->fetchAll(PDO::FETCH_NAMED);

        if ($this->DebugMode) logfile('debug', $dbRevData);
        if ($this->DebugMode) logfile('debug', 'vor addZeroValues(revenue)');
        $dbRevData = addZeroValues ($dateStart, $dateEnd, $period, 'revenue', $dbRevData);
        if ($this->DebugMode) logfile('debug', $dbRevData);
        if ($this->DebugMode) logfile('debug', 'vor addZeroValues(netmargin)');
        if ($this->DebugMode) logfile('debug', $dbRevData);
        $dbRevData = addZeroValues ($dateStart, $dateEnd, $period, 'netmargin', $dbRevData);
        if ($this->DebugMode) logfile('debug', count($dbRevData));
        if ($this->DebugMode) logfile('debug', '---- dbRevData ----');
        if ($this->DebugMode) logfile('debug', $dbRevData);
        for ($j=0; $j<count($dbRevData); $j++) {
            $graphData[$dbRevData[$j]['label']]['revenue'] = $dbRevData[$j]['revenue'];
            $graphData[$dbRevData[$j]['label']]['netmargin'] = $dbRevData[$j]['netmargin'];
            /* --- disabled until second y axis is possible --- 
            if ( $dbRevData[$j]['revenue'] != 0 )
                $graphData[$dbRevData[$j]['label']]['netmargin'] = $dbRevData[$j]['netmargin'] / $dbRevData[$j]['revenue'] * 100.0;
            else
                $graphData[$dbRevData[$j]['label']]['netmargin'] = 0.0;
            /**/
        }


        $sql2 = str_replace('@STORNOVAL@', '1', $sql);
        $sql2 = str_replace('@SUMVAL@', 'stornosum', $sql2);
        $sql2 = str_replace('@MARGIN@', "'' AS netmargin ", $sql2);

        if ($this->DebugMode) logfile('debug', 'vor ---sql2---');
        if ($this->DebugMode) logfile('debug', 'getRevenueEvolution: sql2 = '.$sql2);
        $stmt = $db->prepare($sql2);
        $stmt->execute();
        $dbCancData = $stmt->fetchAll(PDO::FETCH_NAMED);

        if ($this->DebugMode) logfile('debug', $dbCancData);
        if ($this->DebugMode) logfile('debug', 'vor addZeroValues(stornosum)');
        $dbCancData = addZeroValues ($dateStart, $dateEnd, $period, 'stornosum', $dbCancData);
        if ($this->DebugMode) logfile('debug', count($dbCancData));
        for ($j=0; $j<count($dbCancData); $j++) {
            $graphData[$dbCancData[$j]['label']]['canceled'] = $dbCancData[$j]['stornosum'];
            /* --- disabled until second y axis is possible --- 
            if ( $dbRevData[$j]['revenue'] != 0 )
                $graphData[$dbCancData[$j]['label']]['canceled'] = $dbCancData[$j]['stornosum'] / $dbRevData[$j]['revenue'] * 100.0;
            else
                $graphData[$dbCancData[$j]['label']]['canceled'] = 0.0;
            /**/
        }


        if ($this->DebugMode) logfile('debug', '-------graphData---------');
        if ($this->DebugMode) logfile('debug', $graphData);
        $dataTable = new Piwik_DataTable();
        
        // convert this array to a DataTable object
        $dataTable->addRowsFromArrayWithIndexLabel($graphData);
        //if ($this->DebugMode) logfile('debug', $dataTable);

        return $dataTable;
    }


    // Retrieving the yet not paid CIA 
    public function getReadyToSend($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        if ($this->DebugMode) logfile('debug', ('1-IdSite/Date/Period/Segment = '.$idSite.'/'.$date.'/'.$period.'/'.$segment) );
        
        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        if ($this->DebugMode) logfile('debug', ('Date/Period = '.$date.'/'.$period) );

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        if ($this->DebugMode) logfile('debug', ('Start/End Date = '.$dateStart.'/'.$dateEnd) );

        $sql = "SELECT o.oxordernr AS orderno, o.oxtotalordersum AS ordersum, o.oxbillsal AS salutation, "
                 . "CONCAT('<nobr>', o.oxbillcompany, '</nobr>') AS company, "
                 . "CONCAT('<a href=\"mailto:', o.oxbillemail, '\" style=\"text-decoration:underline;\"><nobr>', o.oxbillfname, '&nbsp;', o.oxbilllname, '</nobr></a>') AS name, "
                 . "IF (o.oxdelcity = '', "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                    . ") AS  custdeladdr, "
                 . "p.oxdesc AS paytype, "
                 . "GROUP_CONCAT(CONCAT('<nobr>', a.oxamount, ' x ', a.oxtitle, IF (a.oxselvariant != '', CONCAT(' &ndash; ', a.oxselvariant), ''), '</nobr>') SEPARATOR '<br>') AS orderlist, "
                 . "(TO_DAYS(NOW())-TO_DAYS(o.oxorderdate)) AS days, DATE(o.oxorderdate) AS orderdate , "
                 . "IF(o.oxremark!='', "
                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                        . "''"
                    . "), "
                    . "''"
                 . ") AS remark "
             . "FROM oxorder o, oxpayments p, oxorderarticles a "
             . "WHERE o.oxpaymenttype = p.oxid "
                 . "AND o.oxid = a.oxorderid  "
                 . "AND ((o.oxpaid != '0000-00-00 00:00:00') OR (o.oxpaymenttype IN ({$this->PaymentLater[$this->SiteID]}))) "
                 . "AND o.oxsenddate = '0000-00-00 00:00:00' "
                 . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                 . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                 . "AND o.oxstorno = 0 "
                 . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
             . "GROUP BY o.oxordernr "
             . "ORDER BY days ASC "; 

        if ($this->DebugMode) logfile('debug', 'getReadyToSend: '.$sql);
        
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getInvoiceNotPaid: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getReadyToSend: pdo->execute error = '.$e->getMessage() );
            die();
        }
        if ($this->DebugMode) logfile('debug', $dbData);


        $sumCIA = 0.0;
        $i = 0;
        foreach($dbData as $value) {
                $sumCIA += $value['ordersum'];
                $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
                $i++;
        }
        if ($this->DebugMode) logfile('debug', $dbData);

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        $this->Style = 'font-weight:bold;';
            
        $dataTable->addSummaryRow(new Row(array(
                        Row::COLUMNS => array(
                            'days' => ' ', 
                            'orderdate' => ' ', 
                            'orderno' => ' ', 
                            'company' => ' ', 
                            'name' => ' ', 
                            'email' => ' ', 
                            'city' => ' ',
                            'orderlist' => '<div style="text-align:right;font-weight:bold;">'.Piwik::translate('OxidAnalysis_Sum').'</div>', 
                            'ordersum' => $this->oaCurrFormat($sumCIA, $this), 
                            'remark' => ' ' 
                            )
                    )));
        
        return $dataTable;
    }



    //
    public function getTop5DelivererRevenueGraph()
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueGraph(): WELCOME');
        $this->SiteID = Piwik_Common::getRequestVar('idSite');
        $db = openDB($this);

        $date = Piwik_Common::getRequestVar('date');
        $period = Piwik_Common::getRequestVar('period');
        $this->Currency = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));
        if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueGraph(): date='.$date);
        if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueGraph(): period='.$period);

        if ($period == 'range') {
            $dateStart = substr($date, 0, strpos($date, ',')); 
            $dateEnd = substr($date, strpos($date, ',')+1);  
        } else {
            $timePeriod = new Piwik_Period_Range($period, 'last3');
            $dateStart = $timePeriod->getDateStart()->toString('Y-m-d'); 
            $dateEnd = $timePeriod->getDateEnd()->toString('Y-m-d');
        }
        if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueGraph: AFTER PERIOD  ');

        // retrieve the top5 deliverer
        $sql = "SELECT m.oxid, m.oxtitle, SUM(d.oxamount) AS artcount, SUM(d.oxbrutprice) AS sumprice "
             . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
             . "WHERE d.oxartid = a.oxid "
                    . "AND IF(a.oxmanufacturerid!='', "
                        . "a.oxmanufacturerid, "
                        . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                        . "= m.oxid "
                    . "AND o.oxstorno = 0 "
                    . "AND d.oxstorno = 0 "
                    . "AND d.oxorderid = o.oxid "
                    . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                    . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
             . "GROUP BY m.oxtitle "
             . "ORDER BY SUM(d.oxbrutprice) DESC "
             . "LIMIT 0, 5 ";

        if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueGraph: '.$sql);
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $dbData = $stmt->fetchAll();
        if ($this->DebugMode) logfile('debug', $dbData);

        if ($period == 'range') {
            $dateStart = substr($date, 0, strpos($date, ',')); 
            $dateEnd = substr($date, strpos($date, ',')+1);  
        } else {
            $timePeriod = new Piwik_Period_Range($period, 'last30');
            $dateStart = $timePeriod->getDateStart()->toString('Y-m-d'); 
            $dateEnd = $timePeriod->getDateEnd()->toString('Y-m-d');
        }

        $i = 0;
        $this->ColumnLabels = '';
        $columnLabel = '';
        foreach($dbData as $value) {
            $columnLabel .= $dbData[$i]['oxtitle'] . '|';
            switch ($period) {
                case 'day':
                case 'range':
                    $sql = "SELECT DATE(o.oxorderdate) AS label, SUM(d.oxbrutprice) AS revenue "
                        . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
                        . "WHERE d.oxartid = a.oxid "
                           . "AND IF(a.oxmanufacturerid!='', "
                               . "a.oxmanufacturerid, "
                               . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                               . "= m.oxid "
                           . "AND o.oxstorno = 0 "
                           . "AND d.oxstorno = 0 "
                           . "AND d.oxorderid = o.oxid "
                           . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                           . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                           . "AND m.oxid = '{$dbData[$i]['oxid']}' "
                        . "GROUP BY DATE(o.oxorderdate) "
                        . "ORDER BY DATE(o.oxorderdate) ASC";
                    break;

                case 'week':
                    $sql = "SELECT DATE_FORMAT(oxorderdate, '%Y-%v') AS label, SUM(d.oxbrutprice) AS revenue "
                        . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
                        . "WHERE d.oxartid = a.oxid "
                           . "AND IF(a.oxmanufacturerid!='', "
                               . "a.oxmanufacturerid, "
                               . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                               . "= m.oxid "
                           . "AND o.oxstorno = 0 "
                           . "AND d.oxstorno = 0 "
                           . "AND d.oxorderid = o.oxid "
                           . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                           . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                           . "AND m.oxid = '{$dbData[$i]['oxid']}' "
                        . "GROUP BY DATE_FORMAT(oxorderdate, '%Y-%v') "
                        . "ORDER BY DATE_FORMAT(oxorderdate, '%Y-%v') ASC";
                    break;

                case 'month':
                    $sql = "SELECT DATE_FORMAT(oxorderdate, '%Y-%m') AS label, SUM(d.oxbrutprice) AS revenue "
                        . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
                        . "WHERE d.oxartid = a.oxid "
                           . "AND IF(a.oxmanufacturerid!='', "
                               . "a.oxmanufacturerid, "
                               . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                               . "= m.oxid "
                           . "AND o.oxstorno = 0 "
                           . "AND d.oxstorno = 0 "
                           . "AND d.oxorderid = o.oxid "
                           . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                           . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                           . "AND m.oxid = '{$dbData[$i]['oxid']}' "
                        . "GROUP BY YEAR(o.oxorderdate), MONTH(o.oxorderdate) "
                        . "ORDER BY YEAR(o.oxorderdate), MONTH(o.oxorderdate) ASC";
                    break;

                case 'year':
                    $sql = "SELECT YEAR(o.oxorderdate) AS label, SUM(d.oxbrutprice) AS revenue "
                        . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
                        . "WHERE d.oxartid = a.oxid "
                           . "AND IF(a.oxmanufacturerid!='', "
                               . "a.oxmanufacturerid, "
                               . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                               . "= m.oxid "
                           . "AND o.oxstorno = 0 "
                           . "AND d.oxstorno = 0 "
                           . "AND d.oxorderid = o.oxid "
                           . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                           . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
                           . "AND m.oxid = '{$dbData[$i]['oxid']}' "
                        . "GROUP BY YEAR(o.oxorderdate) "
                        . "ORDER BY YEAR(o.oxorderdate) ASC";
                    break;
            }

            if ($this->DebugMode) logfile('debug', 'getTop5DelivererRevenueGraph: '.$sql);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $dbRevData = $stmt->fetchAll(PDO::FETCH_NAMED);
            if ($this->DebugMode) logfile('debug', $dbRevData);

            $dbRevData = addZeroValues ($dateStart, $dateEnd, $period, 'revenue', $dbRevData);
            if ($this->DebugMode) logfile('debug', count($dbRevData));
            for ($j=0; $j<count($dbRevData); $j++) {
                $graphData[$dbRevData[$j]['label']]["revenue{$i}"] = $dbRevData[$j]['revenue'];
            }
            if ($this->DebugMode) logfile('debug', '$graphData');
            if ($this->DebugMode) logfile('debug', $graphData);

            $i++;
        }

        $db = null;
        if ($this->DebugMode) logfile('debug', $columnLabel);

        $dataTable = new Piwik_DataTable();
        // convert this array to a DataTable object
        $dataTable->addRowsFromArrayWithIndexLabel($graphData);
        if ($this->DebugMode) logfile('debug', $dataTable);

        return $dataTable;

    }


    //
    public function getRevenueSummary($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        if ($this->DebugMode) logfile('debug', 'getRevenueSummary(): WELCOME');
        
        $this->SiteID = $idSite; //-//Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $sRevenueSelect = "SELECT COUNT(*) AS totalnum, SUM(oxtotalordersum) AS totalsum "
                 . "FROM oxorder "
                 . "WHERE oxstorno = 0 "
                 . "AND oxshopid = {$this->ShopID[$this->SiteID]} "
                 . "AND ";
                 
        $sMarginSelect = "SELECT  "
                . "SUM("
                    . "ROUND(d.oxnetprice,2) - "
                    . "ROUND("
                        . "IF(a.oxparentid='', a.oxbprice, IF(a.oxbprice=0.0, (SELECT b.oxbprice FROM oxarticles b where b.oxid = a.oxparentid), a.oxbprice) )*d.oxamount"
                        . ", 2"
                        . ") "
                    . ") "
                . "AS netmargin "
                . "FROM oxorderarticles d, oxorder o, oxarticles a "
                . "WHERE d.oxorderid = o.oxid AND d.oxartid = a.oxid "
                    . "AND o.oxstorno = 0 AND d.oxstorno = 0 "
                    . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                    . "AND  ";

        $db = openDB($this);
        
        
        // ------ Select Revenue and Margin of previous Year -------//
        $sTimeCond = 'YEAR(oxorderdate) = YEAR(CURDATE())-1 ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenuePrevYear = $data[0]['totalsum'];
        $countPrevYear = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginPrevYear = $data[0]['netmargin'];

        
        // ------ Select Revenue and Margin of this Year -------//
        $sTimeCond = 'YEAR(oxorderdate) = YEAR(CURDATE()) ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenueThisYear = $data[0]['totalsum'];
        $countThisYear = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginThisYear = $data[0]['netmargin'];
        
        
        // ------ Select Revenue and Margin of previous Month -------//
        if (date("n") == 1) {
            $nPrevMonth = 12;
            $nYear = date("Y") - 1;
        }
        else {
            $nPrevMonth = date("n") - 1;
            $nYear = date("Y");
        }
        $sTimeCond = 'YEAR(oxorderdate) = ' . $nYear . ' '
                 . 'AND MONTH(oxorderdate) = ' . $nPrevMonth . ' ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenuePrevMonth = $data[0]['totalsum'];
        $countPrevMonth = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginPrevMonth = $data[0]['netmargin'];
        
        
        // ------ Select Revenue and Margin of this Month -------//
        $sTimeCond = 'YEAR(oxorderdate) = YEAR(CURDATE()) '
                 . 'AND MONTH(oxorderdate) = MONTH(CURDATE()) ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenueThisMonth = $data[0]['totalsum'];
        $countThisMonth = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginThisMonth = $data[0]['netmargin'];
        
        
        // ------ Select Revenue and Margin of previous Week -------//
        $startDays = '-'. (date("N")+6) . ' days';
        $dWeekStart = date("Y-m-d", strtotime($startDays));
        $endDays = '-'. date("N") . ' days';
        $dWeekEnd = date("Y-m-d", strtotime($endDays));
        $sTimeCond = 'DATE(oxorderdate) >= \'' . $dWeekStart . '\' '
                 . 'AND DATE(oxorderdate) <= \'' . $dWeekEnd . '\' ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenuePrevWeek = $data[0]['totalsum'];
        $countPrevWeek = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginPrevWeek = $data[0]['netmargin'];
        
        
        // ------ Select Revenue and Margin of this Week -------//
        $startDays = '-'. (date("N")-1) . ' days';
        $dWeekStart = date("Y-m-d", strtotime($startDays));
        $sTimeCond = 'DATE(oxorderdate) >= \'' . $dWeekStart . '\' '
                 . 'AND DATE(oxorderdate) <= DATE(CURDATE()) ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenueThisWeek = $data[0]['totalsum'];
        $countThisWeek = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginThisWeek = $data[0]['netmargin'];
        
        
        // ------ Select Revenue and Margin of Yesterday -------//
        $sTimeCond = 'DATE(oxorderdate) = DATE_ADD(CURDATE(), INTERVAL -1 DAY) ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenueYesterday = $data[0]['totalsum'];
        $countYesterday = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginYesterday = $data[0]['netmargin'];

        
        // ------ Select Revenue and Margin of Today -------//
        $sTimeCond = 'DATE(oxorderdate) = CURDATE() ';
        $sql = $sRevenueSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $revenueToday = $data[0]['totalsum'];
        $countToday = $data[0]['totalnum'];

        $sql = $sMarginSelect . $sTimeCond;
        $data = $this->oaQuery($db, $sql, 'getRevenueSummary');
        $marginToday = $data[0]['netmargin'];
        
        $db = NULL;
        

        $daysPerYear = 337 + date("t", strtotime(date("Y")."-02-01"));

        /* calcuate forecast revenue */
        $revenueForecastYear = $revenueThisYear / (date("z")/$daysPerYear);
        $revenueForecastMonth = $revenueThisMonth / (date("j")/date("t"));
        $revenueForecastWeek = $revenueThisWeek / (dayofWeek($this)/7.0);
        $revenueForecastDay = $revenueToday / ((date("G")+date("i")/60.0)/24.0);

        /* calcuate forecast margin */
        $marginForecastYear = $marginThisYear / (date("z")/$daysPerYear);
        $marginForecastMonth = $marginThisMonth / (date("j")/date("t"));
        $marginForecastWeek = $marginThisWeek / (dayofWeek($this)/7.0);
        $marginForecastDay = $marginToday / ((date("G")+date("i")/60.0)/24.0);

        /* calculate revenue trends */
        if ($revenuePrevYear != 0.0)
            $revenueTrendYear = $revenueForecastYear/$revenuePrevYear*100.0 - 100.0;
        else
            $revenueTrendYear = 100.0;
        if ($revenuePrevMonth != 0.0)
            $revenueTrendMonth = $revenueForecastMonth/$revenuePrevMonth*100.0 - 100.0;
        else
            $revenueTrendMonth = 100.0;
        if ($revenuePrevWeek != 0.0)
            $revenueTrendWeek = $revenueForecastWeek/$revenuePrevWeek*100.0 - 100.0;
        else
            $revenueTrendWeek = 100.0;
        if ($revenueYesterday != 0.0)
            $revenueTrendDay = $revenueForecastDay/$revenueYesterday*100.0 - 100.0;
        else
            $revenueTrendDay = 100.0;

        /* calculate margin trends */
        if ($marginPrevYear != 0.0)
            $marginTrendYear = $marginForecastYear/$marginPrevYear*100.0 - 100.0;
        else
            $marginTrendYear = 100.0;
        if ($marginPrevMonth != 0.0)
            $marginTrendMonth = $marginForecastMonth/$marginPrevMonth*100.0 - 100.0;
        else
            $marginTrendMonth = 100.0;
        if ($marginPrevWeek != 0.0)
            $marginTrendWeek = $marginForecastWeek/$marginPrevWeek*100.0 - 100.0;
        else
            $marginTrendWeek = 100.0;
        if ($marginYesterday != 0.0)
            $marginTrendDay = $marginForecastDay/$marginYesterday*100.0 - 100.0;
        else
            $marginTrendDay = 100.0;

        $dbData = array();
        $icoPath = "plugins/OxidAnalysis/images";
        $icoBlank = '&nbsp;<img src="'.$icoPath.'/blank.png" />';
        
        // -------- YEAR ----------
        array_push($dbData, array(
                'num'     => 1,
                'period'  => Piwik::translate('OxidAnalysis_PrevYear'),
                'count'   => $countPrevYear,
                'margin'  => alignRight( $this->oaCurrFormat($marginPrevYear, $this, false) . $icoBlank),
                'revenue23' => alignRight( $this->oaCurrFormat($revenuePrevYear, $this, false) . $icoBlank)
            ));
        
        $tipMargin  = $this->oaTrendTip( $this->oaCurrFormat($marginTrendYear, $this), $marginForecastYear, $this );
        $tipRevenue = $this->oaTrendTip( $this->oaCurrFormat($revenueTrendYear, $this), $revenueForecastYear, $this );
        $icoMarginTrend  = $this->oaTrendIcon( $marginForecastYear, $marginPrevYear );
        $icoRevenueTrend = $this->oaTrendIcon( $revenueForecastYear, $revenuePrevYear );
        array_push($dbData, array(
                'num'     => 2,
                'period'  => Piwik::translate('OxidAnalysis_ThisYear'),
                'count'   => $countThisYear,
                'margin'  => alignRight(addTitle( $this->oaCurrFormat($marginThisYear, $this, false), $tipMargin ).$icoMarginTrend),
                'revenue23' => alignRight(addTitle( $this->oaCurrFormat($revenueThisYear, $this, false), $tipRevenue ).$icoRevenueTrend)
            ));
        
        // -------- MONTH ----------
        array_push($dbData, array(
                'num' => 3,
                'period' => Piwik::translate('OxidAnalysis_PrevMonth'),
                'count' => $countPrevMonth,
                'margin' => alignRight( $this->oaCurrFormat($marginPrevMonth, $this, false) . $icoBlank),
                'revenue23' => alignRight( $this->oaCurrFormat($revenuePrevMonth, $this, false) . $icoBlank)
            ));
        
        $tipMargin  = $this->oaTrendTip( $marginTrendMonth, $this->oaCurrFormat($marginForecastMonth, $this) , $this );
        $tipRevenue = $this->oaTrendTip( $revenueTrendMonth, $this->oaCurrFormat($revenueForecastMonth, $this) , $this );
        $icoMarginTrend  = $this->oaTrendIcon( $marginForecastMonth, $marginPrevMonth );
        $icoRevenueTrend = $this->oaTrendIcon( $revenueForecastMonth, $revenuePrevMonth );
        array_push($dbData, array(
                'num' => 4,
                'period' => Piwik::translate('OxidAnalysis_ThisMonth'),
                'count' => $countThisMonth,
                'margin' => alignRight(addTitle( $this->oaCurrFormat($marginThisMonth, $this, false), $tipMargin ).$icoMarginTrend),
                'revenue23' => alignRight(addTitle( $this->oaCurrFormat($revenueThisMonth, $this, false), $tipMargin ).$icoRevenueTrend)
            ));

        // -------- WEEK ----------
        array_push($dbData, array(
                'num' => 5,
                'period' => Piwik::translate('OxidAnalysis_PrevWeek'),
                'count' => $countPrevWeek,
                'margin' => alignRight( $this->oaCurrFormat($marginPrevWeek, $this, false) . $icoBlank),
                'revenue23' => alignRight( $this->oaCurrFormat($revenuePrevWeek, $this, false) . $icoBlank)
            ));
        
        $tipMargin  = $this->oaTrendTip( $marginTrendWeek, $this->oaCurrFormat($marginForecastWeek, $this), $this );
        $tipRevenue = $this->oaTrendTip( $revenueTrendWeek, $this->oaCurrFormat($revenueForecastWeek, $this), $this );
        $icoMarginTrend  = $this->oaTrendIcon( $marginForecastWeek, $marginPrevWeek );
        $icoRevenueTrend = $this->oaTrendIcon( $revenueForecastWeek, $revenuePrevWeek );
        array_push($dbData, array(
                'num' => 6,
                'period' => Piwik::translate('OxidAnalysis_ThisWeek'),
                'count' => $countThisWeek,
                'margin' => alignRight(addTitle( $this->oaCurrFormat($marginThisWeek, $this, false), $tipMargin ) . $icoMarginTrend),
                'revenue23' => alignRight(addTitle( $this->oaCurrFormat($revenueThisWeek, $this, false), $tipMargin ) . $icoRevenueTrend)
            ));
        
        // -------- DAY ----------
        array_push($dbData, array(
                'num' => 7,
                'period' => Piwik::translate('OxidAnalysis_Yesterday'),
                'count' => $countYesterday,
                'margin' => alignRight( $this->oaCurrFormat($marginYesterday, $this, false) . $icoBlank ),
                'revenue23' => alignRight( $this->oaCurrFormat($revenueYesterday, $this, false) . $icoBlank )
            ));
        
        $tipMargin  = $this->oaTrendTip( $marginTrendDay, $this->oaCurrFormat($marginForecastDay, $this), $this );
        $tipRevenue = $this->oaTrendTip( $revenueTrendDay, $this->oaCurrFormat($revenueForecastDay, $this), $this );
        $icoMarginTrend  = $this->oaTrendIcon( $marginForecastDay, $marginYesterday );
        $icoRevenueTrend = $this->oaTrendIcon( $revenueForecastDay, $revenueYesterday );
        array_push($dbData, array(
                'num' => 8,
                'period' => Piwik::translate('OxidAnalysis_Today'),
                'count' => $countToday,
                'margin' => alignRight(addTitle( $this->oaCurrFormat($marginToday, $this, false) . $icoMarginTrend, $tipMargin )),
                'revenue23' => alignRight(addTitle( $this->oaCurrFormat($revenueToday, $this, false) . $icoRevenueTrend, $tipMargin ))
            ));
        
        // -------- Prepare Data ----------
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    //
    public function getRevenueAlerts($idSite, $period, $date)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();
        
        $period = 'day';
        $oPeriod = new Range($period, 'last180');
        $dateStart = $oPeriod->getDateStart()->toString('Y-m-d');
        $dateEnd = $oPeriod->getDateEnd()->toString('Y-m-d');
        
        $sql = "SELECT m.oxid, m.oxtitle, SUM(d.oxamount) AS artcount, SUM(d.oxbrutprice) AS sumprice "
             . "FROM oxorderarticles d, oxarticles a, oxmanufacturers m, oxorder o "
             . "WHERE d.oxartid = a.oxid "
                    . "AND IF(a.oxmanufacturerid!='', "
                        . "a.oxmanufacturerid, "
                        . "(SELECT a2.oxmanufacturerid FROM oxarticles a2 WHERE a.oxparentid=a2.oxid)) "
                        . "= m.oxid "
                    . "AND d.oxstorno = 0 "
                    . "AND d.oxorderid = o.oxid "
                    . "AND o.oxshopid = {$this->ShopID[$idSite]} "
                    . "AND DATE(o.oxorderdate) >= '{$dateStart}' " 
                    . "AND DATE(o.oxorderdate) <= '{$dateEnd}' "
             . "GROUP BY m.oxtitle "
             . "ORDER BY m.oxtitle DESC ";

        $db = openDB($this);
        $dbDataLongterm = $this->oaQuery($db, $sql, 'getRevenueAlert');
        $period = 'day';
        $oPeriod = new Range($period, 'last30');
        $dateStart = $oPeriod->getDateStart()->toString('Y-m-d');
        $dateEnd = $oPeriod->getDateEnd()->toString('Y-m-d');

        $sql = "SELECT m.oxid, m.oxtitle, SUM(d.oxamount) AS artcount, SUM(d.oxbrutprice) AS sumprice "
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
                    . "AND o.oxshopid = {$this->ShopID[$idSite]} "
             . "GROUP BY m.oxtitle "
             . "ORDER BY m.oxtitle DESC ";

        $dbDataShortterm = $this->oaQuery($db, $sql, 'getRevenueAlert');

        $i = 0;
        $dbData = array();
        foreach ($dbDataLongterm as $longvalue) {
            $dbData[$i]['title'] = $dbDataLongterm[$i]['oxtitle'];
            $dbData[$i]['longrev'] = $dbDataLongterm[$i]['sumprice'];
            $dbData[$i]['shortrev'] = 0.0;
            $dbData[$i]['missed'] = -($dbData[$i]['longrev'] / 180.0*30.0);
            //$dbData[$i]['missedtext'] = '-';
            $dbData[$i]['evolution'] = -100.0;
            //$dbData[$i]['evolutiontext'] = '-100.0 %';
            foreach ($dbDataShortterm as $shortvalue) {
                if ($shortvalue['oxid'] == $longvalue['oxid']) {
                    $dbData[$i]['shortrev'] = $shortvalue['sumprice'];
                    $dbData[$i]['missed'] = $dbData[$i]['shortrev'] -($dbData[$i]['longrev'] / 180.0*30.0);
                    $dbData[$i]['evolution'] = ($dbData[$i]['shortrev'] / 30.0) / ($dbData[$i]['longrev'] / 180.0) * 100.0 - 100.0;
                }
            }
            $dbData[$i]['longrev'] = $this->oaCurrFormat($dbData[$i]['longrev'], $this);
            $dbData[$i]['shortrev'] = $this->oaCurrFormat($dbData[$i]['shortrev'], $this);
            $dbData[$i]['missedtext'] = $this->oaCurrFormat($dbData[$i]['missed'], $this);
            $dbData[$i]['evolutiontext'] = percFormat($dbData[$i]['evolution'], $this);
            $i++;
        }
        if ($this->DebugMode) logfile('debug', $dbData);

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    // Retrieving the delivered orders without feedback reminder
    public function getFeedbackList($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT oxid, oxordernr AS orderno, oxbillsal AS salutation, oxbillcompany AS company, "
                 . "CONCAT(oxbillfname, '&nbsp;', oxbilllname) AS name, "
                 . "CONCAT('<a href=\"mailto:', oxbillemail, '\" style=\"text-decoration:underline;\">', oxbillemail, '</a>') AS email, "
                 . "CONCAT(oxbillzip, '&nbsp;', oxbillcity) AS city, "
                 . "CONCAT('<a href=\"',"
                        . "REPLACE("
                                . "REPLACE("
                                        . "REPLACE("
                                                . "REPLACE("
                                                        . "REPLACE("
                                                                . "REPLACE(oxtrackcode,"
                                                                        . "'DHL',CONCAT('http://nolp.dhl.de/nextt-online-public/set_identcodes.do?extendedSearch=false&rfn=&searchQuick=Suchen&idc=')),"
                                                                . "'ILX','https://www.iloxx.de/net/popup/trackpop.aspx?id='),"
                                                        . "'UPS','http://wwwapps.ups.com/WebTracking/processRequest?HTMLVersion=5.0&Requester=NES&AgreeToTermsAndConditions=yes&loc=de_DE&tracknum='),"
                                                . "'DPD','http://extranet.dpd.de/cgi-bin/delistrack?typ=1&lang=de&pknr='),"
                                        . "'GLS','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/DE03/DE/5004.htm?txtAction=71000&txtRefNo='),"
                                . "'HMS','http://tracking.hlg.de/Tracking.jsp?TrackID='),"
                        . "'\" style=\"text-decoration:underline;\" target=\"_blank\">', oxtrackcode, '</a>') "
                 . "AS trackcode , "
                 . "DATE(oxorderdate) AS orderdate, (TO_DAYS(oxsenddate)-TO_DAYS(oxorderdate)) AS days, DATE(oxsenddate) AS senddate, oxtotalordersum AS ordersum "
                 . "FROM oxorder "
                 . "WHERE oxordersurvey = '0000-00-00 00:00:00' "
                    . "AND DATE(oxsenddate) >= '{$dateStart}' "
                    . "AND DATE(oxsenddate)  <= '{$dateEnd}' "
                    . "AND oxstorno = 0 "
                    . "AND oxshopid = {$this->ShopID[$this->SiteID]} "
                 . "ORDER BY days ASC "; 

        if ($this->DebugMode) logfile('debug', 'getFeedbacklist: '.$sql);
        $db = openDB($this);
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', $sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
        }
        catch (PDOException $e) {
            logfile( 'error', 'getFeedbacklist: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $i = 0;
        foreach($dbData as $value) {
            if (($period == 'day') || ($period == 'range')) {
                if (strpos($dbData[$i]['trackcode'], 'http') === FALSE) {
                    $newURL = 'href="' . $this->CarrierTrackingUrl[$this->SiteID] ;
                    $dbData[$i]['trackcode'] = str_replace('href="', $newURL, $dbData[$i]['trackcode']);
                }
            }
            $sql = "SELECT oxamount, oxtitle "
                    . "FROM oxorderarticles "
                    . "WHERE oxorderid = '{$dbData[$i]['oxid']}' "
                        . "AND oxstorno = 0 ";
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute();
                if ($stmt->errorCode() != '00000') {
                    logfile('error', $sql );
                    logfile('error', $stmt->errorInfo() );
                }
                $dbData = $stmt->fetchAll();
            }
            catch (PDOException $e) {
                logfile( 'error', 'getFeedbacklist: pdo->execute error = '.$e->getMessage() );
                die();
            }
                    
            $dbData[$i]['oxdetails'] = Piwik_Translate('OxidPlugin_Order') . ':';
            foreach ($details as $detail) {
                $dbData[$i]['oxdetails'] = $dbData[$i]['oxdetails'] . chr(13) . $detail['oxamount'] . ' x ' . $detail['oxtitle'];
            }
            $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
            $dbData[$i]['ordersum'] = addTitle($dbData[$i]['ordersum'], $dbData[$i]['oxdetails']);
            $i++;
        }

        $db = null;

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        
        return $dataTable;
    }


    // Retrieving the yet not paid CIA 
    public function getCIAnotPaid($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT o.oxordernr AS orderno, o.oxtotalordersum AS ordersum, o.oxbillsal AS salutation, "
                 . "o.oxbillcompany AS company, "
                 . "CONCAT('<a href=\"mailto:', o.oxbillemail, '\" style=\"text-decoration:underline;\">', o.oxbillfname, '&nbsp;', o.oxbilllname, '</a>') AS name, "
                 . "IF (o.oxdelcity = '', "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                    . ") AS  custdeladdr, "
                 . "(TO_DAYS(NOW())-TO_DAYS(o.oxorderdate)) AS days, DATE(o.oxorderdate) AS orderdate, "
                 . "GROUP_CONCAT(CONCAT('<nobr>', a.oxamount, ' x ', a.oxtitle, IF (a.oxselvariant != '', CONCAT(' &ndash; ', a.oxselvariant), ''), '</nobr>') SEPARATOR '<br>') AS orderlist, "
                 . "IF(o.oxremark!='', "
                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                        . "''"
                    . "), "
                    . "''"
                 . ") AS remark "
                 . "FROM oxorder o, oxorderarticles a "
                 . "WHERE "
                    . "o.oxpaymenttype = {$this->PaymentCIA[$this->SiteID]} "
                    . "AND o.oxpaid = '0000-00-00 00:00:00' "
                    . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                    . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                    . "AND o.oxstorno = 0 "
                    . "AND o.oxid = a.oxorderid  "
                    . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                 . "GROUP BY o.oxordernr "
                 . "ORDER BY days ASC "; 

        if ($this->DebugMode) logfile('debug', 'getCIAnotPaid: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', $sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getCIAnotPaid: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $sumCIA = 0.0;
        $i = 0;
        foreach($dbData as $value) {
                $sumCIA += $value['ordersum'];
                $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
                $i++;
        }

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        $this->Style = 'font-weight:bold;';
        $dataTable->addSummaryRow(new Row(array(
                        Row::COLUMNS => array(
                            'days'=>' ', 
                            'orderdate'=>' ', 
                            'orderno'=>' ', 
                            'company'=>' ', 
                            'name'=>' ', 
                            'email'=>' ', 
                            'orderlist' => '<div style="text-align:right;font-weight:bold;">'.Piwik::translate('OxidAnalysis_Sum').'</div>',
                            'ordersum' => $this->oaCurrFormat($sumCIA, $this) 
                            )
                    )));
        
        return $dataTable;
    }


    // Retrieving the yet not received the COD 
    public function getCODnotReceived($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT o.oxordernr AS orderno, o.oxtotalordersum AS ordersum, o.oxbillsal AS salutation, "
                 . "CONCAT('<a href=\"mailto:', o.oxbillemail, '\" style=\"text-decoration:underline;\"><nobr>', o.oxbillfname, '&nbsp;', o.oxbilllname, '</nobr></a>') AS name, "
                 . "IF (o.oxdelcity = '', "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillstreet, '&nbsp;', o.oxbillstreetnr, ', ', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelstreet, '&nbsp;', o.oxdelstreetnr, ', ', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                    . ") AS  custdeladdr, "
                 . "CONCAT('<a href=\"',"
                        . "REPLACE("
                                . "REPLACE("
                                        . "REPLACE("
                                                . "REPLACE("
                                                        . "REPLACE("
                                                                . "REPLACE(o.oxtrackcode,"
                                                                        . "'DHL',CONCAT('http://nolp.dhl.de/nextt-online-public/set_identcodes.do?extendedSearch=false&rfn=&searchQuick=Suchen&idc=')),"
                                                                . "'ILX','https://www.iloxx.de/net/popup/trackpop.aspx?id='),"
                                                        . "'UPS','http://wwwapps.ups.com/WebTracking/processRequest?HTMLVersion=5.0&Requester=NES&AgreeToTermsAndConditions=yes&loc=de_DE&tracknum='),"
                                                . "'DPD','http://extranet.dpd.de/cgi-bin/delistrack?typ=1&lang=de&pknr='),"
                                        . "'GLS','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/DE03/DE/5004.htm?txtAction=71000&txtRefNo='),"
                                . "'HMS','http://tracking.hlg.de/Tracking.jsp?TrackID='),"
                        . "'\" style=\"text-decoration:underline;\" target=\"_blank\">', oxtrackcode, '</a>') "
                 . "AS trackcode , "
                 . "(TO_DAYS(NOW())-TO_DAYS(o.oxsenddate)) AS days, DATE(o.oxsenddate) AS senddate, "
                 . "GROUP_CONCAT(CONCAT('<nobr>', a.oxamount, ' x ', a.oxtitle, IF (a.oxselvariant != '', CONCAT(' &ndash; ', a.oxselvariant), ''), '</nobr>') SEPARATOR '<br>') AS orderlist, "
                 . "IF(o.oxremark!='', "
                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                        . "''"
                    . "), "
                    . "''"
                 . ") AS remark "
                 . "FROM oxorder o, oxorderarticles a "
                 . "WHERE "
                    . "o.oxpaymenttype = {$this->PaymentCOD[$this->SiteID]} "
                    . "AND o.oxsenddate != '0000-00-00 00:00:00' "
                    . "AND o.oxpaid = '0000-00-00 00:00:00' "
                    . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                    . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                    . "AND o.oxstorno = 0 "
                    . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                    . "AND o.oxid = a.oxorderid  "
                 . "GROUP BY o.oxordernr "
                 . "ORDER BY days ASC "; 

        if ($this->DebugMode) logfile('debug', 'getCODnotReceived: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getCODnotReceived: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getCODnotReceived: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $sumCOD = 0.0;
        $i = 0;
        foreach($dbData as $value) {
            $sumCOD += $value['ordersum'];
            $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
            if (($period == 'day') || ($period == 'range')) {
                if (strpos($dbData[$i]['trackcode'], 'http') === FALSE) {
                    $newURL = 'href="' . $this->CarrierTrackingUrl[$this->SiteID] ;
                    $dbData[$i]['trackcode'] = str_replace('href="', $newURL, $dbData[$i]['trackcode']);
                }
            }
            $i++;
        }

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        $this->Style = 'font-weight:bold;';
        $dataTable->addSummaryRow(new Row(array(
                        Row::COLUMNS => array(
                            'days'=>' ', 
                            'senddate'=>' ', 
                            'orderno'=>' ', 
                            'trackcode'=>' ', 
                            'company'=>' ', 
                            'name'=>' ', 
                            'email'=>' ', 
                            'orderlist' => '<div style="text-align:right;font-weight:bold;">'.Piwik::translate('OxidAnalysis_Sum').'</div>',
                            'ordersum' => $this->oaCurrFormat($sumCOD, $this) 
                            )
                    )));
        
            return $dataTable;
    }


    // Retrieving the yet not paid invoices  
    public function getInvoiceNotPaid($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT o.oxordernr AS orderno, o.oxtotalordersum AS ordersum, o.oxbillsal AS salutation, "
                 . "CONCAT('<nobr>', o.oxbillcompany, '</nobr>') AS company, CONCAT(o.oxbillfname, '&nbsp;', o.oxbilllname) AS name, "
                 . "CONCAT('<a href=\"mailto:', o.oxbillemail, '\" style=\"text-decoration:underline;\"><nobr>', o.oxbillfname, '&nbsp;', o.oxbilllname, '</nobr></a>') AS name, "
                 . "IF (o.oxdelcity = '', "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillstreet, '&nbsp;', o.oxbillstreetnr, ', ', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelstreet, '&nbsp;', o.oxdelstreetnr, ', ', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                    . ") AS  custdeladdr, "
                 . "CONCAT('<a href=\"',"
                        . "REPLACE("
                                . "REPLACE("
                                        . "REPLACE("
                                                . "REPLACE("
                                                        . "REPLACE("
                                                                . "REPLACE(o.oxtrackcode,"
                                                                        . "'DHL',CONCAT('http://nolp.dhl.de/nextt-online-public/set_identcodes.do?extendedSearch=false&rfn=&searchQuick=Suchen&idc=')),"
                                                                . "'ILX','https://www.iloxx.de/net/popup/trackpop.aspx?id='),"
                                                        . "'UPS','http://wwwapps.ups.com/WebTracking/processRequest?HTMLVersion=5.0&Requester=NES&AgreeToTermsAndConditions=yes&loc=de_DE&tracknum='),"
                                                . "'DPD','http://extranet.dpd.de/cgi-bin/delistrack?typ=1&lang=de&pknr='),"
                                        . "'GLS','http://www.gls-group.eu/276-I-PORTAL-WEB/content/GLS/DE03/DE/5004.htm?txtAction=71000&txtRefNo='),"
                                . "'HMS','http://tracking.hlg.de/Tracking.jsp?TrackID='),"
                        . "'\" style=\"text-decoration:underline;\" target=\"_blank\">', oxtrackcode, '</a>') "
                 . "AS trackcode , "
                 . "(TO_DAYS(NOW())-TO_DAYS(o.oxsenddate)) AS days, DATE(o.oxsenddate) AS senddate, "
                 . "GROUP_CONCAT(CONCAT('<nobr>', a.oxamount, ' x ', a.oxtitle, IF (a.oxselvariant != '', CONCAT(' &ndash; ', a.oxselvariant), ''), '</nobr>') SEPARATOR '<br>') AS orderlist, "
                 . "IF(o.oxremark!='', "
                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                        . "''"
                    . "), "
                    . "''"
                 . ") AS remark "
                 . "FROM oxorder o, oxorderarticles a "
                 . "WHERE "
                    . "oxpaymenttype = {$this->PaymentInvoice[$this->SiteID]} "
                    . "AND o.oxsenddate != '0000-00-00 00:00:00' "
                    . "AND o.oxpaid = '0000-00-00 00:00:00' "
                    . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                    . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                    . "AND o.oxstorno = 0 "
                    . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                    . "AND o.oxid = a.oxorderid  "
                 . "GROUP BY o.oxordernr "
                 . "ORDER BY days ASC "; 

        if ($this->DebugMode) logfile('debug', 'getInvoiceNotPaid: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getInvoiceNotPaid: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getInvoiceNotPaid: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $sumInvoices = 0.0;
        $i = 0;
        foreach($dbData as $value) {
            $sumInvoices += $value['ordersum'];
            $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
            if (($period == 'day') || ($period == 'range')) {
                if (strpos($dbData[$i]['trackcode'], 'http') === FALSE) {
                    $newURL = 'href="' . $this->CarrierTrackingUrl[$this->SiteID] ;
                    $dbData[$i]['trackcode'] = str_replace('href="', $newURL, $dbData[$i]['trackcode']);
                }
            }
            $i++;
        }

        // convert this array to a DataTable object
            /*$dataTable = new Piwik_DataTable();
            $dataTable->addRowsFromArrayWithIndexLabel($dbData);*/
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        $this->Style = 'font-weight:bold;';
        $dataTable->addSummaryRow(new Row(array(
                        Row::COLUMNS => array(
                            'days'=>' ', 
                            'senddate'=>' ', 
                            'orderno'=>' ', 
                            'trackcode'=>' ', 
                            'company'=>' ', 
                            'name'=>' ', 
                            'email'=>' ', 
                            'orderlist' => '<div style="text-align:right;font-weight:bold;">'.Piwik::translate('OxidAnalysis_Sum').'</div>',
                            'ordersum' => $this->oaCurrFormat($sumInvoices, $this) 
                            )
                    )));
        
        return $dataTable;
    }


    // Retrieving the already payed pay in advance orders
    public function getPrePaid($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        
        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT o.oxordernr AS orderno, o.oxtotalordersum AS ordersum, o.oxbillsal AS salutation, "
                 . "CONCAT('<nobr>', o.oxbillcompany, '</nobr>') AS company, "
                 . "CONCAT('<a href=\"mailto:', o.oxbillemail, '\" style=\"text-decoration:underline;\"><nobr>', o.oxbillfname, '&nbsp;', o.oxbilllname, '</nobr></a>') AS name, "
                 . "IF (o.oxdelcity = '', "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity\'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillstreet, '&nbsp;', o.oxbillstreetnr, ', ', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelstreet, '&nbsp;', o.oxdelstreetnr, ', ', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                    . ") AS  custdeladdr, "
                 . "p.oxdesc AS paytype, "
                 . "(TO_DAYS(NOW())-TO_DAYS(o.oxorderdate)) AS days, DATE(o.oxorderdate) AS orderdate, "
                 . "GROUP_CONCAT(CONCAT('<nobr>', a.oxamount, ' x ', a.oxtitle, IF (a.oxselvariant != '', CONCAT(' &ndash; ', a.oxselvariant), ''), '</nobr>') SEPARATOR '<br>') AS orderlist, "
                 . "IF(o.oxremark!='', "
                    . "IF((SELECT o.oxremark LIKE '{$this->IgnoreRemark[$this->SiteID]}') != 1,"
                        . "CONCAT('<img SRC=\"plugins/OxidAnalysis/images/remarks.png\" ALT=\"', o.oxremark, '\" TITLE=\"', o.oxremark, '\" />'), "
                        . "''"
                    . "), "
                    . "''"
                 . ") AS remark "
                 . "FROM oxorder o, oxorderarticles a, oxpayments p "
                 . "WHERE "
                    . "o.oxpaymenttype = p.oxid "
                    . "AND o.oxpaid != '0000-00-00 00:00:00' "
                    . "AND o.oxsenddate = '0000-00-00 00:00:00' "
                    . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                    . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                    . "AND o.oxstorno = 0 "
                    . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                    . "AND o.oxid = a.oxorderid "
                 . "GROUP BY o.oxordernr "
                 . "ORDER BY days ASC "; 

        if ($this->DebugMode) logfile('debug', 'getPrePaid: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getPrePaid: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getPrePaid: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $sumPrePaid = 0.0;
        $i = 0;
        foreach($dbData as $value) {
                $sumPrePaid += $value['ordersum'];
                $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
                $i++;
        }

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        $this->Style = 'font-weight:bold;';
        $dataTable->addSummaryRow(new Row(array(
                        Row::COLUMNS => array(
                            'days'=>' ', 
                            'orderdate'=>' ', 
                            'orderno'=>' ', 
                            'company'=>' ', 
                            'name'=>' ', 
                            'email'=>' ', 
                            'orderlist' => '<div style="text-align:right;font-weight:bold;">'.Piwik::translate('OxidAnalysis_Sum').'</div>',
                            'ordersum' => $this->oaCurrFormat($sumPrePaid, $this) 
                            )
                    )));
        
        return $dataTable;
    }


    // Retrieving the Top Seller  
    public function getTopSeller($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT SUM(oa.oxamount) AS artcount, oa.oxartnum AS artno, CONCAT('<nobr>', oa.oxtitle, '</nobr>') AS arttitle, "
                . "SUM(oa.oxbrutprice) AS artrev, "
                . "SUM(oa.oxnetprice)"
                    . "-IF(a.oxbprice=0.0,"
                        . "IF(a.oxparentid!='',"
                            . "(SELECT b.oxbprice FROM oxarticles b WHERE b.oxid=a.oxparentid),"
                            . "a.oxbprice),"
                        . "a.oxbprice)*SUM(oa.oxamount) AS artmargin "
             . "FROM oxorder o, oxorderarticles oa, oxarticles a " 
             . "WHERE o.oxid = oa.oxorderid "
                . "AND oa.oxartid = a.oxid " 
                . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                . "AND o.oxstorno = 0 "
                . "AND oa.oxstorno = 0 "
             . "GROUP BY oa.oxtitle " 
             . "ORDER BY artcount DESC "; 

        if ($this->DebugMode) logfile('debug', 'getTopSeller: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getTopSeller: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getCODnotReceived: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $i = 0;
        foreach($dbData as $value) {
                $dbData[$i]['artcount'] = intFormat($dbData[$i]['artcount'], $this);
                $dbData[$i]['artrev'] = $this->oaCurrFormat($dbData[$i]['artrev'], $this);
                $dbData[$i]['artmargin'] = $this->oaCurrFormat($dbData[$i]['artmargin'], $this);
                $i++;
        }

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    // Retrieving the Top Cancels  
    public function getTopCancels($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $sql = "SELECT sum(oa.oxamount) AS artcount, oa.oxartnum AS artno, CONCAT('<nobr>', oa.oxtitle, '</nobr>') AS arttitle, "
                . "SUM(oa.oxbrutprice) AS artrev, "
                . "SUM(oa.oxnetprice)"
                    . "-IF(a.oxbprice=0.0,"
                        . "IF(a.oxparentid!='',"
                            . "(SELECT b.oxbprice FROM oxarticles b WHERE b.oxid=a.oxparentid),"
                            . "a.oxbprice),"
                        . "a.oxbprice)*SUM(oa.oxamount) AS artmargin "
             . "FROM oxorder o, oxorderarticles oa, oxarticles a " 
             . "WHERE o.OXID = oa.OXORDERID "
                . "AND oa.oxartid = a.oxid " 
                . "AND DATE(o.oxorderdate) >= '{$dateStart}' "
                . "AND DATE(o.oxorderdate)  <= '{$dateEnd}' "
                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                . "AND (o.oxstorno = 1 OR oa.oxstorno = 1) "
                . "GROUP BY oa.oxtitle " 
             . "ORDER BY artcount DESC  "; 

        if ($this->DebugMode) logfile('debug', 'getTopCancels: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getTopCancels: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getCODnotReceived: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $i = 0;
        foreach($dbData as $value) {
                $dbData[$i]['artcount'] = intFormat($dbData[$i]['artcount'], $this);
                $dbData[$i]['artrev'] = $this->oaCurrFormat($dbData[$i]['artrev'], $this);
                $dbData[$i]['artmargin'] = $this->oaCurrFormat($dbData[$i]['artmargin'], $this);
                $i++;
        }

        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    // Retrieving the Sum and Percentage per Age and Payment  
    public function getAgeAnalysis($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $this->AgeClasses[$this->SiteID] = '0-3000|' . $this->AgeClasses[$this->SiteID];
        $ageClasses = explode('|', $this->AgeClasses[$this->SiteID]);
        $data = array();

        $sql = array();
        $total = array();
        $andWhere = " AND u.oxshopid = {$this->ShopID[$this->SiteID]} "; 

        array_push($sql,  "SELECT COUNT(*) AS total FROM oxuser WHERE oxshopid = {$this->ShopID[$this->SiteID]} ");
        array_push($sql,  "SELECT SUM(o.oxtotalordersum) AS total FROM oxuser u, oxorder o "
                            . "WHERE  u.oxid = o.oxuserid AND o.oxstorno=0 " . $andWhere );
        array_push($sql,  "SELECT COUNT(*) AS total FROM oxuser u " 
                            . "WHERE u.oxsal = 'MR' AND YEAR(u.oxbirthdate) != 0 " . $andWhere );
        array_push($sql,  "SELECT COUNT(*) AS total FROM oxuser u " 
                            . "WHERE u.oxsal = 'MRS' AND YEAR(u.oxbirthdate) != 0 " . $andWhere );
        array_push($sql,  "SELECT SUM(o.oxtotalordersum) AS total FROM oxuser u, oxorder o "
                            . "WHERE  u.oxid = o.oxuserid AND o.oxstorno=0 AND oxsal = 'MR' AND YEAR(oxbirthdate) != 0 " . $andWhere );
        array_push($sql,  "SELECT SUM(o.oxtotalordersum) AS total FROM oxuser u, oxorder o "
                            . "WHERE  u.oxid = o.oxuserid AND o.oxstorno=0 AND oxsal = 'MRS' AND YEAR(oxbirthdate) != 0 " . $andWhere );
        $payments = getOxPayments($this);
        foreach ($payments as $payment)
            array_push($sql, "SELECT SUM(o.oxtotalordersum) AS total FROM oxuser u, oxorder o, oxpayments p "
                                . "WHERE  u.oxid = o.oxuserid AND o.oxpaymenttype = p.oxid AND p.oxdesc='{$payment}' " 
                                . "AND o.oxstorno=0 AND YEAR(u.oxbirthdate) != 0 " . $andWhere );

        $db = openDB($this);
        foreach ($sql as $stmt) {
            if ($this->DebugMode) logfile('debug', 'getAgeAnalysis: '.$stmt);
            $result = $db->query($stmt);
            if (($this->DebugMode) && (is_bool($result)))
                logfile('error', 'getAgeAnalysis: [ERROR] '.$stmt);
            $dbData = $result->fetch();
            if (empty($dbData['total'])) $dbData['total'] = '1.0';
                array_push($total, $dbData['total']);
        }
        if ($this->DebugMode) logfile('debug', $total);

        foreach ($ageClasses as $ageClass) {
            if ($this->DebugMode) logfile('debug', 'getAgeAnalysis: ageClass = '.$ageClass);
            $sql = array();

            if ($ageClass == '0-3000') 
                $totalval=$total[0]; 
            else 
                $totalval=$total[2];
            array_push($sql, "SELECT COUNT(*) AS totalsum, COUNT(*)/{$totalval}*100.0 AS percent " 
                                . "FROM oxuser u " 
                                . "WHERE u.oxsal = 'MR' " 
                                . "AND (YEAR(curdate())-YEAR(u.oxbirthdate)) BETWEEN " . str_replace('-', ' AND ', $ageClass) . $andWhere  );

            if ($ageClass == '0-3000') 
                $totalval=$total[0]; 
            else 
                $totalval=$total[3];
            array_push($sql, "SELECT COUNT(*) AS totalsum, COUNT(*)/{$totalval}*100.0 AS percent " 
                                . "FROM oxuser u " 
                                . "WHERE u.oxsal = 'MRS' " 
                                . "AND (YEAR(curdate())-YEAR(u.oxbirthdate)) BETWEEN " . str_replace('-', ' AND ', $ageClass) . $andWhere  );

            if ($ageClass == '0-3000') 
                $totalval=$total[1]; 
            else 
                $totalval=$total[4];
            array_push($sql, "SELECT SUM(o.oxtotalordersum) AS totalsum, SUM(o.oxtotalordersum)/{$totalval}*100.0 AS percent "  
                                . "FROM oxuser u, oxorder o "
                                . "WHERE  u.oxid = o.oxuserid "
                                . "AND o.oxstorno = 0 "
                                . "AND u.oxsal = 'MR' " 
                                . 'AND (YEAR(curdate())-YEAR(u.oxbirthdate)) BETWEEN ' . str_replace('-', ' AND ', $ageClass) . $andWhere  );

            if ($ageClass == '0-3000') 
                $totalval=$total[1]; 
            else 
                $totalval=$total[5];
            array_push($sql, "SELECT SUM(o.oxtotalordersum) AS totalsum, SUM(o.oxtotalordersum)/{$totalval}*100.0 AS percent "  
                                . "FROM oxuser u, oxorder o "
                                . "WHERE  u.oxid = o.oxuserid "
                                . "AND o.oxstorno = 0 "
                                . "AND u.oxsal = 'MRS' " 
                                . "AND (YEAR(curdate())-YEAR(u.oxbirthdate)) BETWEEN " . str_replace('-', ' AND ', $ageClass) . $andWhere  );

            $i = 5;
            foreach ($payments as $payment) {
                $i++;
                if ($ageClass == '0-3000') 
                    $totalval=$total[1]; 
                else 
                    $totalval=$total[$i];
                array_push($sql, "SELECT SUM(o.oxtotalordersum) AS totalsum, SUM(o.oxtotalordersum)/{$totalval}*100.0 AS percent "  
                                    . "FROM oxuser u, oxorder o, oxpayments p "
                                    . "WHERE u.oxid = o.oxuserid "
                                    . "AND o.oxpaymenttype = p.oxid "
                                    . "AND p.oxdesc = '{$payment}' "
                                    . "AND o.oxstorno = 0 "
                                    . "AND (YEAR(curdate())-YEAR(u.oxbirthdate)) BETWEEN " . str_replace('-', ' AND ', $ageClass) . $andWhere  );
            }

            if ($this->DebugMode) $sumval = array();
            $percentage = array();
            foreach ($sql as $stmt) {
                if ($this->DebugMode) logfile('debug', 'getAgeAnalysis: '.$stmt);
                $result = $db->query($stmt);
                if (($this->DebugMode) && (is_bool($result)))
                    logfile('error', 'getAgeAnalysis: [ERROR] '.$stmt);
                //-old-$dbData = $result->fetch(PDO::FETCH_ASSOC);
                $dbData = $result->fetch();
                if (empty($dbData['percent'])) $dbData['percent'] = '0.0';
                array_push($percentage, $dbData['percent']);
                if ($this->DebugMode) array_push($sumval, $dbData['totalsum']);
            }
            if ($this->DebugMode) logfile('debug', 'SUMVAL');
            if ($this->DebugMode) logfile('debug', $sumval);

            if ($ageClass == '0-3000') {
                $ageClass = 'All';
                $bold = '<b>';
                $this->Style = 'font-weight:bold;';
            } else {
                $bold = '';
                $this->Style = '';
            }
            $temp = array('ageclass' => $bold . str_replace('-', '&nbsp;-&nbsp;', $ageClass), 
                    'mencount' => percFormat($percentage[0], $this), 
                    'womencount' => percFormat($percentage[1], $this), 
                    'menrevenue' => percFormat($percentage[2], $this), 
                    'womenrevenue' => percFormat($percentage[3], $this));
            $i = 3;
            foreach ($payments as $payment) {
                $i++;
                $temp[$payment] = percFormat($percentage[$i], $this);
            }

            array_push($data, $temp);
        }

        $db = null;

        // convert this array to a DataTable object
        if ($this->DebugMode) logfile('debug', $data);
        //-old-$dataTable = new Piwik_DataTable();
        //-old-$dataTable->addRowsFromArrayWithIndexLabel($data);
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($data);
        
        return $dataTable;
    }


    // Retrieving the Sum and Percentage per Age and Payment  
    public function getStoreStatus($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);
        
        // oxstock     - amount in store
        // oxstockflag - 1: default
        //               2: offline when oxstock=0
        //               3: not buyable when oxstock=0
        //               4: foreign store
        // oxdelivery  - date when deliverable again
        // oxremindamount - amount when low email is sent
        // oxstocktext 
        // oxnostocktext 

        $sql = "SELECT a.oxartnum, "
                . "IF(a.oxparentid = '',"
                    . "CONCAT('<nobr>', oxtitle, '</nobr>'),"
                    . "CONCAT('<nobr>', (SELECT b.oxtitle FROM oxarticles b WHERE a.oxparentid = b.oxid), '</nobr>')"
                . ") AS oxtitle, "
                . "CONCAT('<nobr>', a.oxvarselect, '</nobr>') AS oxvarselect, a.oxstock, a.oxstocktext, "
                . "CONCAT('<nobr>', a.oxnostocktext, '</nobr>') AS oxnostocktext, "
                . "a.oxstockflag, a.oxdelivery, a.oxremindamount, a.oxvarcount, oxstock " 
            . "FROM oxarticles a "
            . "WHERE a.oxstock = 0 "
                . "AND a.oxactive = 1 "
                . "AND oxshopid = {$this->ShopID[$this->SiteID]} "
                . "AND oxvarcount = 0 "
            . "ORDER BY a.oxartnum ";
        
        if ($this->DebugMode) logfile('debug', 'getStoreStatus: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getTopSeller: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getCODnotReceived: pdo->execute error = '.$e->getMessage() );
            die();
        }

        $dataTable = new DataTable();
        // convert this array to a DataTable object
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    // Retrieving the Sum and Percentage per Weekday or Daytime 
    public function getDaytimeAnalysis($idSite, $period, $date, $segment = false)
    {
        return $this->getTimeAnalysis('daytime', $idSite, $period, $date, $segment = false);
    }


    // Retrieving the Sum and Percentage per Weekday or Daytime 
    public function getWeekdayAnalysis($idSite, $period, $date, $segment = false)
    {
        return $this->getTimeAnalysis('weekday', $idSite, $period, $date, $segment = false);
    }


    // Retrieving the Sum and Percentage per Weekday or Daytime 
    public function getTimeAnalysis($timeType, $idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        switch ($period) {

            case 'day':
                $whereTime = "date(o.oxorderdate) = '{$dateStart}' ";
                break;

            case 'range':
            case 'week':
            case 'month':
            case 'year':
                $whereTime = "date(o.oxorderdate) >= '{$dateStart}' AND  date(o.oxorderdate) <= '{$dateEnd}' ";
                break;

        }

        $sql = "SELECT SUM(IF(d.oxstorno=0,d.oxbrutprice,0)) AS revenue23,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0)) AS cancel "
                . "FROM oxorderarticles d, oxorder o "
                . "WHERE o.oxid = d.oxorderid "
                    . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                    . "AND " . $whereTime . " ";
                    
        if ($this->DebugMode) logfile('debug', 'getTimeAnalysis: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getPrePaid: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getTimeAnalysis: pdo->execute error = '.$e->getMessage() );
            die();
        }
        if ($this->DebugMode) logfile('debug', $dbData);
        $totalRevenue = $dbData[0]['revenue23'];
        $totalCancel = $dbData[0]['cancel'];
        if ($this->DebugMode) logfile('debug', 'getTimeAnalysis: totalRevenue='.$totalRevenue);
        if ($this->DebugMode) logfile('debug', 'getTimeAnalysis: totalCancel='.$totalCancel);

        if ($timeType == 'weekday') {
            $sql = "SELECT DAYNAME(o.oxorderdate) AS dayname, WEEKDAY(o.oxorderdate) AS inum, "
                 . "SUM(IF(d.oxstorno=0,d.oxbrutprice,0)) AS revenue23,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0)) AS cancel, "
                 . "SUM(IF(d.oxstorno=0,d.oxbrutprice,0))/{$totalRevenue}*100.0 AS revshare,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0))/{$totalCancel}*100.0 AS cancshare, "
                 . "SUM(IF(d.oxstorno=1,d.oxbrutprice,0))/SUM(IF(d.oxstorno=0,d.oxbrutprice,0))*100.0 AS cancpercent "
                 . "FROM oxorderarticles d, oxorder o "
                 . "WHERE o.oxid = d.oxorderid "
                     . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                     . "AND " . $whereTime . " "
                 . "GROUP BY WEEKDAY(o.oxorderdate) "
                 . "ORDER BY WEEKDAY(o.oxorderdate) ";
        } else {
            $sql = "SELECT TIME_FORMAT(o.oxorderdate, '%H:00') as daytime, TIME_FORMAT(o.oxorderdate, '%k') as inum, "
                 . "SUM(IF(d.oxstorno=0,d.oxbrutprice,0)) AS revenue23,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0)) AS cancel, "
                 . "SUM(IF(d.oxstorno=0,d.oxbrutprice,0))/{$totalRevenue}*100.0 AS revshare,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0))/{$totalCancel}*100.0 AS cancshare, "
                 . "SUM(IF(d.oxstorno=1,d.oxbrutprice,0))/SUM(IF(d.oxstorno=0,d.oxbrutprice,0))*100.0 AS cancpercent "
                 . "FROM oxorderarticles d, oxorder o "
                 . "WHERE o.oxid = d.oxorderid "
                     . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                     . "AND " . $whereTime . " "
                 . "GROUP BY TIME_FORMAT(o.oxorderdate, '%H') "
                 . "ORDER BY TIME_FORMAT(o.oxorderdate, '%H') ";
        }
        if ($this->DebugMode) logfile('debug', 'getTimeAnalysis: '.$sql);
        try {
            $db = openDB($this);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', 'getPrePaid: '.$sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getTimeAnalysis: pdo->execute error = '.$e->getMessage() );
            die();
        }
        if ($this->DebugMode) logfile('debug', $dbData);

        $i = 0;
        foreach ($dbData as $value) {
            if (($dbData[$i]['revenue23']==0.0) && ($dbData[$i]['cancel']!=0.0))
                $dbData[$i]['cancpercent'] = 100.0;
            $dbData[$i]['revenue23'] = $this->oaCurrFormat($dbData[$i]['revenue23'], $this);
            $dbData[$i]['cancel'] = $this->oaCurrFormat($dbData[$i]['cancel'], $this);
            $dbData[$i]['revshare'] = percFormat($dbData[$i]['revshare'], $this);
            $dbData[$i]['cancshare'] = percFormat($dbData[$i]['cancshare'], $this);
            $dbData[$i]['cancpercent'] = percFormat($dbData[$i]['cancpercent'], $this);
            $i++;
        }
        if ($this->DebugMode) logfile('debug', $dbData);

        if ($timeType == 'weekday') {
            //$maxCount = 7;   //days per week
            $weekdays = array(
                '<span style="display:none;">0</span>'.Piwik::translate('OxidAnalysis_Monday'), 
                '<span style="display:none;">1</span>'.Piwik::translate('OxidAnalysis_Tuesday'), 
                '<span style="display:none;">2</span>'.Piwik::translate('OxidAnalysis_Wednesday'), 
                '<span style="display:none;">3</span>'.Piwik::translate('OxidAnalysis_Thursday'), 
                '<span style="display:none;">4</span>'.Piwik::translate('OxidAnalysis_Friday'), 
                '<span style="display:none;">5</span>'.Piwik::translate('OxidAnalysis_Saturday'), 
                '<span style="display:none;">6</span>'.Piwik::translate('OxidAnalysis_Sunday'));

            // if no record returned create one entry with zeros
            if (count($dbData) == 0)
                array_push($dbData, array(
                    'inum' => 0,
                    'dayname' => $weekdays[0], 
                    'revenue23' => $this->oaCurrFormat(0.0, $this), 
                    'revshare' => percFormat(0.0, $this), 
                    'cancel' => $this->oaCurrFormat(0.0, $this), 
                    'cancshare' => percFormat(0.0, $this),  
                    'cancpercent' => percFormat(0.0, $this) ));
            $k = 0;
            for ($i=0; $i<7; $i++)
                if ( $dbData[$k]['inum'] != $i )
                    array_push($dbData, array(
                        'inum' => $i,
                        'dayname' => $weekdays[$i], 
                        'revenue23' => $this->oaCurrFormat(0.0, $this), 
                        'revshare' => percFormat(0.0, $this), 
                        'cancel' => $this->oaCurrFormat(0.0, $this), 
                        'cancshare' => percFormat(0.0, $this),  
                        'cancpercent' => percFormat(0.0, $this) ));
                else {
                    $dbData[$k]['dayname'] = $weekdays[$i];
                    if(count($dbData)-1 > $k) 
                        $k++;
                }
        } else {
            //$max = 24;  //hours per day

            // if no record returned create one entry with zeros
            if (count($dbData) == 0)
                array_push($dbData, array(
                    'inum' => $i,
                    'daytime' => str_pad(0, 2, '0', STR_PAD_LEFT).':00', 
                    'revenue23' => $this->oaCurrFormat(0.0, $this), 
                    'revshare' => percFormat(0.0, $this), 
                    'cancel' => $this->oaCurrFormat(0.0, $this), 
                    'cancshare' => percFormat(0.0, $this),  
                    'cancpercent' => percFormat(0.0, $this) ));

            $k = 0;
            for ($i=0; $i<24; $i++)
                if ( $dbData[$k]['inum'] != $i )
                    array_push($dbData, array(
                        'inum' => $i,
                        'daytime' => str_pad($i, 2, '0', STR_PAD_LEFT).':00', 
                        'revenue23' => $this->oaCurrFormat(0.0, $this), 
                        'revshare' => percFormat(0.0, $this), 
                        'cancel' => $this->oaCurrFormat(0.0, $this), 
                        'cancshare' => percFormat(0.0, $this),  
                        'cancpercent' => percFormat(0.0, $this) ));
                else
                    if(count($dbData)-1 > $k)
                        $k++;
        }



        $dataTable = new DataTable();
        // convert this array to a DataTable object
        $dataTable = DataTable::makeFromIndexedArray($dbData);
        if ($this->DebugMode) logfile('debug', '------------------dataTable-----------------');
        if ($this->DebugMode) logfile('debug', $dataTable);

        return $dataTable;
    }


    // Retrieving the Sum and Percentage per Weekday or Daytime 
    public function getDaytimeGraph($date, $period)
    {
include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
if ($this->DebugMode) logfile('debug', 'getDaytimeGraph(): date');
if ($this->DebugMode) logfile('debug', 'date='.$date);
        return $this->getTimeAnalysisGraph($date, $period, 'daytime');
    }


    // Retrieving the Sum and Percentage per Weekday or Daytime 
    public function getWeekdayGraph($date, $period)
    {
include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
if ($this->DebugMode) logfile('debug', 'getWeekdayGraph(): date');
if ($this->DebugMode) logfile('debug', 'date='.$date);
        return $this->getTimeAnalysisGraph($date, $period, 'weekday');

    }


    // Retrieving the Sum and Percentage per Weekday or Daytime 
    public function getTimeAnalysisGraph($date, $period, $timeType = 'weekday')
    {
        //error_reporting(E_ALL);
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): ***START***');
if ($this->DebugMode) logfile('debug', '$timeType='.$timeType);
if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): date');
if ($this->DebugMode) logfile('debug', 'date='.$date);
        $this->SiteID = Piwik_Common::getRequestVar('idSite');
        $db = openDB($this);

        $date = Piwik_Common::getRequestVar('date');
if ($this->DebugMode) logfile('debug', 'date='.$date);
        $period = Piwik_Common::getRequestVar('period');
        $this->Currency = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));

            // works wrong for period=range: $period = Piwik_Common::getRequestVar('period');
            // Work around for the this bug
            /* no longer necessary
            if (strpos($date, ',') > 0)
                    $period = 'range';*/

if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): vor Range');
        if ($period == 'range') {
            $dateStart = substr($date, 0, strpos($date, ',')); 
            $dateEnd = substr($date, strpos($date, ',')+1);  
        } else {
if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): vor requestedDate = ..');
            //$requestedDate = Piwik_Date::factory($date);
//if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): requestedDate = '.$requestedDate);
            //$requestedPeriod = Piwik_Period::factory($period, $requestedDate);
            $requestedPeriod = new Piwik_Period_Range($period, $date);
//if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): vor requestedPeriod = '.$requestedPeriod);
            $dateStart = $requestedPeriod->getDateStart()->toString('Y-m-d'); 
if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): vor dateStart = '.$dateStart);
            $dateEnd = $requestedPeriod->getDateEnd()->toString('Y-m-d');   
if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph(): vor dateEnd = '.$dateEnd);
        }

if ($this->DebugMode) logfile('debug', 'vor Switch');
            switch ($period) {

                case 'day':
                    $whereTime = 'date(o.oxorderdate) = \''. $dateStart.'\' ';
                    break;

                case 'range':
                case 'week':
                case 'month':
                case 'year':
                    $whereTime = 'date(o.oxorderdate) >= \''. $dateStart.'\' AND  date(o.oxorderdate) <= \''. $dateEnd.'\' ';
                    break;

            }

            $sql = 'SELECT SUM(IF(d.oxstorno=0,d.oxbrutprice,0)) AS revenue,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0)) AS cancel '
                    . 'FROM oxorderarticles d, oxorder o '
                    . 'WHERE o.oxid = d.oxorderid '
                        . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                        . 'AND ' . $whereTime . ' ';
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph: '.$sql);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $dbData = $stmt->fetchAll();
            if ($this->DebugMode) logfile('debug', $dbData);
            $totalRevenue = $dbData[0]['revenue'];
            $totalCancel = $dbData[0]['cancel'];
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph: totalRevenue='.$totalRevenue);
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph: totalCancel='.$totalCancel);

            if ($timeType == 'weekday') {
                $sql = 'SELECT WEEKDAY(o.oxorderdate) AS inum, DAYNAME(o.oxorderdate) AS dayname, '
                     . 'SUM(IF(d.oxstorno=0,d.oxbrutprice,0)) AS revenue,  SUM(IF(d.oxstorno=1,d.oxbrutprice,0)) AS cancel '
                     . 'FROM oxorderarticles d, oxorder o '
                     . 'WHERE o.oxid = d.oxorderid '
                         . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                         . 'AND ' . $whereTime . ' '
                     . 'GROUP BY WEEKDAY(o.oxorderdate) '
                     . 'ORDER BY WEEKDAY(o.oxorderdate) ';
            } else {
                $sql = 'SELECT TIME_FORMAT(o.oxorderdate, \'%k\') as inum, TIME_FORMAT(o.oxorderdate, \'%H:00\') as daytime, '
                     . 'SUM(IF(d.oxstorno=0,d.oxbrutprice,0)) AS revenue, SUM(IF(d.oxstorno=1,d.oxbrutprice,0)) AS cancel '
                     . 'FROM oxorderarticles d, oxorder o '
                     . 'WHERE o.oxid = d.oxorderid '
                         . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                         . 'AND ' . $whereTime . ' '
                     . 'GROUP BY TIME_FORMAT(o.oxorderdate, \'%H\') '
                     . 'ORDER BY TIME_FORMAT(o.oxorderdate, \'%H\') ';
            }
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph: '.$sql);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $dbData = $stmt->fetchAll(PDO::FETCH_NAMED);
            if ($this->DebugMode) logfile('debug', $dbData);

            $dbGraph = array();
            if ($timeType == 'weekday') {
                //$maxCount = 7;   //days per week  
                $weekdays = array(
                    Piwik_Translate('OxidPlugin_Monday'), 
                    Piwik_Translate('OxidPlugin_Tuesday'), 
                    Piwik_Translate('OxidPlugin_Wednesday'), 
                    Piwik_Translate('OxidPlugin_Thursday'), 
                    Piwik_Translate('OxidPlugin_Friday'), 
                    Piwik_Translate('OxidPlugin_Saturday'), 
                    Piwik_Translate('OxidPlugin_Sunday') );
                $k = 0;
                for ($i=0; $i<7; $i++)
                    if ( $dbData[$k]['inum'] != $i ) {
                        $dbGraph[$weekdays[$i]] = array('revenue' => 0.0, 'cancel' => 0.0);
                    } else {
                        $dbGraph[$weekdays[$i]] = array('revenue' => $dbData[$k]['revenue'], 'cancel' => $dbData[$k]['cancel']);
                        if (count($dbData)-1 > $k)
                            $k++;
                    }
            } else {
                //$max = 24;  //hours per day
                $k = 0;
                for ($i=0; $i<24; $i++)
                    if ( $dbData[$k]['inum'] != $i ) {
                        if ($this->DebugMode) logfile('debug', 'dbGraph: 0.0');
                        $dbGraph[str_pad($i, 2, '0', STR_PAD_LEFT).':00'] = array('revenue' => 0.0, 'cancel' => 0.0);
                    } else {
                        $dbGraph[$dbData[$k]['daytime']] = array('revenue' => $dbData[$k]['revenue'], 'cancel' => $dbData[$k]['cancel']);
                        if (count($dbData)-1 > $k)
                            $k++;
                    }
            }/*--*/
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph/dbGraph---------------------------');
            if ($this->DebugMode) logfile('debug', $dbGraph);
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph/dbGraph---------------------------');
            if ($this->DebugMode) logfile('debug', 'getTimeAnalysisGraph ***END***');

            $dataTable = new Piwik_DataTable();
            // convert this array to a DataTable object
            $dataTable->addRowsFromArrayWithIndexLabel($dbGraph);

            $db = null;

            return $dataTable;
            //return;

    }


    // Retrieving the Sum and Count per Manufacturer  
    public function getManufacturerRevenue($idSite, $period, $date)
    {
        return $this->getDelivererRevenue($idSite, $period, $date, 'oxmanufacturer');
    }


    // Retrieving the Sum per Manufacturer for  Bar Chart Display  
    public function getManufacturerRevenueGraph($idSite, $period, $date)
    {
        return $this->getDelivererRevenueGraph($idSite, $period, $date, 'oxmanufacturer');
    }


    // Retrieving the Sum and Count per Vendor  
    public function getVendorRevenue($idSite, $period, $date)
    {
        return $this->getDelivererRevenue($idSite, $period, $date, 'oxvendor');
    }


    // Retrieving the Sum per Vendor for  Bar Chart Display  
    public function getVendorRevenueGraph($idSite, $period, $date)
    {
            return $this->getDelivererRevenueGraph($idSite, $period, $date, 'oxvendor');
    }


    // Retrieving the Sum and Count per Deliverer (Manufacturer or Vendor)  
    public function getDelivererRevenue($idSite, $period, $date, $delivererType)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();
        
        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);
        if ($this->DebugMode) logfile ('debug', " ========================= getDelivererRevenue ====================");

            /*
            $this->SiteID = Piwik_Common::getRequestVar('idSite');
            $db = openDB($this);

            $date = Piwik_Common::getRequestVar('date');
            $period = Piwik_Common::getRequestVar('period');
            $this->Currency = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));
            */

            // works wrong for period=range: $period = Piwik_Common::getRequestVar('period');
            // Work around for the this bug
            /* no longer necessary
            if (strpos($date, ',') > 0)
                    $period = 'range';*/

            /*if ($period == 'range') {
                    $dateStart = substr($date, 0, strpos($date, ',')); 
                    $dateEnd = substr($date, strpos($date, ',')+1);  
            } else {
                    $requestedDate = Piwik_Date::factory($date);
                    $requestedPeriod = Piwik_Period::factory($period, $requestedDate);
                    $dateStart = $requestedPeriod->getDateStart()->toString('Y-m-d'); 
                    $dateEnd = $requestedPeriod->getDateEnd()->toString('Y-m-d');   
            }*/

        if ($delivererType == 'oxmanufacturer') {
            $delivererid = 'oxmanufacturerid';
            $delivererTable = 'oxmanufacturers';
        }
        else {
            $delivererid = 'oxvendorid';
            $delivererTable = 'oxvendor';
        }

        $sql = "SELECT oxid, oxtitle FROM {$delivererTable} "
             . "WHERE oxshopid = {$this->ShopID[$this->SiteID]} "
             . "ORDER BY oxtitle ASC ";
        $db = openDB($this);
        $Deliverers = $this->oaQuery($db, $sql, 'getDelivererRevenue');
            //$result = $db->prepare($sql);
            //$result->execute();
            //$Deliverers = $result->fetchAll(PDO::FETCH_NAMED);
        if ($this->DebugMode) logfile('debug', $sql);
        if ($this->DebugMode) logfile('debug', $Deliverers);

        switch ($period) {

            case 'day':
                $whereTime = "date(o.oxorderdate) = '{$dateStart}' ";
                break;

            case 'range':
            case 'week':
            case 'month':
            case 'year':
                $whereTime = "date(o.oxorderdate) >= '{$dateStart}' AND  date(o.oxorderdate) <= '{$dateEnd}' ";
                break;

        }

        $dbData = array();
        $i = 0;
        $totalbrutsum = 0.001; //avoid div0 when total revenue is zero
        $totalnetmargin = 0.001; //avoid div0 when total revenue is zero
        foreach ($Deliverers as $deliverer) {

            $sql = "SELECT '{$deliverer['oxtitle']}', SUM(d.oxamount) AS totalcount, "
                        . "(SUM(d.oxnetprice) - "
                            . "SUM(d.oxamount * a.oxbprice)"
                        . ") AS netmargin, "
                        . "SUM(d.oxamount*d.oxprice) AS brutpricesum "
                    . "FROM oxarticles a, oxorder o, oxorderarticles d "
                    . "WHERE "
                        . "a.oxid = d.oxartid "
                        . "AND o.oxid = d.oxorderid "
                        . "AND a.".$delivererid.'=\''.$deliverer['oxid'].'\''
                        . "AND o.oxstorno = 0 "
                        . "AND d.oxstorno = 0 "
                        . "AND a.oxparentid = '' "
                        . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                            . "AND {$whereTime} ";

        $dbSingleVal = $this->oaQuery($db, $sql, 'getDelivererRevenue');
                    //$result = $db->prepare($sql);
                    //$result->execute();
                    //$dbSingleVal = $result->fetchAll(PDO::FETCH_NAMED);
                    if ($this->DebugMode) logfile('debug', $deliverer['oxtitle']);
                    if ($this->DebugMode) logfile('debug', $sql);
                    if ($this->DebugMode) logfile('debug', $dbSingleVal);
                    $dbData[$i]['deliverer'] = $deliverer['oxtitle'];
                    if (!empty($dbSingleVal[0]['brutpricesum'])) {
                            $dbData[$i]['totalcount'] = $dbSingleVal[0]['totalcount'];
                            $dbData[$i]['netmargin'] = $dbSingleVal[0]['netmargin'];
                            $dbData[$i]['brutsum'] = $dbSingleVal[0]['brutpricesum'];
                            $totalbrutsum += $dbData[$i]['brutsum'];
                            $totalnetmargin += $dbData[$i]['netmargin'];
                            //$dbData[$manufacturer['oxtitle']] = $dbSingleVal[0]['oxbrutpricesum'];
                    } else {
                            $dbData[$i]['totalcount'] = 0;
                            $dbData[$i]['netmargin'] = 0.00;
                            $dbData[$i]['brutsum'] = 0.00;
                            //$dbData[$manufacturer['oxtitle']] = 0.0;
                    }


                    // Variants of articles 
                    /*******/
                    $sql = "SELECT "
                                . "(SELECT b.{$delivererid} FROM oxarticles b WHERE  a.oxparentid = b.oxid) AS delivererid, " 
                                . "SUM(d.oxamount) AS totalcount, " 
                                . "(SUM(d.oxnetprice) - "
                                . "SUM(d.oxamount * "
                                    . "IF(a.oxbprice=0.0,"
                                        . "(SELECT a2.oxbprice FROM oxarticles a2 WHERE a2.oxid = a.oxparentid),"
                                        . "a.oxbprice))"
                                . ") AS netmargin, "
                                . "SUM(d.oxbrutprice) AS brutpricesum " 
                             . "FROM "
                                . "oxorderarticles d, oxorder o, oxarticles a "
                             . "WHERE "
                                . "o.oxid = d.oxorderid "
                                . "AND d.oxartid = a.oxid "
                                . "AND a.oxparentid != '' "
                                . "AND o.oxshopid = {$this->ShopID[$this->SiteID]} "
                                . "AND {$whereTime} " 
                                . "AND o.oxstorno = 0 " 
                                . "AND d.oxstorno = 0 " 
                                . "AND (SELECT b.{$delivererid} FROM oxarticles b " 
                                    . "WHERE  a.oxparentid = b.oxid " 
                                    . "AND b.{$delivererid} = '{$deliverer['oxid']}') = '{$deliverer['oxid']}' "
                            . "GROUP BY (SELECT b.{$delivererid} FROM oxarticles b "
                                . "WHERE  a.oxparentid = b.oxid) "
                            . "ORDER BY (SELECT b.{$delivererid} FROM oxarticles b "
                                . "WHERE  a.oxparentid = b.oxid) ";
                            //. 'ORDER BY '.$delivererid.' ';
        $dbSingleVal = $this->oaQuery($db, $sql, 'getDelivererRevenue');
                    //$result = $db->prepare($sql);
                    //$result->execute();
                    //$dbSingleVal = $result->fetchAll(PDO::FETCH_NAMED);
                    if ($this->DebugMode) logfile ('debug', $sql);
                    if ($this->DebugMode) logfile ('debug', $dbSingleVal);
                    //if ($this->DebugMode) logfile ('debug', $result->rowCount());
                    $dbData[$i]['deliverer'] = $deliverer['oxtitle'];
                    if (!empty($dbSingleVal[0]['brutpricesum'])) {
                            $dbData[$i]['totalcount'] += $dbSingleVal[0]['totalcount'];
                            $dbData[$i]['brutsum'] += $dbSingleVal[0]['brutpricesum'];
                            $dbData[$i]['netmargin'] += $dbSingleVal[0]['netmargin'];
                            $totalbrutsum += $dbData[$i]['brutsum'];
                            $totalnetmargin += $dbData[$i]['netmargin'];
                    } else {
                            $dbData[$i]['totalcount'] += 0;
                            $dbData[$i]['brutsum'] += 0.00;
                    }
                     /******/


                    if ($dbData[$i]['totalcount'] != 0)
                        $i++;
            }

            $iMax = count($dbData) - 1;
            if ($dbData[$iMax]['totalcount'] == 0) {
                unset($dbData[$iMax]);
                $iMax = $iMax - 1;
            }

            for ($i=0; $i<=$iMax; $i++) {
                    $dbData[$i]['percentnet'] = percFormat( $dbData[$i]['netmargin'] / $totalnetmargin * 100.0, $this );
                    $dbData[$i]['percentbrut'] = percFormat( $dbData[$i]['brutsum'] / $totalbrutsum * 100.0, $this );
                    $dbData[$i]['totalcount'] = intFormat($dbData[$i]['totalcount'], $this);
                    $dbData[$i]['netmargin'] = $this->oaCurrFormat($dbData[$i]['netmargin'], $this);
                    $dbData[$i]['brutsum'] = $this->oaCurrFormat($dbData[$i]['brutsum'], $this);
            }

            //$dataTable = new Piwik_DataTable();
            // convert this array to a DataTable object
            //$dataTable->addRowsFromArrayWithIndexLabel($dbData);

        $db = null;
            
        // convert this array to a DataTable object
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    // Retrieving the Sum per Deliverer (manufacturer or vendor) for Bar Chart  
    public function getDelivererRevenueGraph($delivererType)
    {
            include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

            $this->SiteID = Piwik_Common::getRequestVar('idSite');
            $db = openDB($this);

            $date = Piwik_Common::getRequestVar('date');
            $period = Piwik_Common::getRequestVar('period');
            $this->Currency = Piwik::getCurrency(Piwik_Common::getRequestVar('idSite'));

            // works wrong for period=range: $period = Piwik_Common::getRequestVar('period');
            // Work around for the this bug
            /* no longer necessary
            if (strpos($date, ',') > 0)
                    $period = 'range';*/

            if ($period == 'range') {
                    $dateStart = substr($date, 0, strpos($date, ',')); 
                    $dateEnd = substr($date, strpos($date, ',')+1);  
            } else {
                    $requestedDate = Piwik_Date::factory($date);
                    $requestedPeriod = Piwik_Period::factory($period, $requestedDate);
                    $dateStart = $requestedPeriod->getDateStart()->toString('Y-m-d'); 
                    $dateEnd = $requestedPeriod->getDateEnd()->toString('Y-m-d');   
            }

            if ($delivererType == 'oxmanufacturer') {
                    $delivererid = 'oxmanufacturerid';
                    $delivererTable = 'oxmanufacturers';
            }
            else {
                    $delivererid = 'oxvendorid';
                    $delivererTable = 'oxvendor';
            }

            $sql = 'SELECT oxid, oxtitle FROM ' . $delivererTable . ' '
                 . 'WHERE oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                 . 'ORDER BY oxtitle ASC';
            $result = $db->prepare($sql);
            $result->execute();
            $Deliverers = $result->fetchAll(PDO::FETCH_NAMED);
            if ($this->DebugMode) logfile('debug', $Deliverers);

            switch ($period) {

                case 'day':
                    $whereTime = 'date(o.oxorderdate) = \''. $dateStart.'\' ';
                    break;

                case 'range':
                case 'week':
                case 'month':
                case 'year':
                    $whereTime = 'date(o.oxorderdate) >= \''. $dateStart.'\' AND  date(o.oxorderdate) <= \''. $dateEnd.'\' ';
                    break;

            }

            $dbData = array();
            $i = 0;
            foreach ($Deliverers as $deliverer) {

                    /*$sql = 'SELECT '
                                . '\''.$deliverer['oxtitle'].'\', '
                                .'SUM(d.oxbrutprice) AS oxbrutpricesum '
                             . 'FROM oxorderarticles d, oxorder o, oxarticles a '
                             . 'WHERE '
                                . 'd.oxorderid = o.oxid '
                                . 'AND '.$whereTime.' '
                                . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                                . 'AND o.oxstorno = 0 ' 
                                . 'AND d.oxartid = a.oxid '
                                . 'AND a.oxparentid = \'\' '
                                . 'AND a.'.$delivererid.'=\''.$deliverer['oxid'].'\'';*/

                    $sql = 'SELECT '
                                . '\''.$deliverer['oxtitle'].'\', '
                                . '(SUM(d.oxnetprice) - '
                                    . 'SUM(d.oxamount * a.oxbprice)'
                                . ') AS netmargin, '
                                . 'SUM(d.oxamount*d.oxprice) AS brutpricesum '
                            . 'FROM oxarticles a, oxorder o, oxorderarticles d '
                            . 'WHERE '
                                .'a.oxid = d.oxartid '
                                . 'AND o.oxid = d.oxorderid '
                                . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                                . 'AND a.'.$delivererid.'=\''.$deliverer['oxid'].'\''
                                . 'AND o.oxstorno = 0 '
                                . 'AND d.oxstorno = 0 '
                                . 'AND a.oxparentid = \'\' '
                                . 'AND '.$whereTime.' ';


                    $result = $db->prepare($sql);
                    $result->execute();
                    $dbSingleVal = $result->fetchAll(PDO::FETCH_NAMED);
                    if ($this->DebugMode) logfile('debug', $deliverer['oxtitle']);
                    if ($this->DebugMode) logfile('debug', $sql);
                    if ($this->DebugMode) logfile('debug', $dbSingleVal);
                    if (!empty($dbSingleVal[0]['brutpricesum'])) {
                            $dbData[$deliverer['oxtitle']] = $dbSingleVal[0]['brutpricesum'];
                    }

                    $sql = 'SELECT '
                                    . '(SELECT b.'.$delivererid.' FROM oxarticles b '
                                    . 'WHERE  a.oxparentid = b.oxid) AS '.$delivererid.', ' 
                                    . 'SUM(d.oxbrutprice) AS brutpricesum ' 
                             . 'FROM oxorderarticles d, oxorder o, oxarticles a '
                             . 'WHERE o.oxid = d.oxorderid '
                             . 'AND d.oxartid = a.oxid '
                             . 'AND a.oxparentid != \'\' '
                             . 'AND '.$whereTime.' ' 
                             . 'AND o.oxshopid = ' . $this->ShopID[$this->SiteID] . ' '
                             . 'AND o.oxstorno = 0 ' 
                             . 'AND d.oxstorno = 0 ' 
                             . 'AND (SELECT b.'.$delivererid.' FROM oxarticles b ' 
                                    . 'WHERE  a.oxparentid = b.oxid ' 
                                    . 'AND b.'.$delivererid.' = \''.$deliverer['oxid'].'\') = '
                                    . '\''.$deliverer['oxid'].'\' '
                            . 'GROUP BY '.$delivererid.' '
                            . 'ORDER BY '.$delivererid.' ';
                    $result = $db->prepare($sql);
                    $result->execute();
                    $dbSingleVal = $result->fetchAll(PDO::FETCH_NAMED);
                    if ($this->DebugMode) logfile ('debug', $sql);
                    if ($this->DebugMode) logfile ('debug', $dbSingleVal);
                    if (!empty($dbSingleVal[0]['brutpricesum'])) {
                            if (empty($dbData[$deliverer['oxtitle']]))
                                    $dbData[$deliverer['oxtitle']] = $dbSingleVal[0]['brutpricesum'];
                            else
                                    $dbData[$deliverer['oxtitle']] += $dbSingleVal[0]['brutpricesum'];
                    }

                    $i++;
            }

            arsort($dbData, SORT_NUMERIC);
            while (count($dbData) >= $this->MaxBarChartColumns) {
                    array_pop($dbData);
            }
            if ($this->DebugMode) logfile('debug', 'getDelivererRevenueGraph/dbData-----------------------');
            if ($this->DebugMode) logfile('debug', $dbData);
            if ($this->DebugMode) logfile('debug', 'getDelivererRevenueGraph/dbData-----------------------');

            $dataTable = new Piwik_DataTable();
            // convert this array to a DataTable object
            $dataTable->addRowsFromArrayWithIndexLabel($dbData);

            $db = null;

            return $dataTable;
    }


    function getVoucherUse($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $sql = "SELECT o.oxid, v.oxdateused, (TO_DAYS(NOW())-TO_DAYS(v.oxdateused)) AS days, s.oxdiscount, s.oxdiscounttype, "
                    . "CONCAT('<nobr>', v.oxvouchernr, '</nobr>') AS oxvouchernr, s.oxseriedescription, "
                    . "o.oxordernr, v.oxorderid, CONCAT(o.oxbillfname, '&nbsp;', o.oxbilllname) AS name, o.oxtotalordersum, " 
                    . "u.oxusername, u.oxstreet, u.oxstreetnr, u.oxzip, u.oxcity "
                . "FROM oxvouchers v, oxorder o, oxvoucherseries s, oxuser u "
                . "WHERE v.oxdateused != '0000-00-00' "
                    . "AND v.oxorderid = o.oxid "
                    . "AND v.oxvoucherserieid = s.oxid "
                    . "AND o.oxuserid = u.oxid "
                    . "AND o.oxshopid = {$this->ShopID[$idSite]} "
                . "ORDER BY v.oxdateused DESC "
                . "LIMIT 0, 500 ";

        $db = openDB($this);
        $dbData = $this->oaQuery($db, $sql, 'getVoucherUse');

        $i = 0;
        foreach($dbData as $value) {
            $dbData[$i]['oxdiscount'] = $this->oaCurrFormat($dbData[$i]['oxdiscount'], $this);
            $dbData[$i]['oxtotalordersum'] = $this->oaCurrFormat($dbData[$i]['oxtotalordersum'], $this);

            $sql = "SELECT oxamount, oxtitle "
                    . "FROM oxorderarticles "
                    . "WHERE oxorderid = '{$dbData[$i]['oxid']}' "
                        . "AND oxstorno = 0 ";
            $dbData = $this->oaQuery($db, $sql, 'getVoucherUse');
            $dbData[$i]['oxdetails'] = Piwik::translate('OxidAnalysis_Order') . ':';
            foreach ($details as $detail) {
                $dbData[$i]['oxdetails'] = $dbData[$i]['oxdetails'] . chr(13) . $detail['oxamount'] . ' x ' . $detail['oxtitle'];
            }
            $dbData[$i]['oxvouchernr'] = addTitle($dbData[$i]['oxvouchernr'], $dbData[$i]['oxseriedescription']);
            $dbData[$i]['oxtotalordersum'] = addTitle($dbData[$i]['oxtotalordersum'], $dbData[$i]['oxdetails']);
            $i++;
        }

        $db = null;

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    function getVoucherOverview($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $sql = "SELECT v.oxvouchernr, s.oxseriedescription, s.oxdiscount, s.oxdiscounttype, "
                    . "COUNT(*) AS totalcount, SUM(IF(v.oxdateused='0000-00-00',0,1)) AS usedcount, "
                    . "(TO_DAYS(MIN(v.oxdateused))-TO_DAYS(MAX(v.oxdateused))+1) AS days, "
                    . "SUM(o.oxtotalordersum) AS ordersum, AVG(o.oxtotalordersum) AS average "
                . "FROM oxvouchers v "
                    . "LEFT JOIN oxorder o "
                        . "ON (v.oxorderid = o.oxid "
                        . "AND o.oxshopid = {$this->ShopID[$idSite]}) "
                    . "LEFT JOIN oxvoucherseries s "
                        . "ON v.oxvoucherserieid = s.oxid "
                . "GROUP BY v.oxvouchernr "
                . "ORDER BY ordersum DESC "
                . "LIMIT 0, 500 "; 

        $db = openDB($this);
        $dbData = $this->oaQuery($db, $sql, 'getVoucherOverview');
        $db = null;

        $i = 0;
        foreach($dbData as $value) {
            $dbData[$i]['oxvouchernr'] = addTitle($dbData[$i]['oxvouchernr'], $dbData[$i]['oxseriedescription']);
            $dbData[$i]['oxdiscount'] = $this->oaCurrFormat($dbData[$i]['oxdiscount'], $this);
            $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
            $dbData[$i]['average'] = $this->oaCurrFormat($dbData[$i]['average'], $this);
            $i++;
        }

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    function getOpenCashOnDelivery($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        return $this->getOpenPayments( $this->PaymentCOD[$idSite], $idSite );
    }


    function getOpenPayInAdvance($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        return $this->getOpenPayments( $this->PaymentCIA[$idSite], $idSite );
    }


    function getOpenInvoices($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        return $this->getOpenPayments( $this->PaymentInvoice[$idSite], $idSite );
    }


    function getOpenPayments($paymentType, $idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $this->SiteID = Common::getRequestVar('idSite');
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();


        if (($paymentType == $this->PaymentInvoice[$this->SiteID]) || ($paymentType == $this->PaymentCOD[$this->SiteID])) {
            $sentWhere = "AND oxsenddate != '0000-00-00 00:00:00' ";
            $startDate = "oxsenddate";
        }
        else {
            $sentWhere = "";
            $startDate = "oxorderdate";
        }
        if ($this->DebugMode) 
            logfile('debug', 'getOpenPayments/'.$paymentType);

        $sql = "SELECT oxid, oxordernr, oxtotalordersum, CONCAT('<nobr>', oxbillfname, ' ', oxbilllname, '</nobr>') AS oxbillname, oxbillemail, "
                 . "CONCAT('<nobr>', oxbillzip, ' ', oxbillcity, '</nobr>') AS oxbilladdress, (TO_DAYS(NOW())-TO_DAYS($startDate)) AS days "
                 . "FROM oxorder "
                 . "WHERE oxpaymenttype = {$paymentType} "
                 . $sentWhere
                 . "AND oxshopid = {$this->ShopID[$this->SiteID]} "
                 . "AND oxpaid = '0000-00-00 00:00:00' "
                 . "AND oxstorno = 0 "
                 . "ORDER BY days ASC " 
                 . "LIMIT 0, 500 "; 
                 
        $db = openDB($this);
        $dbData = $this->oaQuery($db, $sql, 'getOpenPayments/'.$paymentType);

        $i = 0;
        foreach($dbData as $value) {
            $sql = "SELECT oxamount, oxtitle "
                    . "FROM oxorderarticles "
                    . "WHERE oxorderid = '{$dbData[$i]['oxid']}' "
                        . "AND oxstorno = 0 ";
                    
                    
            $db = openDB($this);
            $details = $this->oaQuery($db, $sql, 'getOpenPayments/'.$paymentType);

            $dbData[$i]['oxdetails'] = Piwik::translate('OxidAnalysis_Order') . ':';
            foreach ($details as $detail) {
                $dbData[$i]['oxdetails'] = $dbData[$i]['oxdetails'] . chr(13) . $detail['oxamount'] . ' x ' . $detail['oxtitle'];
            }

            $dbData[$i]['oxtotalordersum'] = $this->oaCurrFormat($dbData[$i]['oxtotalordersum'], $this);
            $dbData[$i]['oxtotalordersum'] = addTitle($dbData[$i]['oxtotalordersum'], $dbData[$i]['oxdetails']);
            $i++;
        }

        $db = null;

        $dataTable = new DataTable();
        // convert this array to a DataTable object
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    function getLiveRevenue($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $sql = "SELECT o.oxid AS oxorderid, o.oxordernr, IF(DATE(NOW())=DATE(o.oxorderdate),1,0) AS actualdate, TIME(o.oxorderdate) AS ordertime, "
                    . "CONCAT('<nobr>',DATE(o.oxorderdate),'</nobr>') AS orderdate, DAYOFMONTH(o.oxorderdate) AS orderday,"
                    . "(SELECT COUNT(*) FROM oxorder o2 WHERE o2.oxuserid=o.oxuserid AND o2.oxstorno=0) AS ordercount, "
                    . "CONCAT(o.oxbillfname, '&nbsp;', o.oxbilllname, "
                        . "IF( (SELECT COUNT(*) FROM oxorder o2 WHERE o2.oxuserid=o.oxuserid AND o2.oxstorno=0)=1, "
                            . "'', "
                            . "CONCAT('&nbsp;(', (SELECT COUNT(*) FROM oxorder o2 WHERE o2.oxuserid=o.oxuserid AND o2.oxstorno=0), ')') "
                            . ") "
                        . ") "
                        . "AS oxbillname, "
                    . "p.oxdesc AS paydesc, o.oxtotalordersum, "
                    . "CONCAT(o.oxbillstreet, '&nbsp;', o.oxbillstreetnr, CHAR(13), o.oxbillzip, '&nbsp;', o.oxbillcity) AS oxbilladdress "
                . "FROM oxorder o, oxpayments p "
                . "WHERE o.oxstorno = 0 "
                    . "AND o.oxpaymenttype = p.oxid "
                    . "AND o.oxshopid = {$this->ShopID[$idSite]} "
                . "ORDER BY o.oxordernr DESC "
                . "LIMIT 0, 50";

        $db = openDB($this);
            
        $dbData = $this->oaQuery($db, $sql, 'getLiveRevenue');

        $i = 0;
        foreach($dbData as $value) {
            $sql = "SELECT oxamount, CONCAT(oxtitle, IF(oxselvariant='','',CONCAT(', ',oxselvariant))) AS oxtitle "
                    . "FROM oxorderarticles "
                    . "WHERE oxorderid = '" . $dbData[$i]['oxorderid'] . "' "
                        . "AND oxstorno = 0" ;
            $details = $this->oaQuery($db, $sql, 'getLiveRevenue');
            $dbData[$i]['oxdetails'] = Piwik::translate('OxidAnalysis_Order') . ':';
            foreach ($details as $detail) {
                $dbData[$i]['oxdetails'] = $dbData[$i]['oxdetails'] . chr(13) . $detail['oxamount'] . ' x ' . $detail['oxtitle'];
            }
            $dbData[$i]['oxdetails'] = $dbData[$i]['oxdetails'] . chr(13) . '[' . $dbData[$i]['paydesc'] . ']';

            $dbData[$i]['oxtotalordersum'] = $this->oaCurrFormat($dbData[$i]['oxtotalordersum'], $this);
            //$dbData[$i]['ordertime'] = addTitle($dbData[$i]['ordertime'], $dbData[$i]['orderdate']);
            $dbData[$i]['oxbillname'] = addTitle($dbData[$i]['oxbillname'], $dbData[$i]['oxbilladdress']);
            $dbData[$i]['oxtotalordersum'] = addTitle($dbData[$i]['oxtotalordersum'], $dbData[$i]['oxdetails']);
            if ($dbData[$i]['actualdate'] == 0) {
                $dbData[$i]['oxbillname'] = showGray($dbData[$i]['oxbillname'], $this);
                $dbData[$i]['orderdate'] = showGray($dbData[$i]['orderdate'], $this);
                $dbData[$i]['ordertime'] = showGray($dbData[$i]['ordertime'], $this);
                $dbData[$i]['oxtotalordersum'] = showGray($dbData[$i]['oxtotalordersum'], $this);
            }
            $i++;
        }

        $db = null;

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    function getNewsRegs($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $txtDenied = '<nobr><img src="plugins/OxidAnalysis/images/NewsInfoRed.jpg" alt="'.Piwik::translate('OxidAnalysis_NewsDenied').'" />'
                    . Piwik::translate('OxidAnalysis_NewsDenied').'</nobr>';
        $txtConfirmed = '<nobr><img src="plugins/OxidAnalysis/images/NewsInfoGreen.jpg" alt="'.Piwik::translate('OxidAnalysis_NewsAllowed').'" />'
                    . Piwik::translate('OxidAnalysis_NewsAllowed').'</nobr>';
        $txtRequested = '<nobr><img src="plugins/OxidAnalysis/images/NewsInfoAmber.jpg" alt="'.Piwik::translate('OxidAnalysis_NewsNotConfirmeded').'" />'
                    . Piwik::translate('OxidAnalysis_NewsNotConfirmed').'</nobr>';

        $sql = "SELECT (TO_DAYS(NOW())-TO_DAYS(s.oxsubscribed)) AS oxdays, s.oxsubscribed, "
                . "s.oxemail, CONCAT(s.oxfname, '&nbsp;', s.oxlname) AS oxname, "
                . "CONCAT(u.oxstreet, '&nbsp;', u.oxstreetnr, CHAR(13), u.oxzip, '&nbsp;', u.oxcity) AS oxaddress, "
                . "(CASE s.oxdboptin "
                    . "WHEN 0 THEN '$txtDenied' "
                    . "WHEN 1 THEN '$txtConfirmed' "
                    . "WHEN 2 THEN '$txtRequested' END) AS oxdboptin, "
                . "(SELECT SUM(o.oxtotalordersum) "
                    . "FROM oxorder o "
                    . "WHERE o.oxuserid = u.oxid AND o.oxstorno = 0) "
                    . "AS ordersum, "
                . "(SELECT COUNT(*) "
                    . "FROM oxorder o "
                    . "WHERE o.oxuserid = u.oxid AND o.oxstorno = 0) "
                    . "AS ordercount "
                . "FROM oxnewssubscribed s, oxuser u "
                . "WHERE s.oxdboptin != 0 "
                    . "AND s.oxuserid = u.oxid "
                    . "AND u.oxshopid = {$this->ShopID[$idSite]} "
                . "ORDER BY (TO_DAYS(NOW())-TO_DAYS(s.oxsubscribed)) "
                . "LIMIT 0, 500";

        $db = openDB($this);

        $dbData = $this->oaQuery($db, $sql, 'getNewsReg');

        $db = null;

        $i = 0;
        foreach($dbData as $value) {

            $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
            if ($dbData[$i]['ordercount'] == 1)
                $orderInfo = $dbData[$i]['ordercount'] . '&nbsp;' . Piwik::translate('OxidAnalysis_Order');
            else
                $orderInfo = $dbData[$i]['ordercount'] . '&nbsp;' . Piwik::translate('OxidAnalysis_Orders');
            $dbData[$i]['ordersum'] = addTitle($dbData[$i]['ordersum'], $orderInfo);
            $dbData[$i]['ordercount'] = intFormat($dbData[$i]['ordercount'], $this);
            $i++;
        }

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;
    }


    function getBirthdayUsers($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->Currency = $site->getCurrency();

        $txtDenied = '<img src="plugins/OxidAnalysis/images/NewsInfoRed.jpg" alt="'.Piwik::translate('OxidAnalysis_NewsDenied').'" />';
        $txtConfirmed = '<img src="plugins/OxidAnalysis/images/NewsInfoGreen.jpg" alt="'.Piwik::translate('OxidAnalysis_NewsAllowed').'" />';
        $txtRequested = '<img src="plugins/OxidAnalysis/images/NewsInfoAmber.jpg" alt="'.Piwik::translate('OxidAnalysis_NewsNotConfirmed').'" />';

        $sql = "SELECT CONCAT("
                        . "(CASE s.oxdboptin "
                            . "WHEN 0 THEN '$txtDenied' "
                            . "WHEN 1 THEN '$txtConfirmed' "
                            . "WHEN 2 THEN '$txtRequested' END), "
                        . "u.oxfname, '&nbsp;', u.oxlname) AS username, "
                    . "u.oxusername, u.oxbirthdate, YEAR(CURDATE())-YEAR(u.oxbirthdate) AS userage, "
                    . "(SELECT COUNT(*) FROM oxorder o2 WHERE o2.oxuserid=u.oxid) AS ordercount, "
                    . "SUM(o.oxtotalordersum) AS ordersum "
                 . "FROM oxuser u "
                    . "LEFT JOIN oxnewssubscribed s ON s.oxuserid = u.oxid "
                    . "LEFT JOIN oxorder o ON o.oxuserid = u.oxid "
                 . "WHERE MONTH(u.oxbirthdate) = MONTH(CURDATE()) "
                    . "AND DAY(u.oxbirthdate) = DAY(CURDATE()) "
                    . "AND u.oxshopid = {$this->ShopID[$idSite]} "
                 . "GROUP BY u.oxid "
                 . "LIMIT 0, 500";
        if ($this->DebugMode) logfile('debug', 'getBirthdayUsers()');
        if ($this->DebugMode) logfile('debug', $sql);

        $db = openDB($this);
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', $sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll();
            $db = null;
        }
        catch (PDOException $e) {
            logfile( 'error', 'getCIAnotPaid: pdo->execute error = '.$e->getMessage() );
            die();
        }

        if ($this->DebugMode) logfile('debug', $dbData);
        $i = 0;
        foreach($dbData as $value) {
            $dbData[$i]['ordersum'] = $this->oaCurrFormat($dbData[$i]['ordersum'], $this);
            $i++;
        }

        $dataTable = new DataTable();
        // convert this array to a DataTable object
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;  
    }


    function getRatingsAndReviews($idSite)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';

        $sql1 = "SELECT TO_DAYS(NOW())-TO_DAYS(r.oxcreate) AS oxdays, r.oxcreate AS oxcreate, "
                . "IF(a.oxparentid = '', "
                    . "a.oxtitle, "
                    . "(SELECT a2.oxtitle FROM oxarticles a2 WHERE a2.oxid = a.oxparentid)) "
                    . "AS oxtitle, "
                . "a.oxartnum, a.oxprice, "
                . "CONCAT(SUBSTRING(r.oxtext, 1, 80), IF(CHAR_LENGTH(r.oxtext)>80,'...','')) AS shorttext, "
                . "r.oxtext AS oxtext, CONCAT('<img src=\"plugins/OxidAnalysis/images/rateds', r.oxrating, '.png\">') AS oxrating, "
                . "CONCAT(u.oxfname, '&nbsp;', u.oxlname, CHAR(13), u.oxstreet, '&nbsp;', u.oxstreetnr, CHAR(13), u.oxzip, 'nbsp;', u.oxcity) AS name, u.oxusername AS oxemail "
                //. "u.oxlname, u.oxusername AS oxemail, u.oxstreet, u.oxstreetnr, u.oxzip, u.oxcity "
              . "FROM oxreviews r, oxuser u, oxarticles a "
              . "WHERE r.oxuserid = u.oxid "
                . "AND a.oxid = r.oxobjectid "
                . "AND u.oxshopid = {$this->ShopID[$idSite]} "
              . "ORDER BY (TO_DAYS(NOW())-TO_DAYS(r.oxcreate)) "
              . "LIMIT 0, 500 ";


        $db = openDB($this);

        $dbData1 = $this->oaQuery($db, $sql1, 'getRatingsAndReviews');

        $sql2 = "SELECT TO_DAYS(NOW())-TO_DAYS(r.oxtimestamp) AS oxdays, r.oxtimestamp AS oxcreate, "
                . "IF(a.oxparentid = '', "
                    . "a.oxtitle, "
                    . "(SELECT a2.oxtitle FROM oxarticles a2 WHERE a2.oxid = a.oxparentid)) "
                    . "AS oxtitle, "
                . "a.oxartnum, a.oxprice, "
                . " '-' AS shorttext, '' AS oxtext, CONCAT('<img src=\"plugins/OxidAnalysis/images/rateds', r.oxrating, '.png\">') AS oxrating, "
                . "CONCAT(u.oxfname, '&nbsp;', u.oxlname, CHAR(13), u.oxstreet, '&nbsp;', u.oxstreetnr, CHAR(13), u.oxzip, 'nbsp;', u.oxcity) AS name, u.oxusername AS oxemail "
              . "FROM oxratings r, oxuser u, oxarticles a "
              . "WHERE r.oxuserid = u.oxid "
                . "AND a.oxid = r.oxobjectid "
                . "AND (SELECT COUNT(*) "
                    . "FROM oxreviews r1 "
                    . "WHERE r1.oxcreate = r.oxtimestamp "
                        . "AND r1.oxuserid = r.oxuserid ) = 0 "
              . "ORDER BY (TO_DAYS(NOW())-TO_DAYS(r.oxtimestamp)) "
              . "LIMIT 0, 500 ";
            
        $dbData2 = $this->oaQuery($db, $sql2, 'getRatingsAndReviews');

        $db = null;

        foreach($dbData2 as $value) {
            array_push($dbData1, $value);
        }
        if ($this->DebugMode) logfile('debug', $dbData1);

        $i = 0;
        foreach($dbData1 as $value) {
            $dbData1[$i]['shorttext'] = '<div style="display:block;">'.addTitle($dbData1[$i]['shorttext'], $dbData1[$i]['oxtext']).'</div>';
            $dbData1[$i]['oxrating'] = addTitle($dbData1[$i]['oxrating'], $dbData1[$i]['name']);
            $i++;
        }

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData1);

        return $dataTable;  
    }


    function getPayTypeSums($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $selectedYear = date("Y", strtotime($date));
        $sql = "SELECT sum(oxtotalordersum) AS totalsum "
                . "FROM oxorder "
                . "WHERE YEAR(oxorderdate)=$selectedYear AND oxstorno=0 "
                . "AND oxshopid = {$this->ShopID[$idSite]} "
                . "GROUP BY YEAR(oxorderdate) ";

        $db = openDB($this);

        $dbData = $this->oaQuery($db, $sql, 'getPayTypeSums');
        if (count($dbData) !=0 )
            $totalSum = $dbData[0]['totalsum'];
        else
            $totalSum = 0.0;

        $sql = "SELECT p.oxdesc, COUNT(*) AS ordercount, SUM(o.oxtotalordersum) AS totalordersum, (SUM(o.oxtotalordersum)/$totalSum*100.0) AS percentage "
                 . "FROM oxorder o, oxpayments p " 
                 . "WHERE p.oxid=o.oxpaymenttype AND p.oxactive=1 AND YEAR(o.oxorderdate)=$selectedYear AND o.oxstorno=0 "
                 . "AND o.oxshopid = {$this->ShopID[$idSite]} "
                 . "GROUP BY p.oxdesc "
                 . "ORDER BY totalordersum DESC "; 
        $dbData = $this->oaQuery($db, $sql, 'getPayTypeSums');

        $db = null;
        
        $i = 0;
        foreach($dbData as $value) {
            $dbData[$i]['totalordersum'] = $this->oaCurrFormat($dbData[$i]['totalordersum'], $this);
            $dbData[$i]['percentage'] = percFormat($dbData[$i]['percentage'], $this);
            $i++;
        }

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;  
    }
    
    
    
    /*
     * Retrieve data for widgetOpenPaytypeSums
     */
    function getOpenPaytypeSums($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $selectedYear = date("Y", strtotime($date));
        
        $db = openDB($this);
        $dbData = array();
		
        // select open CIA
        $sql = "SELECT count(*) AS totalcount, sum(oxtotalordersum) AS totalsum "
             . "FROM oxorder "
             . "WHERE oxpaymenttype = {$this->PaymentCIA[$idSite]} "
                . "AND oxshopid = {$this->ShopID[$idSite]} "
                . "AND oxpaid = '0000-00-00 00:00:00' "
                . "AND oxstorno = 0 "; 
        $data = $this->oaQuery($db, $sql, 'getOpenPaytypeSums');
        array_push($dbData, array(
                'paytpe' => Piwik::translate('OxidAnalysis_CIA'),
                'count'  => $data[0]['totalcount'],
                'open'   => $this->oaCurrFormat( (0.0 - $data[0]['totalsum']), $this )
            ));
		
        // select open COD
        $sql = "SELECT count(*) AS totalcount, sum(oxtotalordersum) AS totalsum "
             . "FROM oxorder "
             . "WHERE oxpaymenttype = {$this->PaymentCOD[$idSite]} "
                . "AND oxshopid = {$this->ShopID[$idSite]} "
                . "AND oxpaid = '0000-00-00 00:00:00' "
                . "AND oxsenddate != '0000-00-00 00:00:00' "
                . "AND oxstorno = 0 "; 
        $data = $this->oaQuery($db, $sql, 'getOpenPaytypeSums');
        array_push($dbData, array(
                'paytpe' => Piwik::translate('OxidAnalysis_COD'),
                'count'  => $data[0]['totalcount'],
                'open'   => $this->oaCurrFormat( (0.0 - $data[0]['totalsum']), $this )
            ));
			
        // select Invoices
        $sql = "SELECT count(*) AS totalcount, sum(oxtotalordersum) AS totalsum "
             . "FROM oxorder "
             . "WHERE oxpaymenttype IN ({$this->PaymentInvoice[$idSite]}) "
                . "AND oxshopid = {$this->ShopID[$idSite]} "
                . "AND oxpaid = '0000-00-00 00:00:00' "
                . "AND oxsenddate != '0000-00-00 00:00:00' "
                . "AND oxstorno = 0 "; 
        $data = $this->oaQuery($db, $sql, 'getOpenPaytypeSums');
        array_push($dbData, array(
                'paytpe' => Piwik::translate('OxidAnalysis_Invoice'),
                'count'  => $data[0]['totalcount'],
                'open'   => $this->oaCurrFormat( (0.0 - $data[0]['totalsum']), $this )
            ));
			
        // select PrePaid received
        $sql = "SELECT count(*) AS totalcount, sum(oxtotalordersum) AS totalsum "
             . "FROM oxorder "
             . "WHERE oxpaid != '0000-00-00 00:00:00' "
                . "AND oxsenddate = '0000-00-00 00:00:00' "
                . "AND oxshopid = {$this->ShopID[$idSite]} "
                . "AND oxstorno = 0 "; 
        $data = $this->oaQuery($db, $sql, 'getOpenPaytypeSums');
        array_push($dbData, array(
                'paytpe' => Piwik::translate('OxidAnalysis_PaidInAdvance'),
                'count'  => $data[0]['totalcount'],
                'open'   => $this->oaCurrFormat( (0.0 - $data[0]['totalsum']), $this )
            ));
			
        $db = null;
        
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;  
    }
    
    
    
    /*
     * Retrieve data for widgetOpenPaytypeSums
     */
    function getUserAge($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();


        $ageClasses = explode('|', $this->AgeClasses[$idSite]);
        $data = array();

        $sql = array();
        $total = array();
        $andWhere = 'AND YEAR(u.oxbirthdate) != 0 '
                  . 'AND u.oxshopid = ' . $this->ShopID[$idSite] . ' '; 

        array_push($sql, "SELECT COUNT(*) AS total FROM oxuser u " 
                        . "WHERE u.oxsal = 'MR' ".$andWhere );
        array_push($sql, "SELECT COUNT(*) AS total FROM oxuser u " 
                        . "WHERE u.oxsal = 'MRS' ".$andWhere );
        array_push($sql, "SELECT SUM(o.oxtotalordersum) AS total FROM oxuser u, oxorder o "
                        . "WHERE  u.oxid = o.oxuserid AND u.oxsal = 'MR' ".$andWhere );
        array_push($sql, "SELECT SUM(o.oxtotalordersum) AS total FROM oxuser u, oxorder o "
                        . "WHERE  u.oxid = o.oxuserid AND u.oxsal = 'MRS' ".$andWhere ) ;
        
        $db = openDB($this);

        foreach ($sql as $stmt) {
            $data = $this->oaQuery($db, $stmt, 'getUserAge');
            if (empty($dbData[0]['total'])) 
                $dbData[0]['total'] = '1.0';
            array_push($total, $dbData[0]['total']);
            if ($this->DebugMode) logfile('debug', $dbData);
        }
						
        foreach ($ageClasses as $ageClass) {
            $sql = array();
            $andWhere = "AND (YEAR(curdate())-YEAR(u.oxbirthdate)) BETWEEN " . str_replace('-', ' AND ', $ageClass) . ' '
                      . "AND u.oxshopid = {$this->ShopID[$idSite]} "; 

            array_push($sql,  "SELECT COUNT(*) AS totalsum, COUNT(*)/{$total[0]}*100.0 AS percent " 
                            . "FROM oxuser u " 
                            . "WHERE oxsal = 'MR' " 
                            . $andWhere );
            array_push($sql,  "SELECT COUNT(*) AS totalsum, COUNT(*)/{$total[1]}*100.0 AS percent " 
                            . "FROM oxuser u " 
                            . "WHERE oxsal = 'MRS' " 
                            . $andWhere );
            array_push($sql,  "SELECT SUM(o.oxtotalordersum) AS totalsum, SUM(o.oxtotalordersum)/{$total[2]}*100.0 AS percent "
                            . "FROM oxuser u, oxorder o "
                            . "WHERE  u.oxid = o.oxuserid "
                            . "AND oxsal = 'MR' " 
                            . $andWhere );
            array_push($sql,  "SELECT SUM(o.oxtotalordersum) AS totalsum, SUM(o.oxtotalordersum)/{$total[3]}*100.0 AS percent "  
                            . "FROM oxuser u, oxorder o "
                            . "WHERE  u.oxid = o.oxuserid "
                            . "AND oxsal = 'MRS' " 
                            . $andWhere );
							
            $percentage = array();
            foreach ($sql as $stmt) {
                $data = $this->oaQuery($db, $stmt, 'getUserAge');
                array_push($percentage, $data[0]['percent']);
            }
							
            array_push($data, array(
                    'ageclass' => str_replace('-', '&nbsp;-&nbsp;', $ageClass), 
                    'malecount' => $percentage[0], 
                    'femalecount' => $percentage[1], 
                    'malerevenue' => $percentage[2], 
                    'femalerevenue' => $percentage[3]));
			
        }

        $db = null;
        
        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($data);

        return $dataTable;  
    }
    
    
    
    function getLogisticsData($idSite, $period, $date)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();

        $oPeriod = new Range($period, 'last30');
        $dateThisStart = $oPeriod->getDateStart()->toString('Y-m-d');
        $dateThisEnd = $oPeriod->getDateEnd()->toString('Y-m-d');
        
        $dayDiff = (int) ((strtotime($dateThisEnd) - strtotime($dateThisStart)) / 86400 +1);
        $datePrevStart = date("Y-m-d",strtotime($dateThisStart)-3600*24*$dayDiff);
        $datePrevEnd = date("Y-m-d",strtotime($dateThisEnd)-3600*24*$dayDiff);

        $whereThis = "DATE(oxorderdate) >= '$dateThisStart' AND DATE(oxorderdate) <= '$dateThisEnd' ";
        $wherePrev = "DATE(oxorderdate) >= '$datePrevStart' AND DATE(oxorderdate) <= '$datePrevEnd' ";

        $selSql = "SELECT COUNT(*) AS totalnum "
                 . "FROM oxorder "
                 . "WHERE oxstorno = 0 "
                    . "AND oxtotalordersum != 0.0 "
                    . 'AND oxshopid = ' . $this->ShopID[$idSite] . ' '
                    . "AND ";

        $db = openDB($this);
        $dbData = array();

        $sql = $selSql . $whereThis;
        $data = $this->oaQuery($db, $sql, 'getLogisticsData');
        $dbData['this']['ordered'] = $data[0]['totalnum'];

        $sql = str_replace('oxorderdate', 'oxsenddate', $sql);
        $data = $this->oaQuery($db, $sql, 'getLogisticsData');
        $dbData['this']['sent'] = $data[0]['totalnum'];
            
        $sql = $selSql . $wherePrev;
        $data = $this->oaQuery($db, $sql, 'getLogisticsData');
        $dbData['prev']['ordered'] = $data[0]['totalnum'];

        $sql = str_replace('oxorderdate', 'oxsenddate', $sql);
        $data = $this->oaQuery($db, $sql, 'getLogisticsData');
        $dbData['prev']['sent'] = $data[0]['totalnum'];

        $sql = "SELECT COUNT(*) AS countready, SUM(oxtotalordersum) AS sumready "
            . "FROM oxorder "
            . "WHERE "
                . "((oxpaid != '0000-00-00 00:00:00') "
                    ."OR (oxpaymenttype IN ('oxidinvoice',".$this->PaymentLater[$idSite]."))) "
                . 'AND oxshopid = ' . $this->ShopID[$idSite] . ' '
                . "AND oxsenddate = '0000-00-00 00:00:00' "
                . "AND oxstorno = 0 ";
        $sql = $sql . ' AND ' . $whereThis;

        $data = $this->oaQuery($db, $sql, 'getLogisticsData');
        $dbData['open']['count'] = $data[0]['countready'];
        $dbData['open']['sum'] = $this->oaCurrFormat($data[0]['sumready'],$this,false);

        $db = null;

        return $dbData;

    }


    function getReturningCustomers($idSite, $period, $date, $segment = false)
    {
        include PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/conf/'.'config.inc.php';
        $site = new Site($idSite);
        $this->SiteID = $idSite;
        $this->Currency = $site->getCurrency();
        
        $dateStart = $this->oaGetStartDate($date,$period);
        $dateEnd = $this->oaGetEndDate($date,$period);

        $db = openDB($this);

        $sql = "SELECT ordercount, COUNT(ordercount) AS customercount FROM "
                . "(SELECT oxbillemail, COUNT(oxbillemail) AS ordercount "
                    . "FROM oxorder "
                    . "WHERE oxstorno=0 "
                        . "AND oxshopid = {$this->ShopID[$idSite]} "
                        . "AND DATE(oxorderdate) >= '{$dateStart}' "
                        . "AND DATE(oxorderdate)  <= '{$dateEnd}' "
                    . "GROUP BY oxbillemail) AS c "
                . "GROUP BY ordercount ORDER BY ordercount ASC "; 
        $dbData = $this->oaQuery($db, $sql, 'getReturningCustomers');
        if ($this->DebugMode) logfile('debug', $dbData);
        $db = null;
        
        /*$i = 0;
        foreach($dbData as $value) {
            $dbData[$i]['totalordersum'] = $this->oaCurrFormat($dbData[$i]['totalordersum'], $this);
            $dbData[$i]['percentage'] = percFormat($dbData[$i]['percentage'], $this);
            $i++;
        }*/

        $dataTable = new DataTable();
        $dataTable = DataTable::makeFromIndexedArray($dbData);

        return $dataTable;  
    }
    
    
    
    function oaGetStartDate($date, $period)
    {
        if ($period == "range") {
            return substr($date, 0, strpos($date, ',')); 
        } 
        else {
            $piwikDate = Date::factory($date);
            $date = Period\Factory::build($period, $piwikDate);
            return $date->getDateStart()->toString();
        }

        return;
    }
    
    
    
    function oaGetEndDate($date, $period)
    {
        if ($period == "range") {
            return substr($date, strpos($date, ',')+1);
        } 
        else {
            $piwikDate = Date::factory($date);
            $date = Period\Factory::build($period, $piwikDate);
            return $date->getDateEnd()->toString();
        }

        return ;
    }
    
    
    function oaQuery($db, $sql, $func) 
    {
        if ($this->DebugMode) {
            logfile('debug', $func . ': ' . $sql);
        }
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->errorCode() != '00000') {
                logfile('error', $func . ': ' . $sql );
                logfile('error', $stmt->errorInfo() );
            }
            $dbData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (PDOException $e) {
            logfile('error', $func.': pdo->execute error = '.$e->getMessage() );
            die();
        }
        return $dbData;
    }
    
    
    
    function oaTrendTip ($valueTrend, $valueForecast, $conf)
    {
        return Piwik::translate('OxidAnalysis_Trend')    . sprintf(": %+d %% \n", $valueTrend)
             . Piwik::translate('OxidAnalysis_Forecast') . sprintf(": %+1.2F %s", $valueForecast, MetricsFormatter::getCurrencySymbol($conf->SiteID));
        
    }
    
    
    function oaTrendIcon ($valueForecast, $valuePrevious)
    {
        $icoPath = "plugins/OxidAnalysis/images";
        $icoUp    = '&nbsp;<img src="'.$icoPath.'/up.png" />';
        $icoDown  = '&nbsp;<img src="'.$icoPath.'/down.png" />';
        if ($valueForecast > $valuePrevious)
            return $icoUp;
        else
            return $icoDown;
    }
    
    
    function oaCurrFormat($value, $conf, $align=True)
    {
        //$locale = explode(",",str_replace("-","_",$_SERVER['HTTP_ACCEPT_LANGUAGE']));
        if ($align)
            return ( alignRight( number_format($value, 2, $conf->DecimalPoint, $conf->ThousandsSep).' '.MetricsFormatter::getCurrencySymbol($conf->SiteID) ) );
        else
            return ( number_format($value, 2, $conf->DecimalPoint, $conf->ThousandsSep).' '.MetricsFormatter::getCurrencySymbol($conf->SiteID) );
    }
	
}
<?php 

function openDB($conf) {
	
    switch ($conf->DatabaseType) {
        case 'mysql':
            if ($conf->DebugMode) logfile('debug', 'openDB(): SiteID='.$conf->SiteID);
            if ($conf->SiteID != 0) 
                $iSite = $conf->SiteID;
            else
                $iSite = 1;

            $dbConn = 'mysql:host='.$conf->DatabaseHost[$iSite].';port='.$conf->DatabasePort[$iSite].';dbname='.$conf->DatabaseName[$iSite];
            $dbUser = $conf->DatabaseUser[$iSite];
            $dbPass = $conf->DatabasePass[$iSite];

            $dbh = new PDO($dbConn, $dbUser, $dbPass); 
            $dbh->exec('set names "utf8"');
            $dbh->exec('SET SESSION group_concat_max_len = 4096');
            break;

        case 'sqlite':
            $dbh = new PDO('sqlite:'.$conf->DatabaseName);
            break;

        default:
            logfile('error', 'Missing DatabaseType in config.php');
            break;

    }

    if (!empty($dbh)) 
        return $dbh;
    else
        return 0;

}


function oxFeedbackInstalled ($conf){
	
    $db = openDB($conf);

    $sql = 'SHOW FIELDS FROM oxorder WHERE UPPER(field) = \'OXORDERSURVEY\' ';
    $result = $db->query($sql);
    $data = $result->fetch(PDO::FETCH_ASSOC);
    $db = NULL;

    if( empty($data['Field']) )
        return FALSE;
    else
        return TRUE;		
	
}


function dayofWeek($conf) {
	
    if ($conf->FirstDayOfWeek == 'Monday')
        if (date("w") == 0)			// sunday
            return 7;
        else
            return date("w");
    else
        return (date("w")+1);
}

	
// Add zero values to array
function addZeroValues ($dateStart, $dateEnd, $periodType, $fieldName, $dbData)
{
        switch ($periodType) {

                case 'day':
                case 'range':
                        $dateActual = $dateStart;
                        while ( $dateActual <= $dateEnd ) {
                                if ( checkExistsInArray($dateActual, $dbData) == FALSE )
                                        array_push($dbData, array('label' => $dateActual, $fieldName => 0.0));
                                $dateActual = date("Y-m-d", strtotime('+1 day', strtotime($dateActual)));
                        }
                        break;

                case 'week':
                        $dateActual = $dateStart;
                        while ( $dateActual <= $dateEnd ) {
                                $weekActual = date("Y-W", strtotime($dateActual));
                                if ( checkExistsInArray($weekActual, $dbData) == FALSE )
                                        array_push($dbData, array('label' => $weekActual, $fieldName => 0.0));
                                $dateActual = date("Y-m-d", strtotime('+1 week', strtotime($dateActual)));
                        }
                        break;

                case 'month':
                        $dateActual = $dateStart;
                        while ( $dateActual <= $dateEnd ) {
                                $monthActual = date("Y-m", strtotime($dateActual));
                                if ( checkExistsInArray($monthActual, $dbData) == FALSE )
                                        array_push($dbData, array('label' => $monthActual, $fieldName => 0.0));
                                $dateActual = date("Y-m-d", strtotime('+1 month', strtotime($dateActual)));
                        }
                        break;

                case 'year':
                        $dateActual = $dateStart;
                        while ( $dateActual <= $dateEnd ) {
                                $yearActual = date("Y", strtotime($dateActual));
                                if ( checkExistsInArray($yearActual, $dbData) == FALSE )
                                        array_push($dbData, array('label' => $yearActual, $fieldName => 0.0));
                                $dateActual = date("Y-m-d", strtotime('+1 year', strtotime($dateActual)));
                        }
                        break;

        }
        
        // date value exists, but not the field
        foreach ($dbData as $key => $dbDataSet) {
            if ( !isset($dbDataSet[$fieldName]) )
                $dbData[$key][$fieldName] = 0.0;
        }

        for ($i=0; $i <count($dbData); $i++)
                for ($j=0; $j<count($dbData); $j++)
                        if ( $dbData[$i]['label'] <= $dbData[$j]['label'] ) {
                                $tmpLabel = $dbData[$i];
                                $dbData[$i] = $dbData[$j];
                                $dbData[$j] = $tmpLabel;
                        }

        return $dbData;

}


// Check for existence
function checkExistsInArray($needle, $dbData)
{
        $found = FALSE;
        foreach ($dbData as $subArray) {
                if ( in_array($needle,$subArray) == TRUE ) {
                        $found = TRUE;
                        break;
                }
        }
        return $found;
}


// Check for existence
function getOxPayments($conf)
{
        $db = openDB($conf);
        //$sql = 'SELECT oxdesc FROM oxpayments WHERE oxactive=1 ORDER BY oxdesc ASC ';
        if ($conf->ActivePaymentsOnly)
                $sql = 'SELECT DISTINCT p.oxdesc FROM oxpayments p, oxorder o WHERE p.oxid=o.oxpaymenttype AND p.OXACTIVE=1 ORDER BY p.oxdesc ASC';
        else
                $sql = 'SELECT DISTINCT p.oxdesc FROM oxpayments p, oxorder o WHERE p.oxid=o.oxpaymenttype ORDER BY p.oxdesc ASC';
        $result = $db->query($sql);
        $data = $result->fetchAll(PDO::FETCH_COLUMN);
        $db = NULL;

        return $data;
}


// Print value to logfile
function logfile($file, $value)
{
    $fh = fopen(PIWIK_INCLUDE_PATH . '/plugins/OxidAnalysis/logs/'.$file.'.log', 'a+');
    fputs ($fh, date("Y-m-d H:i:s  "));
    if (gettype($value) == 'array' OR gettype($value) == 'object')
        fputs ($fh, print_r($value, TRUE));
    else
        fputs ($fh, $value."\n");
    fclose($fh);
    return;
}


// Format Currency Values
function currFormat($value, $conf)
{
        if (empty($conf->Style)) $conf->Style = '';
        return '<div style="text-align:right;'.$conf->Style.'">' 
                . number_format($value, 2, $conf->DecimalSeparator, '') 
                . '&nbsp;' . htmlentities($conf->Currency, ENT_QUOTES, "UTF-8") . '&nbsp;'
                . '</div>';
}


// Format Integer Values
function intFormat($value, $conf)
{
        if (empty($conf->Style)) $conf->Style = '';
        return '<div style="text-align:right;'.$conf->Style.'">' 
                . $value 
                . '&nbsp;'
                . '</div>';
}


// Format Percentage Values
function percFormat($value, $conf)
{
        if (empty($conf->Style)) $conf->Style = '';
        return '<div style="text-align:right;'.$conf->Style.'">' 
                . number_format($value, 1, $conf->DecimalSeparator, '') 
                . '&nbsp;' . '%' . '&nbsp;' 
                . '</div>';
}


// Adds a title tooltip to the string
function addTitle($value, $title)
{
    // if (empty($conf->Style)) $conf->Style = '';
    return '<span title="'.$title.'">' 
            . $value 
            . '</span>';
}


// Adds a title tooltip to the string
function showGray($value, $conf)
{
    if (empty($conf->StyleGrayText)) $conf->StyleGrayText = 'color:#aaa;';
    return '<div style="'.$conf->StyleGrayText.'">' 
            . $value 
            . '</div>';
}


// Align the string in the cell to the right
function alignRight($value)
{
    return '<div style="text-align:right;">' 
            . $value 
            . '</div>';
}

?>
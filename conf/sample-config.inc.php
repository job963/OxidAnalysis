<?

$this->TablePrefix = "";
$this->DebugMode = FALSE; 

$this->DatabaseType = "mysql";

$this->EnableActions = FALSE;
$this->FirstDayOfWeek = "Monday";

$this->ActivePaymentsOnly = FALSE;

$this->DecimalSeparator = ",";
$this->MaxBarChartColumns = 15;
$this->SiteID = 0;

/* eShop specific block begins here */
$this->DatabaseHost[1] = "localhost";
$this->DatabasePort[1] = "3306";
$this->DatabaseName[1] = "oxid";
$this->DatabaseUser[1] = "root";
$this->DatabasePass[1] = "root";

$this->ShopID[1] = "'oxbaseshop'";
// for EE use: $this->ShopID[1] = "1";

$this->EnableMenuReadyToSend[1] = True;
$this->EnableMenuRevenue[1] = True;
$this->EnableMenuTimeRevenue[1] = True;
$this->EnableMenuCIAnotPaid[1] = True;
$this->EnableMenuCODnotReceived[1] = True;
$this->EnableMenuInvoiceNotPaid[1] = True;
$this->EnableMenuPaidInAdvance[1] = True;
$this->EnableMenuTopSeller[1] = True;
$this->EnableMenuTopCancels[1] = True;
$this->EnableMenuAgeAnalysis[1] = True;
$this->EnableMenuStoreStatus[1] = True;
$this->EnableMenuManufacturerRevenue[1] = True;
$this->EnableMenuVendorRevenue[1] = True;

$this->PaymentCIA[1] = "'oxidpayadvance'";
$this->PaymentCOD[1] = "'oxidcashondel'";
$this->PaymentInvoice[1] = "'oxidinvoice'";
$this->PaymentLater[1] = "'oxidinvoice','oxidcashondel','9ef547fa3b118077e98651b6dd3fe5f1'";
$this->PaymentPrepaid[1] = "";

$this->CheckInventory[1] = True;
$this->FeedbackMaxDays[1] = 30;
$this->ShowOnlySendCOD[1] = True;
$this->AgeClasses[1] = "0-17|18-25|26-40|41-60|61-99";
$this->CarrierTrackingUrl[1] = "http://tracking.hlg.de/Tracking.jsp?TrackID=";
$this->IgnoreRemark[1] = "Hier k%nnen Sie uns noch etwas mitteilen.";
$this->LogisticsRange[1] = "last30";

/* repeat here the block above for the next eShop */

?>
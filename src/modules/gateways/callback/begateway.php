<?php
require "../../../init.php";
require_once(__DIR__ . '/../begateway/lib/lib/beGateway.php');

$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("begateway");

if (!$GATEWAY['type']) {
    $GATEWAY = getGatewayVariables("begateway");
}

if (!$GATEWAY['type']) {
    exit("Module Not Activated");
}

$webhook = new \beGateway\Webhook;
\beGateway\Settings::$shopId = $GATEWAY['shop_id'];
\beGateway\Settings::$shopKey = $GATEWAY['shop_key'];

$logs = print_r($webhook->getResponse(), true);

if ($webhook->isAuthorized()) {
  list($invoiceid, $customerid) = explode('|',$webhook->getTrackingId());

  $invoiceid = checkCbInvoiceID($invoiceid, "begateway");

  if ($invoiceid && $webhook->isSuccess()) {
      addInvoicePayment($invoiceid, $webhook->getUid(), "", "", "begateway");
      if ($webhook->isTest()) {
        begateway_add_note(array('invoiceid' => $invoiceid, 'transid' => $webhook->getUid()));
      }
      logTransaction("beGateway", $logs, "Successful");
      if ($webhook->getResponse()->transaction->credit_card->token) {
        $cc = $webhook->getResponse()->transaction->credit_card;
        update_query("tblclients", array("cardtype" => $cc->brand, "gatewayid" => $cc->token, "cardlastfour" => $cc->last_4), array("id" => $customerid));
      }

      header("location:../../../viewinvoice.php?id=" . $invoiceid . "&paymentsuccess=true");
      die('OK');
  }
  logTransaction("beGateway", $logs, "Error");
}
header("location:../../../viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true");
die('ERROR');

<?php
/*
 * Copyright (C) 2017 beGateway
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      beGateway
 * @copyright   2017 beGateway
 * @version     1.0.0
 * @license     https://opensource.org/licenses/MIT The MIT License
 */
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

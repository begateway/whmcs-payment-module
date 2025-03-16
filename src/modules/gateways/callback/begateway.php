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
 * @copyright   2020 beGateway
 * @version     2.5.0
 * @license     https://opensource.org/licenses/MIT The MIT License
 */
require "../../../init.php";
require_once(__DIR__ . '/../begateway/vendor/autoload.php');

$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$gatewayModuleName = basename(__FILE__, '.php');

$GATEWAY = getGatewayVariables($gatewayModuleName);

if (!$GATEWAY['type']) {
    die("Module Not Activated");
}

$webhook = new \BeGateway\Webhook;
\BeGateway\Settings::$shopId = $GATEWAY['shop_id'];
\BeGateway\Settings::$shopKey = $GATEWAY['shop_key'];

$logs = json_encode($webhook->getResponseArray());

logTransaction($GATEWAY['name'], $logs, $webhook->getStatus());

if ($webhook->isAuthorized()) {
  list($invoiceid, $customerid) = explode('|',$webhook->getTrackingId());
  $uid = $webhook->getUid();

  # transaction has been made not via whmcs
  if (!isset($customerid)) {
    $invoiceid = null;
  }

  if (!isset($invoiceid) && isset($webhook->getResponse()->transaction->erip)) {
    # try to get it from ERIP data
    $invoiceid = $webhook->getResponse()->transaction->erip->account_number;
    $invoiceid = preg_replace("/[^0-9]/", "", $invoiceid);
    $invoiceid = intval($invoiceid);
  }

  $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY['name']);

  # it halts script if trx exists and response body is empty

  checkCbTransID($uid);

  if ($invoiceid && $webhook->isSuccess()) {
      $money = new \BeGateway\Money;
      $money->setCurrency($webhook->getResponse()->transaction->currency);
      $money->setCents($webhook->getResponse()->transaction->amount);

      addInvoicePayment($invoiceid, $uid, $money->getAmount(), null, $gatewayModuleName);

      if ($webhook->getResponse()->transaction->credit_card->token) {
        $cc = $webhook->getResponse()->transaction->credit_card;
        update_query("tblclients", array("cardtype" => $cc->brand, "gatewayid" => $cc->token, "cardlastfour" => $cc->last_4), array("id" => $customerid));
      }
      
      die('OK');
  }
} else {
  die('Not authorized');
}
die('ERROR');

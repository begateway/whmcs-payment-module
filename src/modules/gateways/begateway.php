<?php
/*
 * Copyright (C) 2025 beGateway
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
 * @copyright   2025 beGateway
 * @version     2.6.0
 * @license     https://opensource.org/licenses/MIT The MIT License
 */
if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

require_once(__DIR__ . '/begateway/vendor/autoload.php');

function begateway_MetaData()
{
    return array(
        'DisplayName' => 'beGateway Gateway Module',
        'APIVersion' => '1.1', // Use API Version 1.1
    );
}

function begateway_Version()
{
    return '2.6.0';
}

function begateway_config() {
    global $whmcs;

    $currentUser = new \WHMCS\Authentication\CurrentUser;
    $admin = $currentUser->admin();

    $language = ($admin) ? $admin->language : $whmcs->get_config('Language');
    
    if (empty($language))
        $language = 'english';

    if (!file_exists(__DIR__ . '/begateway/lang/' . $language . '.php'))
        $language = 'english';

    /* @var array $_GATEWAYLANG */
    require __DIR__ . '/begateway/lang/' . $language . '.php';

    $days = array();
    for($i=1;$i<31;$i++) {
      $days["$i"] = "$i";
    }

    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value" => "beGateway"),
        "shop_id" => array("FriendlyName" => $_GATEWAYLANG['shop_id'], "Type" => "text", "Size" => "25", "Description" => $_GATEWAYLANG['shop_id_desc']),
        "shop_key" => array("FriendlyName" => $_GATEWAYLANG['shop_key'], "Type" => "text", "Size" => "50", "Description" => $_GATEWAYLANG['shop_key_desc']),
        "domain_gateway" => array("FriendlyName" => $_GATEWAYLANG['domain_gateway'], "Type" => "text", "Size" => "25", "Description" => $_GATEWAYLANG['domain_gateway_desc']),
        "domain_checkout" => array("FriendlyName" => $_GATEWAYLANG['domain_checkout'], "Type" => "text", "Size" => "25", "Description" => $_GATEWAYLANG['domain_checkout_desc']),
        "payment_valid" => array("FriendlyName" => $_GATEWAYLANG['payment_valid'], "Type" => "text", "Size" => "25", "Description" => $_GATEWAYLANG['payment_valid_desc']),
        "card_enable" => array("FriendlyName" => $_GATEWAYLANG['card_enable'], "Type" => "yesno", "Description" => $_GATEWAYLANG['card_enable_desc']),
        "erip_enable" => array("FriendlyName" => $_GATEWAYLANG['erip_enable'], "Type" => "yesno", "Description" => $_GATEWAYLANG['erip_enable_desc']),
        "erip_service_no" => array("FriendlyName" => $_GATEWAYLANG['erip_service_no'], "Type" => "text", "Size" => "25", "Description" => $_GATEWAYLANG['erip_service_no_desc']),
        "test_mode" => array("FriendlyName" => $_GATEWAYLANG['test_mode'], "Type" => "yesno", "Description" => $_GATEWAYLANG['test_mode_desc'])
    );
    return $configarray;
}

function begateway_link($params) {
    global $_LANG;

    $response = begateway_get_token($params);
    if ($response->isSuccess()) {
      $code = '
      <form method="get" action="' . $response->getRedirectUrlScriptName() . '">
        <input type="hidden" name="token" value="' . $response->getToken() . '">
        <input type="submit" value="'. $params['langpaynow'] . '">
      </form>';
    } else {
      $code = '<div style="color: red;">'. $_LANG['error'] . ': '. $response->getMessage() . '</div>';
    }
    return $code;
}

function begateway_get_token($params) {
    global $_LANG;

    $invoiceid = $params['invoiceid'];
    $customerid = $params['clientdetails']['id'];
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];
    $amount =  $params['amount'];
    $currency = $params['currency'];
    $description = $params['description'];

    $language = substr($_LANG['locale'],0,2);
    $success_url = $params["systemurl"] . "/viewinvoice.php?id=" . $invoiceid . '&paymentsuccess=true';
    $decline_url = $params["systemurl"] . "/viewinvoice.php?id=" . $invoiceid . '&paymentfailed=true';
    $fail_url = $params["systemurl"] . "/viewinvoice.php?id=" . $invoiceid . '&paymentfailed=true';
    $notification_url = $params["systemurl"] . "/modules/gateways/callback/begateway.php";
    #$notification_url = 'https://itchy-humans-mate.loca.lt/' . "/modules/gateways/callback/begateway.php";

    $token = new \BeGateway\GetPaymentToken();
    $token->money->setAmount($amount);
    $token->money->setCurrency($currency);
    $token->setTrackingId("$invoiceid|$customerid");
    $token->setDescription($description);
    $token->setLanguage($language);
    $token->setNotificationUrl($notification_url);
    $token->setSuccessUrl($success_url);
    $token->setDeclineUrl($decline_url);
    $token->setFailUrl($fail_url);
    $token->additional_data->setContract(['recurring', 'card_on_file']);

    $token->customer->setFirstName($firstname);
    $token->customer->setLastName($lastname);
    $token->customer->setEmail($email);

    $token->additional_data->setPlatformData('WHMCS v' . $params['whmcsVersion']);
    $token->additional_data->setIntegrationData('BeGateway Gateway Module v' . begateway_Version());

    if (!empty($params['payment_valid'])) {
        $token->setExpiryDate(date("c", intval($params['payment_valid']) * 60 + time() + 1));
    }

    if ($params['card_enable']) {
      $cc = new \BeGateway\PaymentMethod\CreditCard;
      $token->addPaymentMethod($cc);
    }

    if ($params['test_mode']) {
      $token->setTestMode();
    }

    if ($params['erip_enable']) {
        $erip = new \BeGateway\PaymentMethod\Erip(array(
          'order_id' => $invoiceid,
          'account_number' => $invoiceid,
          'service_no' => $params['erip_service_no'],
          'service_info' => array($description)
        ));
        $token->addPaymentMethod($erip);
      }

    \BeGateway\Settings::$shopId = $params['shop_id'];
    \BeGateway\Settings::$shopKey = $params['shop_key'];
    \BeGateway\Settings::$checkoutBase = 'https://' . $params['domain_checkout'];
    return $token->submit();
}

function begateway_refund($params) {
    \BeGateway\Settings::$shopId = $params['shop_id'];
    \BeGateway\Settings::$shopKey = $params['shop_key'];
    \BeGateway\Settings::$gatewayBase = 'https://' . $params['domain_gateway'];

    $refund = new \BeGateway\RefundOperation;
    $refund->setParentUid($params['transid']);
    $refund->money->setAmount($params['amount']);
    $refund->setReason($params['description']);

    $refund_response = $refund->submit();

    $raw_message = print_r($refund_response->getResponse(), true);
    # Return Results
    if ($refund_response->isSuccess()) {
        return array("status" => "success", "transid" => $refund_response->getUid(), "rawdata" => $raw_message);
    } elseif ($refund_response->isFailed()) {
        return array("status" => "declined", "rawdata" => $raw_message);
    } else {
        return array("status" => "error", "rawdata" => $raw_message);
    }
}

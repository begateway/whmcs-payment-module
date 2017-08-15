<?php
if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

require_once(__DIR__ . '/begateway/lib/lib/beGateway.php');

function begateway_config() {
    global $_LANG;

    // Set locale.
    putenv('LC_ALL='. $_LANG['locale']);
    setlocale(LC_ALL, $_LANG['locale']);

    // Text domain.
    $textDomain = 'beGatewayPaymentGateway';

    // Bind text domain.
    bindtextdomain($textDomain, __DIR__ . '/begateway/lang');


    $days = array();
    for($i=1;$i<31;$i++) {
      $days["$i"] = "$i";
    }

    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value" => "beGateway"),
        "shop_id" => array("FriendlyName" => dgettext($textDomain, "Shop ID"), "Type" => "text", "Size" => "25", "Description" => dgettext($textDomain, "Enter your shop Id")),
        "shop_key" => array("FriendlyName" => dgettext($textDomain, "Shop Key"), "Type" => "text", "Size" => "50", "Description" => dgettext($textDomain, "Enter your shop secret key")),
        "domain_checkout" => array("FriendlyName" => dgettext($textDomain, "Checkout Domain"), "Type" => "text", "Size" => "25", "Description" => dgettext($textDomain, "Enter your payment provider checkout domain e.g. checkout.domain.com")),
        "domain_gateway" => array("FriendlyName" => dgettext($textDomain, "Gateway Domain"), "Type" => "text", "Size" => "25", "Description" => dgettext($textDomain, "Enter your payment provider gateway domain e.g. gateway.domain.com")),
        "card_enable" => array("FriendlyName" => dgettext($textDomain, "Enable card payments"), "Type" => "yesno", "Description" => dgettext($textDomain, "Tick to enable card payments payments")),
        "erip_enable" => array("FriendlyName" => dgettext($textDomain, "Enable ERIP"), "Type" => "yesno", "Description" => dgettext($textDomain, "Tick to enable ERIP payments")),
        "erip_service_no" => array("FriendlyName" => dgettext($textDomain, "ERIP service code"), "Type" => "text", "Size" => "25", "Description" => dgettext($textDomain, "Enter your ERIP service code"))
    );
    return $configarray;
}

function begateway_link($params) {
    $response = begateway_get_token($params);
    if ($response->isSuccess()) {
      $code = '
      <form method="post" action="' . $response->getRedirectUrlScriptName() . '">
        <input type="hidden" name="token" value="' . $response->getToken() . '">
        <input type="submit" value="'. $params['langpaynow'] . '">
      </form>';
    } else {
      $code = '<div style="color: red;">'.dgettext($textDomain, "Error") . ': '. $response->getMessage() . '</div>';
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
    $notification_url = str_replace('whmcs.local', 'whmcs.webhook.begateway.com:8443', $notification_url);

    $token = new \beGateway\GetPaymentToken();
    $token->money->setAmount($amount);
    $token->money->setCurrency($currency);
    $token->setTrackingId("$invoiceid|$customerid");
    $token->setDescription($description);
    $token->setLanguage($language);
    $token->setNotificationUrl($notification_url);
    $token->setSuccessUrl($success_url);
    $token->setDeclineUrl($decline_url);
    $token->setFailUrl($fail_url);
    $token->setCancelUrl($cancel_return);

    $token->customer->setFirstName($firstname);
    $token->customer->setLastName($lastname);
    $token->customer->setEmail($email);
    $token->setAddressHidden();
    $token->setEmailReadonly();

    if ($params['card_enable']) {
      $cc = new \beGateway\PaymentMethod\CreditCard;
      $token->addPaymentMethod($cc);
    }

    if ($params['erip_enable']) {
        $erip = new \beGateway\PaymentMethod\Erip(array(
          'order_id' => $invoiceid,
          'account_number' => $invoiceid,
          'service_no' => $params['erip_service_no'],
          'service_info' => array($desription)
        ));
        $token->addPaymentMethod($erip);
      }

    \beGateway\Settings::$shopId = $params['shop_id'];
    \beGateway\Settings::$shopKey = $params['shop_key'];
    \beGateway\Settings::$checkoutBase = 'https://' . $params['domain_checkout'];
    return $token->submit();
}

function begateway_refund($params) {
    \beGateway\Settings::$shopId = $params['shop_id'];
    \beGateway\Settings::$shopKey = $params['shop_key'];
    \beGateway\Settings::$gatewayBase = 'https://' . $params['domain_gateway'];

    $refund = new \beGateway\Refund;
    $refund->setParentUid($params['transid']);
    $refund->money->setAmount($params['amount']);
    $refund->setReason($params['description']);

    $refund_response = $refund->submit();

    $raw_message = print_r($refund_response->getResponse(), true);
    print_r($refund_response);
    # Return Results
    if ($refund_response->isSuccess()) {
        return array("status" => "success", "transid" => $refund_response->getUid(), "rawdata" => $raw_message);
    } elseif ($refund_response->isFailed()) {
        return array("status" => "declined", "rawdata" => $raw_message);
    } else {
        return array("status" => "error", "rawdata" => $raw_message);
    }
}

function begateway_add_note($params) {
    $result = select_query('tblinvoices','notes',array('id'=>$params['invoiceid']));
    $note = mysql_fetch_array($result);
    update_query('tblinvoices', array('notes'=> $notes['notes'] . "\r\n" . "THIS IS TEST TRANSACTION. UID: " . $params['transid']), array('id'=>$params['invoiceid']));
}

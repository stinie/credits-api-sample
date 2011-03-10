<?php
// Copyright 2004-2011 Facebook. All Rights Reserved.

/**
   * You should reference http://developers.facebook.com/docs/creditsapi as you
   * familiarize yourself with callback.php. In particular, read all the steps
   * under "Callback Flow in Detail".
   *
   * Your application needs the following inputs and outputs
   *
   * @param int order_id             
   * @param string status
   * @param string method
   * @param array order_details (JSON-encoded)
   *
   * @return array A JSON-encoded array with order_id, next_state (optional: error code, comments)
   */

// Enter your app information below 
$api_key = '<api key>';
$secret = '<secret>';

include_once 'facebook.php';

// prepare the return data array
$data = array('content' => array());

// parse signed data
$request = parse_signed_request($_REQUEST['signed_request'], $secret);

if ($request == null) {
  // handle an unauthenticated request here
}

$payload = $request['credits'];

// retrieve all params passed in
$func = $_REQUEST['method'];
$order_id = $payload['order_id'];

if ($func == 'payments_status_update') {
  $status = $payload['status'];

  // write your logic here, determine the state you wanna move to
  if ($status == 'placed') {
    $next_state = 'settled';
    $data['content']['status'] = $next_state;
  }
  // compose returning data array_change_key_case
  $data['content']['order_id'] = $order_id;

} else if ($func == 'payments_get_items') {

  // remove escape characters  
  $order_info = stripcslashes($payload['order_info']);
  if (is_string($order_info)) {	

     // Per the credits api documentation, you should pass in an item reference
     // and then query your internal DB for the proper information. Then set 
     // the item information here to be returned to facebook then shown to the 
     // user for confirmation.
     $item['title'] = 'BFF Locket';
     $item['price'] = 1;
     $item['description'] = 'This is a BFF Locket...';
     $item['image_url'] = 'http://www.facebook.com/images/gifts/21.png';
     $item['product_url'] = 'http://www.facebook.com/images/gifts/21.png';
  } else {

    // In the sample credits application we allow the developer to enter the
    // information for easy testing. Please note that this information can be
    // modified by the user if not verified by your callback. When using
    // credits in a production environment be sure to pass an order ID and 
    // contruct item information in the callback, rather than passing it
    // from the parent call in order_info.
    $item = json_decode($order_info, true);
    $item['price'] = (int)$item['price'];

    // for url fields, if not prefixed by http://, prefix them
    $url_key = array('product_url', 'image_url');  
    foreach ($url_key as $key) {
      if (substr($item[$key], 0, 7) != 'http://') {
        $item[$key] = 'http://'.$item[$key];
      }
    }

    // prefix test-mode
    if (isset($payload['test_mode'])) {
       $update_keys = array('title', 'description');
       foreach ($update_keys as $key) {
         $item[$key] = '[Test Mode] '.$item[$key];
       }
     }
  }

  // Put the associate array of item details in an array, and return in the
  // 'content' portion of the callback payload.
  $data['content'] = array($item);
}

// required by api_fetch_response()
$data['method'] = $func;

// send data back
echo json_encode($data);

// you can find the following functions and more details
// on http://developers.facebook.com/docs/authentication/canvas
function parse_signed_request($signed_request, $secret) {
  list($encoded_sig, $payload) = explode('.', $signed_request, 2);

  // decode the data
  $sig = base64_url_decode($encoded_sig);
  $data = json_decode(base64_url_decode($payload), true);

  if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
    error_log('Unknown algorithm. Expected HMAC-SHA256');
    return null;
  }

  // check signature
  $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
  if ($sig !== $expected_sig) {
    error_log('Bad Signed JSON signature!');
    return null;
  }

  return $data;
}

function base64_url_decode($input) {
  return base64_decode(strtr($input, '-_', '+/'));
}

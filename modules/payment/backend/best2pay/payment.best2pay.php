<?php
/**
 * Обработка данных, полученных от системы Best2Pay
 *
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    6.0
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2018 OOO «Диафан» (http://www.diafan.ru/)
 */

if (!defined('DIAFAN')) {
    $path = __FILE__;
    while (!file_exists($path . '/includes/404.php')) {
        $parent = dirname($path);
        if ($parent == $path) exit;
        $path = $parent;
    }
    include $path . '/includes/404.php';
}

if (!empty($_REQUEST["callback"])) {
    $xml = file_get_contents("php://input");


    if (!$xml)
        echo("Empty data");
    $xml = simplexml_load_string($xml);

    if (!$xml)
        die("Non valid XML was received");
    $response = json_decode(json_encode($xml));
    if (!$response)
        die("Non valid XML was received");

    $order_id = intval($response->reference);

    if ($order_id == 0)
        die("Invalid order id: {$order_id}");

    $pay = $this->diafan->_payment->check_pay($order_id, 'best2pay');

    if (!orderAsPayed($response, $pay)) {
        $this->diafan->_payment->fail($pay, 'pay');
    } else {
        echo("ok");
        $this->diafan->_payment->success($pay, 'pay');
    }
} else {
    $pay = $this->diafan->_payment->check_pay($_REQUEST["reference"], 'best2pay');

    $b2p_order_id = intval($_REQUEST["id"]);
    if (!$b2p_order_id)
        return false;

    $b2p_operation_id = intval($_REQUEST["operation"]);
    if (!$b2p_operation_id)
        return false;

    if (checkPaymentStatus($pay, $b2p_order_id, $b2p_operation_id)) {
        $this->diafan->_payment->success($pay);
    } else {
        $this->diafan->_payment->fail($pay);
    }
}


function checkPaymentStatus($pay, $b2p_order_id, $b2p_operation_id)
{
    // check payment operation state
    $signature = base64_encode(md5($pay["params"]['best2pay_sector'] . $b2p_order_id . $b2p_operation_id . $pay["params"]['best2pay_password']));

    if (!$pay["params"]['best2pay_test']) {
        $best2pay_url = 'https://pay.best2pay.net';
    } else {
        $best2pay_url = 'https://test.best2pay.net';
    }

    $query = http_build_query(array(
        'sector' => $pay["params"]['best2pay_sector'],
        'id' => $b2p_order_id,
        'operation' => $b2p_operation_id,
        'signature' => $signature
    ));
    $context = stream_context_create(array(
        'http' => array(
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($query) . "\r\n",
            'method' => 'POST',
            'content' => $query
        )
    ));

    $repeat = 3;
    while ($repeat) {

        $repeat--;

        sleep(2);

        $xml = file_get_contents($best2pay_url . '/webapi/Operation', false, $context);


        if (!$xml)
            break;

        $xml = simplexml_load_string($xml);
        if (!$xml)
            break;
        $response = json_decode(json_encode($xml));

        if (!$response)
            break;

        $order_id = intval($response->reference);
        if ($order_id == 0)
            return false;

        if (($response->type != 'PURCHASE' && $response->type != 'EPAYMENT') || $response->state != 'APPROVED')
            return false;

        $tmp_response = json_decode(json_encode($response), true);
        unset($tmp_response["signature"]);
        unset($tmp_response["protocol_message"]);

        $signature = base64_encode(md5(implode('', $tmp_response) . $pay["params"]['best2pay_password']));
        if (!$signature === $response->signature) {
            break;
        }
        return true;
    }

    return false;
}

function orderAsPayed($response, $pay)
{

    $order_id = intval($response->reference);
    if ($order_id == 0)
        die("Invalid order id: {$order_id}");

    if (!$pay)
        die("No such order id: {$order_id}");


    $tmp_response = (array)$response;
    unset($tmp_response["signature"]);
    $signature = base64_encode(md5(implode('', $tmp_response) . $pay["params"]['best2pay_password']));
    if ($signature !== $response->signature)
        die("Invalid signature");

    if (($response->type != 'PURCHASE' && $response->type != 'EPAYMENT' && $response->type != 'AUTHORIZE') || $response->state != 'APPROVED')
        return false;

    return true;
}











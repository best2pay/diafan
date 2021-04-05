<?php

if (! defined('DIAFAN'))
{
	$path = __FILE__;
	while(! file_exists($path.'/includes/404.php'))
	{
		$parent = dirname($path);
		if($parent == $path) exit;
		$path = $parent;
	}
	include $path.'/includes/404.php';
}

class Payment_best2pay_model extends Diafan
{
	public function get($params, $pay)
	{
        $currency = '643';

        if (empty($params["best2pay_test"])) {
            $best2pay_url = 'https://pay.best2pay.net';
        } else {
            $best2pay_url = 'https://test.best2pay.net';
        }

        $desc=$pay["desc"];

        $amount = $pay["summ"];
        $signature = base64_encode(md5($params["best2pay_sector"] . intval($amount * 100) . $currency . $params["best2pay_password"]));

        $fiscalPositions='';

        $result["fiscal_position"] = '';

        if(! empty($pay["details"]["discount"]))
        {
            $s = 0;
            foreach($pay["details"]["goods"] as &$r)
            {
                $s += $r["summ"];
            }
            foreach($pay["details"]["goods"] as &$r)
            {
                $r["price"] = number_format($r["price"] * ($pay["summ"]/$s), 2, '.', '');
                $r["summ"] = number_format($r["price"] * $r["count"], 2, '.', '');
            }
        }
        if(! empty($pay["details"]["goods"]))
        {
            $fiscalAmount = 0;
            foreach($pay["details"]["goods"] as $row)
            {
                $result["fiscal_position"].=$row["count"].';';
                $price = $row["price"] * 100;
                $result["fiscal_position"].=$price.';';
                $result["fiscal_position"].=$params["best2pay_tax"].';';
                $result["fiscal_position"].=str_ireplace(['|', ';'], ['', ''], $row["name"]).'|';

                $fiscalAmount += $row["count"] * $price;
            }
            if ($pay["details"]["delivery"]["summ"]) {
                $result["fiscal_position"].='1;';
                $price = $pay["details"]["delivery"]["summ"] * 100;
                $result["fiscal_position"].=$price.';';
                $result["fiscal_position"].=$params["best2pay_tax"].';';
                $result["fiscal_position"].='Доставка|';

                $fiscalAmount += $price;
            }
            $amountDiff = abs($fiscalAmount - $amount * 100);
            if ($amountDiff) {
                $result["fiscal_position"].='1;';
                $result["fiscal_position"].=$amountDiff.';';
                $result["fiscal_position"].='6;';
                $result["fiscal_position"].='coupon;14';
            }
        }

        if (!empty($params["best2pay_kkt"])) {
            if ($params["best2pay_kkt"]==1) {
                $TAX = (strlen($params["best2pay_tax"]) > 0) ?
                    intval($params["best2pay_tax"]) : 7;
                if ($TAX > 0 && $TAX < 7) {
                    $fiscalPositions = $result["fiscal_position"];
                }
            }
        }

        $query = http_build_query(array(
            'sector' => $params["best2pay_sector"],
            'reference' => $pay["id"],
            'amount' => intval($amount * 100),
            'fiscal_positions' => $fiscalPositions,
            'description' => $desc,
            'email' => $pay["details"]["email"],
            'phone' => $pay["details"]["phone"],
            'currency' => $currency,
            'mode' => 1,
            'url' => BASE_PATH_HREF.'payment/get/best2pay',
            'signature' => $signature
        ));

        $context = stream_context_create(array(
            'http' => array(
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . "Content-Length: " . strlen($query) . "\r\n",
                'method'  => 'POST',
                'content' => $query
            )
        ));

        $b2p_order_id = file_get_contents($best2pay_url . '/webapi/Register', false, $context);

        $resultUrl = '';
        if (intval($b2p_order_id) == 0) {
            $pay["text"] = $b2p_order_id;
        } else {
            $signature = base64_encode(md5($params["best2pay_sector"] . $b2p_order_id . $params["best2pay_password"]));
            $resultUrl =  "{$best2pay_url}/webapi/Purchase?sector={$params["best2pay_sector"]}&id={$b2p_order_id}&signature={$signature}";
        }

        $result['data'] = array(
            'sector' => $params["best2pay_sector"],
            'reference' => $pay["id"],
            'amount' => intval($amount * 100),
            'fiscal_positions' => $fiscalPositions,
            'description' => $desc,
            'email' => $pay["details"]["email"],
            'phone' => $pay["details"]["phone"],
            'currency' => $currency,
            'mode' => 1,
            'url' => BASE_PATH_HREF.'payment/get/best2pay',
            'signature' => $signature
        );

        //$result['pay'] = $pay;

        $result["resultUrl"]      = $resultUrl;
        $result["text"]      = $pay["text"];

        return $result;
	}
}

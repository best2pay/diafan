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

class Payment_best2pay_admin
{
	public $config;

	public function __construct()
	{
		$this->config = array(
			"name" => 'Best2Pay',
			"params" => array(
				'best2pay_sector' => 'Номер сектора',
				'best2pay_password' => 'Пароль',
				'best2pay_test' => array('name' => 'Тестовый режим', 'type' => 'checkbox'),
				'best2pay_kkt' => array('name' => 'Передавать данные на свое ККТ', 'type' => 'checkbox'),
                'best2pay_tax' => array(
                    'name' => 'Ставка НДС',
                    'type' => 'select',
                    'select' => array(
                        1 => 'ставка НДС 18%',
                        2 => 'ставка НДС 10%',
                        3 => 'ставка НДС расч. 18/118',
                        4 => 'ставка НДС расч. 10/110',
                        5 => 'ставка НДС 0%',
                        6 => 'НДС не облагается',
                    ),
                ),
            )
		);
	}
}
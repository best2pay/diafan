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

echo $result["text"];

//echo '<pre>';
//print_r($result);

if ($result["resultUrl"]) {
?>
<form id="pay" name="pay" method="POST" action="<?php echo $result["resultUrl"]?>">
    <p><input type="submit" value="<?php echo $this->diafan->_('Оплатить', false);?>"></p>
</form>
<?php } ?>
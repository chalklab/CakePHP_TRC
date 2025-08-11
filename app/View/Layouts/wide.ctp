<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php echo $this->Html->charset(); ?>
	<title>ThermoML Converter: <?php echo $this->fetch('title'); ?></title>
	<?php
	echo $this->Html->meta('icon');
	echo $this->Html->css('bootstrap.min');
	echo $this->Html->css('bootstrap-theme.min');
	echo $this->Html->css('sticky-footer-navbar');
	echo $this->Html->css('signin');
	echo $this->Html->css("https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.7/css/bootstrap-dialog.min.css");
	echo $this->Html->script('jquery');
	echo $this->Html->script('jqcake');
	echo $this->Html->script('bootstrap.min');
	echo $this->Html->script('jsmol/JSmol.lite.nojq');
	echo $this->Html->script("https://cdnjs.cloudflare.com/ajax/libs/bootstrap3-dialog/1.34.7/js/bootstrap-dialog.min.js");
	echo $this->fetch('meta');
	echo $this->fetch('css');
	echo $this->fetch('script');
	?>
</head>
<body>
	<?php include('header.ctp'); ?>
		<div class="container-fluid theme-showcase" role="main" style="padding-top: 60px;">
			<?php echo $this->fetch('content'); ?>
		</div>
	<?php include('footer.ctp'); ?>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php echo $this->Html->charset(); ?>
	<title>ThermoML Converter: <?php echo $title_for_layout; ?></title>
	<?php
	echo $this->Html->meta('icon');
	echo $this->Html->css('bootstrap.min');
	echo $this->Html->css('bootstrap-theme.min');
	echo $this->Html->css('sticky-footer-navbar');
	echo $this->Html->css('signin');
	echo $this->Html->css('trc');
    echo $this->Html->script('jquery');
	echo $this->Html->script('bootstrap.min');
	echo $this->Html->script('jsmol/JSmol.min.nojq');
	echo $this->Html->script('trc');
	echo $this->fetch('meta');
	echo $this->fetch('css');
	echo $this->fetch('script');
	?>
</head>
<body>
	<?php include('header.ctp'); ?>
		<div class="container-fluid theme-showcase" role="main" style="margin-top: 60px;">
			<?php echo $this->fetch('content'); ?>
		</div>
	<?php //echo $this->element('sql_dump'); ?>
	<?php include('footer.ctp'); ?>
</body>
</html>

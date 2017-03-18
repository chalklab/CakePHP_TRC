<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php echo $this->Html->charset(); ?>
	<title>ChemExtractor: <?php echo $title_for_layout; ?></title>
	<?php
	echo $this->Html->meta('icon');
	echo $this->Html->css('firefox');
	echo $this->Html->css('jquery-ui');
	echo $this->Html->css('bootstrap.min');
	echo $this->Html->css('bootstrap-theme.min');
	echo $this->Html->css('sticky-footer-navbar');
	echo $this->Html->css('boottabs');
	echo $this->Html->css('shadows');
	echo $this->Html->css('signin');
	echo $this->Html->css('chemextractor');
    echo $this->Html->script('jquery');
	echo $this->Html->script('jquery-ui');
	echo $this->Html->script('jqcake');
	echo $this->Html->script('bootstrap.min');
	echo $this->Html->script('https://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-MML-AM_CHTML',['async'=>true]);
    echo $this->fetch('meta');
	echo $this->fetch('css');
	echo $this->fetch('script');
	?>
    <script type="text/x-mathjax-config">
        MathJax.Hub.Config(
        {tex2jax: {
        inlineMath: [['**','**']],
        displayMath: [['$$','$$']]
      }});
    </script>
</head>
<body>
	<?php include('header.ctp'); ?>
		<div class="container theme-showcase" role="main">
			<?php echo $this->fetch('content'); ?>
		</div>
	<?php //echo $this->element('sql_dump'); ?>
	<?php include('footer.ctp'); ?>
</body>
</html>
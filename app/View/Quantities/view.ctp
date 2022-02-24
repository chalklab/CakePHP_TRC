<?php
//pr($data);exit;
$q=$data['Quantity'];
$cnts=$data['counts'];
?>
<div class="row">
	<div class="col-sm-10 col-sm-offset-1">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title">Physical Property</h3>
			</div>
			<div class="panel-body">
				<p><?php echo $q['name']; ?></p>
				<?php if(!empty($q['definition'])) { echo "<p>".$q['definition']."</p>"; }; ?>
				<p><?php echo "Symbol: ".$q['symbol']; ?></p>
				<p><?php
					if (stristr($q['source'], 'http')) {
						echo $this->Html->link('Source', $q['source'], ['target' => '_blank']);
					}
					?></p>
				<p>Counts: Conditions: <?php echo $cnts['ccount']; ?>, Data: <?php echo $cnts['dcount']; ?></p>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-sm-10 col-sm-offset-1">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h3 class="panel-title">Systems</h3>
			</div>
			<div class="list-group responsivediv400">
				<?php
				foreach ($syss as $sid=>$name) {
					echo $this->Html->link($name,'/systems/view/'.$sid,['class'=>'list-group-item']);
				} ?>
			</div>
		</div>
	</div>
</div>

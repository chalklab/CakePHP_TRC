<?php
// separate the data into sections by table
$con=$cond['Condition'];
$cmp=$cond['Compohnent'];
$pnt=$cond['Datapoint'];
$ser=$cond['Dataseries'];
$set=$cond['Dataset'];
$phs=$cond['Phase'];
$qty=$cond['Quantity'];
$sys=$cond['System'];
$unt=$cond['Unit'];
?>
<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 style="margin: 0;">Condition</h4>
			</div>
			<div class="panel-body">
				<p>From: <?php echo $this->Html->link($set['title'],'/datasets/view/'.$set['id']); ?><br/>
					Quantity: <?php echo $qty['name']; ?><br/>
					Value: <?php echo $con['number']; ?><br/>
					Unit: <?php echo $unt['symbol']; ?><br/>
					<?php
					if(!is_null($ser['id'])) { echo "Dataseries: ".$ser['idx'].'<br/>'; }
					if(!is_null($pnt['id'])) { echo "Datapoint: ".$pnt['row_index'].'<br/>'; }
					if(!is_null($phs['id'])) { echo "Phase: ".$phs['Phasetype']['name'].'<br/>'; }
					if(!is_null($sys['id'])) { echo "System: ".$sys['name'].'<br/>'; }
					if(!is_null($cmp['id'])) { echo "Component: ".$cmp['Chemical']['Substance']['name'].'<br/>'; }
					?>
				</p>
			</div>
		</div>
	</div>
</div>
<?php //pr($cond); ?>

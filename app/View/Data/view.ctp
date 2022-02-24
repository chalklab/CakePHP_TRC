<?php
// separate the data into sections by table
$dat=$datum['Data'];
$cmp=$datum['Compohnent'];
$pnt=$datum['Datapoint'];
$ser=$datum['Dataseries'];
$set=$datum['Dataset'];
$phs=$datum['Phase'];
$qty=$datum['Quantity'];
$smp=$datum['Sampleprop'];
$unt=$datum['Unit'];
?>
<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 style="margin: 0;">Datum</h4>
			</div>
			<div class="panel-body">
				<p>From: <?php echo $this->Html->link($set['title'],'/datasets/view/'.$set['id']); ?><br/>
					Quantity: <?php echo $qty['name']; ?><br/>
					Value: <?php echo $dat['number']." ± ".$dat['error']; ?><br/>
					Unit: <?php echo $unt['symbol']; ?><br/>
					<?php
					if(!is_null($ser['id'])) { echo "Dataseries: ".$ser['idx'].'<br/>'; }
					if(!is_null($pnt['id'])) { echo "Datapoint: ".$pnt['row_index'].'<br/>'; }
					if(!is_null($phs['id'])) { echo "Phase: ".$phs['Phasetype']['name'].'<br/>'; }
					if(!is_null($smp['id'])) { echo "Sampleprop: ".$smp['quantity_name'].'<br/>'; }
					if(!is_null($cmp['id'])) { echo "Component: ".$cmp['Chemical']['Substance']['name'].'<br/>'; }
					?>
				</p>
			</div>
		</div>
	</div>
</div>
<?php //pr($cond); ?>

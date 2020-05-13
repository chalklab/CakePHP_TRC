<?php //pr($data);exit;
$sys=$data['System'];
$dat=$data['Data'];
pr($data);
?>
<h2><?php echo $sys['name']; ?></h2>
<p>Phase: <?php echo $sys['phase'];?></p>
<p>Type: <?php echo $sys['type'];?></p>
<div class="col-md-12">
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h2 class="panel-title">System Data</h2>
		</div>
		<div class = "panel-body">
			<table class = "table table-striped">
				<thead>
				<tr>
					<th>Conditions</th>
					<th>Data</th>
					<th>Settings</th>
					<th>References</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach($dat as $datum)
				
				{ ?>
					<tr>
						<td>
						
						</td>
						<td>
							<?php echo $datum['number'].' ('.$datum['error'].')'; ?> <
						</td>
						<td>
						
						</td>
						<td>
						
						</td>
						<td>
						
						</td>
					</tr>
				<?php }  ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
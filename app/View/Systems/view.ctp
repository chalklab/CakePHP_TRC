<?php
$system=$data['System'];
$sets=$data['Reference'];
$subs=$data['Substance'];
$subcnt=count($subs);
?>
<div class="row">
	<div class="col-xs-12 col-sm-<?php echo 12-(3*$subcnt); ?> col-md-<?php echo 10-(2*$subcnt); ?> col-md-offset-1" style="padding-right: 3px;">
		<div class="panel panel-success" style="font-size: 16px;height: 250px;">
			<div class="panel-heading">
				<h1 class="panel-title">System</h1>
			</div>
			<div class="panel-body" style="padding: 10px 10px 10px 15px;">
				<ul class="list-unstyled">
					<li><?php echo "<b>Name:</b> ".$system['name']; ?></li>
					<li><b>Substances</b>:
					<?php
					foreach($subs as $sidx=>$sub) {
						if($sidx>0) { echo "; "; }
						echo $this->Html->link($sub['name'],'/substances/view/'.$sub['id']);
					}; ?>
					</li>
					<li><?php echo "<b>Composition:</b> ".$system['composition']; ?></li>
					<li><?php echo "<b>References:</b> ".$system['refcnt']; ?></li>
					<li><?php echo "<b>Datasets:</b> ".$system['setcnt']; ?></li>
					<li><?php echo "<b>Datapoints:</b> ".$system['pntcnt']; ?></li>
				</ul>
			</div>
		</div>
	</div>
	<?php
	foreach ($subs as $idx=>$sub) { ?>
		<div class="col-xs-<?php echo 12/$subcnt; ?> col-sm-3 col-md-2" style="padding: 0 3px;">
			<?php
			$identifiers=$sub['Identifier'];$idents=[];
			foreach ($identifiers as $ident) { $idents[$ident['type']]=$ident['value']; }
			echo $this->element('molecule',['index'=>$idx,'named'=>false,'height'=>250]+$idents);
			?>
		</div>
	<?php } ?>
</div>
<div class="row">
	<div class="col-xs-6 col-md-8 col-md-offset-1">
		<h3>Datasets by Reference</h3>
	</div>
	<div class="col-xs-6 col-md-2" style="margin-top: 20px;">
		<?php if(count($sets)>5) { ?>
			<!-- uses listsrc function in trc.js -->
			<input id="listsrc" placeholder="Search references" class="form-control pull-right" data-search-override="true" type="text"/>
		<?php } ?>
	</div>
</div>
<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<div id="accordion" class="panel-group">
			<?php
			$i=1;$size=4;
			foreach($sets as $refid=>$ref) { ?>
				<div class="panel panel-default sections" style="margin: 0;">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion" data-sort="<?= $ref['cite']; ?>" href="#collapse<?= $i; ?>">
								<?php
								$count=count($ref['sets']);$plural='';
								if($count>1) { $plural='s'; }
								echo $ref['cite']." (".$count." dataset".$plural.")";
								?>
							</a>
						</h4>
					</div>
					<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse<?php if($i==1) { echo " in"; } ?>">
						<div class="list-group" style="max-height: <?php echo ($size*40)+2; ?>px;overflow-y: scroll;font-size: 14px;">
							<?php
							foreach($ref['sets'] as $setid=>$set) {
								$q='Quantity';
								if(stristr($set['props'],';')) { $q='Quantities'; }
								$desc = "<b>Experimental ".$q.":</b> ".$set['props'].", ".$set['sers']." series, ".$set['points']." datapoints";
								$opts = ["title"=>strtolower($desc),'alt'=>$desc,"class"=>"list-group-item"];
								echo "<li class='links'>".html_entity_decode($this->Html->link($desc,'/datasets/view/'.$setid,$opts))."</li>";
							}
							?>
						</div>
					</div>
				</div>
				<?php $i++; } ?>
		</div>
	</div>
</div>

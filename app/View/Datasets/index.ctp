<!-- uses listsrc function in trc.js -->
<div class="row">
	<div class="col-md-8 col-md-offset-1">
		<h3>Datasets by Experimental Quantity</h3>
	</div>
	<div class="col-md-2" style="margin-top: 20px;">
		<input id="listsrc" placeholder="Search datasets" class="form-control pull-right" data-search-override="true" type="text"/>
	</div>
</div>
<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<div class="panel-group" id="accordion">
		<?php
		$i=1;$size=4;
		foreach($qs as $qid=>$qname) {
			if(isset($qr[$qid])) { ?>
			<div class="panel panel-default sections" style="margin: 0;">
				<div class="panel-heading" data-toggle="collapse" data-parent="#accordion" data-sort="<?= $qname; ?>" href="#collapse<?= $i; ?>" style="cursor: pointer;padding: 5px 15px;">
					<p style="margin: 0;font-weight: bold;"><?php $count=count($qr[$qid]);echo $qname." (".$count." papers)"; ?></p>
				</div>
				<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse<?php if($i==1) { echo " in"; } ?>">
					<div class="list-group" style="max-height: <?php echo ($size*40*2)+2; ?>px;overflow-y: scroll;font-size: 14px;">
						<?php
						$qrs=array_intersect_key($rs,$qr[$qid]); // ** reference_id as both key and value needed
						//pr($qrs);exit;
						foreach($qrs as $rid=>$rtitle) {
							$opts = ["title"=>strtolower($rtitle),'alt'=>$rtitle,"class"=>"list-group-item list-group-item-small"];
							echo "<li class='links'>".$this->Html->link($rtitle,'/references/view/'.$rid,$opts)."</li>";
						}
						?>
					</div>
				</div>
			</div>
			<?php } $i++; } ?>
		</div>
	</div>
</div>

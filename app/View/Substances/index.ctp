<!-- uses listsrc function in trc.js -->
<div class="row">
	<div class="col-md-8 col-md-offset-1">
		<h3>Substances</h3>
	</div>
	<div class="col-md-2" style="margin-top: 20px;">
		<input id="listsrc" placeholder="Search substances" class="form-control pull-right" data-search-override="true" type="text"/>
	</div>
</div>
<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<div class="panel-group" id="accordion">
			<?php
			$i=1;$size=4;
			foreach($data as $first=>$subs) { ?>
				<div class="panel panel-default sections" style="margin: 0;">
					<div class="panel-heading" data-toggle="collapse" data-parent="#accordion" data-sort="<?= $first; ?>" href="#collapse<?= $i; ?>" style="cursor: pointer;padding: 5px 15px;">
						<p style="margin: 0;font-weight: bold;"><?php $count=count($subs);echo $first." (".$count." papers)"; ?></p>
					</div>
					<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse<?php if($i==1) { echo " in"; } ?>">
						<div class="list-group" style="max-height: <?php echo ($size*40)+2; ?>px;overflow-y: scroll;font-size: 14px;">
							<?php
							foreach($subs as $subid=>$name) {
								$opts = ["title"=>strtolower($name),'alt'=>$name,"class"=>"list-group-item list-group-item-small"];
								echo "<li class='links'>".html_entity_decode($this->Html->link($name,'/substances/view/'.$subid,$opts))."</li>";
							}
							?>
						</div>
					</div>
				</div>
				<?php $i++; } ?>
		</div>
	</div>
</div>

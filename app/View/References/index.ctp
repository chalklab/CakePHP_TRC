<!-- uses listsrc function in trc.js -->
<?php
$path=Configure::read('path');
?>
<div class="row">
	<div class="col-md-8 col-md-offset-1">
		<h3>References by Year</h3>
	</div>
	<div class="col-md-2" style="margin-top: 20px;">
		<input id="listsrc" placeholder="Search references" class="form-control pull-right" data-search-override="true" type="text"/>
	</div>
</div>
<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<div class="panel-group" id="accordion">
		<?php
		$i=1;$size=4;
			foreach($data as $year=>$refs) { ?>
				<div class="panel panel-default sections" style="margin: 0;">
					<div class="panel-heading" data-toggle="collapse" data-parent="#accordion" data-sort="<?= $year; ?>" href="#collapse<?= $i; ?>" style="cursor: pointer;padding: 5px 15px;">
						<p style="margin: 0;font-weight: bold;"><?php $count=count($refs);echo $year." (".$count.")"; ?></p>
					</div>
					<div id="collapse<?php echo $i; ?>" class="panel-collapse collapse<?php if($i==1) { echo " in"; } ?>">
						<div class="list-group" style="max-height: <?php echo ($size*40)+2; ?>px;overflow-y: scroll;font-size: 14px;">
							<?php
							foreach($refs as $refid=>$title) {
								$opts = ["title"=>strtolower($title),'alt'=>$title,"class"=>"list-group-item list-group-item-small"];
								echo "<li class='links'>".$this->Html->link($title,$path.'references/view/'.$refid,$opts)."</li>";
							}
							?>
						</div>
					</div>
				</div>
				<?php $i++; } ?>
		</div>
	</div>
</div>

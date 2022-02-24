<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title">Units</h4>
			</div>
			<div class="list-group responsivediv200">
				<?php
				foreach ($data as $id => $title) {
					echo $this->Html->link($title,'/units/view/'.$id,['class'=>'list-group-item']);
				} ?>
			</div>
		</div>
	</div>
</div>

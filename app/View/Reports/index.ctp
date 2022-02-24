<div class="row">
	<div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h2 class="panel-title">Reports</h2>
            </div>
            <div class='list-group responsivediv200'>
				<?php foreach ($data as $id => $title) {
					echo $this->Html->link($title,'/reports/view/'.$id,['class'=>'list-group-item list-group-item-small']);
				}
				?>
            </div>
        </div>
    </div>
</div>

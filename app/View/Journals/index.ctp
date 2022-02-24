<?php //pr($data); ?>

<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Journals</h2>
            </div>
            <div class='list-group'>
				<?php foreach ($data as $id => $name) {
					echo $this->Html->link($name." (".$id.")",'/journals/view/'.$id,['class'=>'list-group-item list-group-item-small']);
				}
				?>
            </div>
        </div>
    </div>
</div>

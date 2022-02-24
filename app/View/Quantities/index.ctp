<?php //pr($data);exit; ?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Physical Quantities</h2>
            </div>
            <div class="panel-body">
				<?php foreach ($data as $first=>$quants) { ?>
				<ul class="list-unstyled">
					<?php echo '<li>'.$first.'</li>'; ?>
                	<ul class="list-unstyled">
                    	<?php
						foreach ($quants as $id=>$name)
                        echo "<li>".$this->Html->link($name,'/quantities/view/'.$id)."</li>";
                    	?>
					</ul>
                </ul>
				<?php } ?>
            </div>
        </div>
    </div>
</div>

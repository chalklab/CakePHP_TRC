<?php //pr($data); ?>

<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Publications</h2>
            </div>
            <div class='list-group'>
                <div class="panel-group" id="accordion">
                    <?php foreach ($data as $phaseid => $phase) { ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $phaseid; ?>">
                                        <?php echo "Phase $phaseid"; ?>
                                    </a>
                                </h4>
                            </div>
                            <div id="collapse<?php echo $phaseid; ?>" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <?php foreach ($phase as $id => $title) {
                                        echo $this->Html->link($title." (".$propCount[$id].")",'/publications/view/'.$id,['class'=>'list-group-item']);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
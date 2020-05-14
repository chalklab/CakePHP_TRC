<?php //pr($data); ?>

<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Journals</h2>
            </div>
            <div class='list-group'>
                <div class="panel-group" id="accordion">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">

                                </h4>
                            </div>
                                <div class="panel-body">
                                    <?php foreach ($data as $id => $name) {
                                        echo $this->Html->link($name." (".$id.")",'/Journals/view/'.$id,['class'=>'list-group-item']);
                                    }
                                    ?>
                                </div>
                            </div>

                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
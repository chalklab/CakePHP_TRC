<?php//pr($data);?>
<h2>Units</h2>

<div class="row">
    <div class="col-sm-12 col-sm-offset-1"
        <div class="panel panel-primary">

            <div class='list-group'>
                <div class="panel-group" id="accordion">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">

                            </h4>
                        </div>
                            <div class="panel-body">


                                <?php foreach ($data as $id => $title) {echo $this->Html->link($title." (".id.")",'/units/view/'.$id,['class'=>'list-group-item']);
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



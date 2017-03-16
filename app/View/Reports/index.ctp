<?php //pr($data);exit; ?>
<h2>Reports</h2>
<?php foreach($data as $pub=>$reps) { ?>
    <div class="col-sm-6">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title"><?php echo $pub; ?><span class="badge pull-right text-success"><?php echo count($reps); ?></span></h3>
            </div>
            <div class="list-group" style="max-height: 200px;overflow-y:scroll;">
                <?php
                foreach($reps as $id=>$title) {
                    echo "<li class='list-group-item'>".$this->Html->link(str_replace($pub.": ","",$title), '/reports/view/'.$id);
                    if ($this->Session->read('Auth.User.type') == 'admin') { ?>
                        <span class="badge pull-right text-info">
                            <?php echo $this->Html->link("<span class='glyphicon glyphicon-remove' style='color: #fff;'></span>", '/reports/delete/'.$id,['escape'=>false]) ?>
                        </span>
                    <?php }
                    echo "</li>";
                }
                ?>
            </div>
        </div>
    </div>
<?php } ?>
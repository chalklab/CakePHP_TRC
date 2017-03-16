<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <h1>Rulesets <a href="add" class="btn btn-success btn-sm pull-right">Add Ruleset</a></h1>
        <div class="panel panel-primary">
            <div class="list-group">
                <?php
                foreach ($data as $id=>$name) {
                    echo "<li class='list-group-item'>";
                    echo $this->Html->link($name,'/rulesets/view/'.$id);
                    if ($this->Session->read('Auth.User.type') == 'admin') {
                        echo $this->Html->link("Delete",'/rulesets/delete/'.$id,['class'=>'btn btn-danger btn-sm pull-right','style'=>'margin-top: -5px;margin-left: 10px;']);
                        echo $this->Html->link("Edit",'/rulesets/edit/'.$id,['class'=>'btn btn-warning btn-sm pull-right','style'=>'margin-top: -5px;']);
                    }
                    echo "</li>";
                }
                ?>
            </div>
        </div>
    </div>
</div>
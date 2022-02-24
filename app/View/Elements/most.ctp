<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Papers with the Most Data (will load slowly)</h3>
    </div>
    <div class="list-group" style="max-height: 580px;overflow-y:scroll;">
        <?php
        $data=$this->requestAction('/files/most/5');$index=0;
        foreach($data as $id=>$name) {
            echo $this->Html->link($name,'/references/view/'.$id,['class'=>'list-group-item']);
        }
        ?>
    </div>
</div>

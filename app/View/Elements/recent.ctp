<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">Recently Extracted Data</h3>
    </div>
    <div class="list-group" style="max-height: 580px;overflow-y:scroll;">
        <?php
        $data=$this->requestAction('/reports/recent/100');$index=0;
        foreach($data as $id=>$name) {
            echo $this->Html->link($name,'/reports/view/'.$id,['class'=>'list-group-item']);
        }
        ?>
    </div>
</div>
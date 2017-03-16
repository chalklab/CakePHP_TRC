<?php //pr($data);exit; ?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Chemical Properties</h2>
            </div>
            <div class="panel-body">
                <ul class="list-unstyled">
                    <?php
                    foreach ($data as $datum) {
                        $p=$datum['Property'];$c=$datum[0];
                        echo "<li>".$this->Html->link($p['name'],'/properties/view/'.$p['id'])." (".$c['pcount'].")</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>
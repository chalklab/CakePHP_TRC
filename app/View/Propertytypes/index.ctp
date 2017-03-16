<?php //pr($data)?>
<ul class="list-unstyled">
    <?php
    foreach ($data as $group => $type) { ?>
        <div class="row">
            <div class="col-sm-9">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h2 class="panel-title"><?php echo $groups[$group]; ?></h2>
                    </div>
                    <div class="panel-body">
                        <?php
                        foreach ($type as $id => $code) {
                            if ($propCount[$id] > 0) {
                                echo "<li>" . $this->Html->link($code, '/propertytypes/view/' . $id) . " (" . $propCount[$id] . ")" . "</li>";
                            }
                        }
                        ?> </div>
                </div>
            </div>
        </div> <?php
    }
    ?>
</ul><br>

<?php if ($this->Session->read('Auth.User.type') == "admin") {
    echo $this->Html->link("Add New Property Type", ['action' => 'add']);
} ?>

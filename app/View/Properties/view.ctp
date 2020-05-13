<?php
//pr($data);exit;
?>
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Chemical Property</h3>
                </div>
                <div class="panel-body">
                    <p><?php echo $property['name']; ?></p>
                    <p><?php echo $property['definition']; ?></p>
                    <p>
                        <?php
                        if (stristr($property['source'], 'http')) {
                            echo $this->Html->link('Source', $property['source'], ['target' => '_blank']);
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Systems</h3>
                </div>

                <?php
                $columns = 3;
                function array_divide($systems, $columns)
                {
                    $total = count($systems);
                    if ($total == 0) return false;
                    $segmentLimit = ceil($total / $columns);
                    $outputArray = array_chunk($systems, $segmentLimit,true);
                    return $outputArray;
                }

                //pr(array_divide($systems, $columns));
                $systems = array_divide($systems, $columns);

                foreach ($systems as $column) { ?>
                    <ul class="list-group col-sm-4">
                        <?php
                        foreach ($column as $sid => $name) {
                            echo $this->Html->link(substr($name,0,40) . "… (" . $counts[$sid] . ")", 'system/' . $sid . '/' . $id, ['class' => 'list-group-item']);
                        }
                        ?>
                    </ul>
                    <?php
                }
                ?>

            </div>
        </div>
    </div>

<?php //pr($data); ?>
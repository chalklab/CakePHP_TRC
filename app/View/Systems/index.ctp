<?php //pr($data); ?>
<?php
echo "<h3>"."Systems (beta)"."</h3>";

$chars=[];
$sortedSystems=array();
foreach($data as $id=>$name) {
    $trimmed=trim($name);
    $char=strtoupper(substr($name,0,1));
    if(!in_array($char,$chars)) {
        $chars[] = $char;
    }
    if($name!="") {
        $sortedSystems[$char][$id] = $name;
    }
}?>

<div class="row">
    <div class="col-sm-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Search</h2>
            </div>
            <div class="panel-body">
                <?php
                echo '<p>' . '<input class="col-xs-12" type="text" id="letterSearch" placeholder="Search systems...">' . '</p>';
                ?>
            </div>
        </div>
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Browse</h2>
            </div>
            <div class="panel-body">
                <p>
                    <?php

                    echo '<p>' . 'Click on a letter/number below to show systems starting with that letter' . '</p>';

                    foreach ($chars as $char) {
                        echo "<button type='button' class='btn btn-default btn-md' onclick=\"showletter('" . $char . "')\"
                                  style=\"display: inline;cursor: pointer; font-family:'Lucida Console', monospace\">";
                        echo "$char";
                        echo "</button>";
                    }
                    echo "</p>";
                    ?>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="panel panel-default">
            <div class="panel-body" style="max-height: 476px;overflow-y: scroll;">
                <?php
                $chars = array_keys($sortedSystems);
                foreach ($sortedSystems as $char => $iarray) {
                    echo "<div id='" . $char . "' class='letter' style='display: none;'>";
                    echo "<ul class='list-unstyled' style='font-size: 16px;'>";
                    foreach ($iarray as $pid => $system) {
                        echo '<li>' . html_entity_decode($this->Html->link($system, '/systems/view/' . $pid)) . '</li>';
                    }
                    echo "</ul></div>";
                }
                ?>
            </div>
        </div>

    </div>
</div>
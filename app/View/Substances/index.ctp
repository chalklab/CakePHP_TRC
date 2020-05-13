
<?php
$chars=[];
foreach($data as $char=>$iarray) { $chars[]=$char; }
?>

<h3>Substances</h3>

<div class="row">
    <div class="col-sm-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Search</h2>
            </div>
                <div class="panel-body">
                    <?php
                    echo '<p>' . '<input class="col-xs-12" type="text" id="letterSearch" placeholder="Search compounds...">' . '</p>';
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

                    echo '<p>' . 'Click on a letter/number below to show substances starting with that letter' . '</p>';
                    
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
                $chars = array_keys($data);
                foreach ($data as $char => $iarray) {
                    echo "<div id='" . $char . "' class='letter' style='display: none;'>";
                    echo "<ul class='list-unstyled' style='font-size: 16px;'>";
                foreach($iarray as $pid=>$substance) {
                    echo '<li>'.html_entity_decode($this->Html->link($substance,'/substances/view/'.$pid)).'</li>';
                }
                echo "</ul></div>";
                }
                ?>
            </div>
        </div>

    </div>
</div>

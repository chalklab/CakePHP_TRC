<style type="text/css">
    .showReports:hover {
        text-decoration: underline;
        cursor: pointer;
    }

</style>
<div class="row">
    <div class="col-sm-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"> Property Type</h2>
            </div>
            <div class="panel-body">
                <?php
                $propertytype = $data['Propertytype'];
                $ruleset = $data['Ruleset'];
                $property = $data['Property'];
                ?>


                <ul class="list-unstyled">
                    <li> <?php echo "Property Name: " . $property['name']; ?> </li>
                    <li> <?php echo "Code: " . $propertytype['code']; ?> </li>
                    <li> <?php echo "Ruleset: " . $ruleset['name']; ?> </li>
                    <li> <?php echo "Phase(s): " . ucfirst($propertytype['phases']); ?> </li>
                    <li> <?php echo "State(s): " . ucfirst($propertytype['states']); ?> </li>
                </ul>
            </div>
        </div>
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"> Systems</h2>
            </div>
            <div class="panel-body">
                <?php
                $chars = [];
                $sortedSystems = array();
                foreach ($data['System'] as $name => $system) {
                    $trimmed = trim($name);
                    $char = strtoupper(substr($trimmed, 0, 1));
                    if (!in_array($char, $chars)) {
                        $chars[] = $char;
                    }
                    if ($trimmed != "") {
                        $sortedSystems[$char][$name] = $system;
                    }
                }
                ?>
                <p>Search: <input type="text" id="letterSearch"></p>
                <p>Click on a letter/number below to show systems starting with that letter</p>
                <p>
                    <?php
                    foreach ($chars as $char) {
                        echo "<button type='button' class= 'btn btn-default btn-md' onclick=\"showletter('" . $char . "')\" 
                                style=\"display: inline;cursor: pointer; font-family:'Lucida Console', monospace\">";
                        echo "$char";
                        echo "</button>";
                    }
                    echo "</p>"; ?>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="panel panel-default">
            <div class="panel-body" style="max-height: 476px;overflow-y: scroll;">
                <?php
                $chars = array_keys($sortedSystems);
                foreach ($sortedSystems as $char => $iarray)
                {
                echo "<div id='" . $char . "' class='letter' style='display:none;'>";
                ?>
                <ul class="list-unstyled" style='font-size: 16px;'> <?php
                    foreach ($iarray as $id => $system)
                    {
                    $datasets = $system['Dataset'];
                    $reports = $system['Report'];
                    echo '<li><div class="showReports">' . $system['name'] . " (" . count($datasets);
                    ?>)
            </div>

            <div class="systemReports" style="display:none;">


                <?php
                foreach ($reports as $n => $report) {
                    $name = "";
                    if (isset($report['title']) && $report['title']) {
                        $name = $report['title'];
                    }
                    if (isset($report['Author']) && count($report['Author']) > 0) {
                        $name = "Report by ";
                        foreach ($report['Author'] as $i => $author) {
                            if ($i > 0) {
                                $name .= " and ";
                            }
                            $name .= utf8_encode($author['name']);
                        }
                    }


                    echo $this->Html->link($name, '/datasets/view/' . $datasets[$n]['id']) . "<br>";


                }
                echo '</div></li>';
                }
                ?>
                </ul></div>

            <?php
            }
            ?>
        </div>
        </div>
    </div>
</div>


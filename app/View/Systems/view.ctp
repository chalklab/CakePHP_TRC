<?php
$system=$data['System'];
$sets=$data['Dataset'];
$subs=$data['Substance'];
//$dataset=$data['Dataset'];
?>
<div class="row">
    <div class="col-xs-12 col-md-3">
        <h2>System</h2>
        <ul>
            <li><?php echo "Name: ".$system['name']; ?></li>
            <li><?php echo "Phase: ".$system['phase']; ?></li>
            <li><?php echo "Composition: ".$system['composition']; ?></li>
        </ul>
    </div>
    <div class="col-xs-12 col-md-9">
        <h2>Substances</h2>
        <?php
        foreach ($subs as $idx=>$sub) {
            ?>
            <div class="col-xs-12 col-md-4">
                <?php
                echo "<h4>".$this->Html->link($sub['name'],'/substances/view/'.$sub['id'])."</h4>";
                $chem=[];
                (isset($sub['name'])) ? $chem['name']=$sub['name'] : $chem['name']="Unknown compound";
                (isset($sub['Identifier'][0]['value'])) ? $chem['inchi']=$sub['Identifier'][0]['value'] : $chem['inchi']="";
                (isset($sub['Identifier'][1]['value'])) ? $chem['inchikey']=$sub['Identifier'][1]['value'] : $chem['inchikey']="";
                (isset($sub['casrn'])) ? $chem['casrn']=$sub['casrn'] : $chem['casrn']="";
                echo $this->element('molecule',['index'=>$idx,'fontsize'=>14,'height'=>200,'width'=>200, 'named'=>false]+$chem);
                ?>
            </div>
        <?php } ?>
    </div>
</div>
<div class="panel-group" style="padding-top: 20px;">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">Data Sets</h4>
        </div>
        <div class="list-group">
            <?php
            foreach($sets as $set) {
                $prop=$set['Sampleprop'][0]['property_name'];
                echo $this->Html->link($prop,'/datasets/view/'.$set['id'],["title"=>$prop,"class"=>"list-group-item"]);
            }
            ?>
        </div>
    </div>
</div>

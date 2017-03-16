<?php
$substance=$data['Substance'];
$identifiers=$data['Identifier'];
$system=$data['System'];
$idnicetext=['inchi'=>'InChI String','inchikey'=>'InChi Key','casrn'=>'CASRN','smiles'=>'SMILES','pubchemId'=>'PubChem ID','chemspiderId'=>'ChemSpider ID']?>

<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Substance</h2>
            </div>
            <div class="panel-body">
            <ul class="list-unstyled">
                <li><?php echo "Substance Name: ".$substance['name'];?></li>
                <li><?php echo "Formula: ".$substance['formula'];?></li>
                <li><?php echo "Molecular Weight: ".$substance['molweight']." g/mol";?></li>
                <?php
                foreach($identifiers as $identifier) {
                    if(isset($idnicetext[$identifier['type']])) {
                        echo "<li>".$idnicetext[$identifier['type']].": ".$identifier['value']."</li>";
                    }
                }
                ?>
            </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-8 col-sm-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Systems</h2>
            </div>
            <div class="list-group">
                <?php foreach($system as $sys) {
                    if(count($sys['Dataset'])>0) { ?>
                        <li class="list-group-item">
                            <div class="showReports" style="display:inline;cursor: pointer;">
                                <?php echo $sys['name']; ?> (<?php echo count($sys['Dataset']); ?>)
                            </div>
                            <div class="systemReports" style="display:none;">
                            <ul class="list-unstyled">
                                <?php foreach($sys['Dataset'] as $dataset) {
                                        echo "<li>".$this->Html->link($dataset['title'],'/datasets/view/'.$dataset['id'])."</li>";
                                    } ?>
                            </ul>
                        </div>
                    </li>
                    <?php
                    }
                } ?>
            </div>
        </div>
    </div>
</div>
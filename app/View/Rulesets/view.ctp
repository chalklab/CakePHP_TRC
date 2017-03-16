<?php
//pr($data);
$set=$data['Ruleset'];
$rules=$data['Rule'];
?>

<h2><?php echo $set['name']; ?>&nbsp;&nbsp;<small><?php echo $set['comment']; ?></small></h2>
<div class="row">
    <?php foreach($rules as $r) {
        $tmpl=$r['Ruletemplate'];unset($r['Ruletemplate']);
        $snips=$r['RulesRulesnippet'];unset($r['RulesRulesnippet']);
        ?>
    <div class="col-sm-10 col-md-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <?php echo $this->Html->link($r['name'],'/rules/view/'.$r['id'],['target'=>'_blank)']); ?>
                    <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>
                </h2>
            </div>
            <div class="panel-body">
                <div class="col-sm-5">
                    <p class="list-group-item bg-muted">Action: <?php echo $r['RulesRuleset']['action']; ?></p>
                </div>
                <?php if(!empty($r['url'])) { ?>
                <div class="col-sm-2">
                    <p class="list-group-item bg-muted">
                        <?php echo $this->Html->link('Regex101',$r['url'],['target'=>'_blank)']); ?>
                    </p>
                </div>
                <?php } ?>
                <div class="col-sm-3">
                    <p class="list-group-item bg-muted" title="Extraction mode ('capture','match', or 'mixed')">Mode: <?php echo $r['mode']; ?></p>
                </div>
                <div class="col-sm-2">
                    <p class="list-group-item bg-muted" title="Data layout (row or column)">Layout: <?php echo $r['layout']; ?></p>
                </div>
                <div class="col-sm-12" style="margin-top: 10px;">
                    <?php
                    $treg=$tmpl['regex'];
                    foreach($snips as $rsnip) {
                        $block=$rsnip['block'];
                        $snip=$rsnip['Rulesnippet'];
                        $treg=str_replace("@B".$block."@","<mark title='".$snip['name']."' alt='".$snip['name']."'>".htmlentities($snip['regex'])."</mark>",$treg);
                    }
                    echo $treg;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
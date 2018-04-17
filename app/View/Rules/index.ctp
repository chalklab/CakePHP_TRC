<?php
//pr($rules);exit;
if(!isset($rules)) { $rules=[]; }
?>
<h1>Rules
    <a href="add" class="btn btn-success btn-sm pull-right">Add Rule</a>
    <a href="/trc/ruletemplates/add" class="btn btn-default btn-sm pull-right">Add Rule Template</a>
    <a href="/trc/rulesnippets/add" class="btn btn-info btn-sm pull-right">Add Rule Snippet</a>
</h1>
<div class="col-lg-12" style="max-height: 650px;overflow-y:scroll;">
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Rule</th>
                <th>Regex</th>
                <th>Mode</th>
                <th>Layout</th>
                <th>Datasets</th>
            </tr>
        </thead>
        <?php foreach($rules as $idx=>$r) {
            $tmpl=$r['Ruletemplate'];
            $rule=$r['Rule'];
            $snips=$r['RulesRulesnippet'];
            $user=$r['User'];
            $sets=$r['Ruleset'];
            ?>
        <tr class="">
            <td><?php echo $this->Html->link($rule['name'],'/rules/view/'.$rule['id']); ?></td>
            <td>
                <?php
                $treg=$tmpl['regex'];
                foreach($snips as $rsnip) {
                    $block=$rsnip['block'];
                    $snip=$rsnip['Rulesnippet'];
                    $treg=str_replace("@B".$block."@","<mark title='".$snip['name']."' alt='".$snip['name']."'>".htmlentities($snip['regex'])."</mark>",$treg);
                }
                echo $treg;
                ?>
            </td>
            <td><?php echo $rule['mode']; ?></td>
            <td><?php echo $rule['layout']; ?></td>
            <td><?php echo count($sets); ?></td>
        </tr>
        <?php } ?>
    </table>
</div>
</div>
<?php
//pr($rule);exit;
if(!isset($rule)) { $data=[]; }
$r=$rule['Rule'];
$t=$rule['Ruletemplate'];
$u=$rule['User'];
$snips=$rule['RulesRulesnippet'];
?>
<h1>Rule <a href="../add" class="btn btn-success btn-sm pull-right">Add Rule</a>&nbsp;
    <a href="../index" class="btn btn-info btn-sm pull-right">List Rules</a>
</h1>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h2 class="panel-title"><?php echo $r['name']; ?></h2>
            </div>
            <div class="panel-body" style="font-size: 16px; ">
                <p>Rule Template: <?php echo $t['name']; ?> (<?php echo $t['regex']; ?>)<br/>
                    Regex:
                    <?php
                    $treg=$t['regex'];
                    foreach($snips as $rsnip) {
                        $block=$rsnip['block'];
                        $snip=$rsnip['Rulesnippet'];
                        ($rsnip['optional']=="yes") ? $x="?" : $x="";

                        $treg=str_replace("@B".$block."@","<mark title='".$snip['name']."' alt='".$snip['name']."'>".
                            htmlentities($snip['regex'].$x)."</mark>",$treg);
                    }
                    echo $treg;
                    ?><br/>
                    Example: <?php echo $r['example']; ?><br/>
                    Created by: <?php echo $u['fullname']; ?></p>
            </div>
        </div>
    </div>
</div>
<?php
if(!empty($r['url'])&&!is_null($r['url'])) {
    echo $this->element('regex101',['url'=>$r['url']]);
}
?>
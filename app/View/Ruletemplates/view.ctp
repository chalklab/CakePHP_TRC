<?php
// Variables from controller: $data
$t=$data['Ruletemplate'];
?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h3 class="panel-title">Rule Template - <?php echo $t['name']; ?></h3>
            </div>
            <div class="panel-body">
                Regex: <?php echo $t['regex']; ?><br />
                Blocks: <?php echo $t['blocks']; ?><br />
                Example: <?php echo $t['example']; ?><br />
                Comment: <?php echo $t['comment']; ?>
            </div>
        </div>
    </div>
</div>
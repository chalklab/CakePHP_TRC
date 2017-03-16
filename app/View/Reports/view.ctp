<?php //pr($data); ?>
<?php
$rep = $data['Report'];
$pub = $data['Publication'];
$set = $data['Dataset'];
$ref = $set['Reference'];
$sys = $set['System'];
?>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title"><?php echo $rep['title']; ?></h2>
            </div>
            <div class="panel-body">
                <ul>
                    <li><?php echo "Publication: ".$this->Html->link($pub['title'],"/publications/view/".$pub['id']); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h2 class="panel-title">System</h2>
            </div>
            <div class="panel-body">
                    <ul>
                        <li><?php echo $this->Html->link($sys['name'],"/systems/view/".$sys['id']); ?></li>
                    </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h2 class="panel-title">Data</h2>
            </div>
            <div class="panel-body">
                <ul>
                    <li><?php echo $this->Html->link($set['title'],"/datasets/view/".$set['id']); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-success">
            <div class="panel-heading">
                <h2 class="panel-title">Reference</h2>
            </div>
            <div class="panel-body">
                <?php
                $aus=$ref['authors'];
                if(stristr($aus,"}")) {
                    $a=json_decode($aus,true);
                    echo "<b>";
                    $cnt=count($a);
                    foreach($a as $i=>$au) {
                        echo implode(" ",$au);
                        if($i==$cnt-1) {
                            echo "; ";
                        } elseif($i==$cnt-2) {
                            echo " and ";
                        } else {
                            echo ", ";
                        }
                    }
                    echo "</b>";
                } else {
                    echo "<b>".$aus."</b>";
                }
                if(isset($ref['doi'])) {
                    echo $this->Html->link($ref["title"],'http://dx.doi.org/'.$ref['doi'])."<i> ".$ref['journal']."</i>. "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                } elseif (isset($ref['url'])) {
                    echo $this->Html->link($ref["title"], $ref['url'])."<i> ".$ref['journal']."</i>. "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                } else {
                    echo $ref["title"]."<i> ".$ref['journal']."</i>. "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                }
                ?>
            </div>
        </div>
    </div>
</div>

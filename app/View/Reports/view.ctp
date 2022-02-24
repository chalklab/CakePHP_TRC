<?php
$rep = $data['Report'];
$sets = $data['Dataset'];
$ref = $data['Reference'];
?>
<div class="row">
    <div class="col-sm-10 col-sm-offset-1">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h2 class="panel-title">Report</h2>
            </div>
            <div class="panel-body">
                <ul>
                    <li><?php echo $this->Html->link($rep['title'],"/references/view/".$ref['id']); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-sm-5 col-sm-offset-1">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h2 class="panel-title">System</h2>
            </div>
            <div class="list-group">
				<?php
				foreach($sets as $set) {
					$sys=$set['System'];
					echo $this->Html->link($sys['name'], "/systems/view/" . $sys['id'], ['class' => ['list-group-item list-group-item-small']]);
				} ?></div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="panel panel-warning">
            <div class="panel-heading">
                <h2 class="panel-title">Datasets</h2>
            </div>
            <div class="list-group">
                <?php
				foreach($sets as $set) {
					echo $this->Html->link($set['title'], "/datasets/view/" . $set['id'], ['class' => ['list-group-item list-group-item-small']]);
				} ?>
			</div>
        </div>
    </div>
    <div class="col-sm-10 col-sm-offset-1">
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
                    foreach($a as $i=>$aus) {
                        echo implode(" ",$aus);
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
				$jnl=$ref['Journal']['name'];
                if(isset($ref['doi'])) {
                    echo $this->Html->link($ref["title"],'http://dx.doi.org/'.$ref['doi'])."<i> ".$jnl."</i>. "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                } elseif (isset($ref['url'])) {
                    echo $this->Html->link($ref["title"], $ref['url'])."<i> ".$jnl."</i>. "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                } else {
                    echo $ref["title"]."<i> ".$jnl."</i>. "."<b>".$ref['year']."</b>, ".$ref['volume'].", ".$ref['startpage']." - ".$ref['endpage'];
                }
                ?>
            </div>
        </div>
    </div>
</div>

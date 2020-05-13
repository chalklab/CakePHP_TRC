<?php
$i=1;$size=4;
$jced=$data[2];
//pr($jced);exit;//
?>

<h2>Papers</h2>
<div class="row">
    <div class="panel-group" id="accordion">
        <?php
        if(!empty($jced['File'])) {
            foreach($jced['File'] as $file) {
                //pr($data); ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $i; ?>">
                                <?php
                                $count=count($file['Dataset']);
                                echo $file['title']." (".$count.")";
                                ?>
                            </a>
                        </h4>
                    </div>
                    <div id="collapse<?php echo $i; ?>" class="panel-collapse collapse<?php if($i==1) { echo " in"; }; ?>">
                        <div class="list-group" style="max-height: <?php echo ($size*40)+2; ?>px;overflow-y: scroll;font-size: 14px;">
                            <?php
                            foreach($file['Dataset'] as $set) {
                                $scount=count($set['Dataseries']);
                                $title=ucfirst($set['System']['name'])." (".$set['Sampleprop'][0]['property_name'].") [".$scount." Series]";
                                echo "<li>".$this->Html->link($title,'/datasets/view/'.$set['id'],["title"=>$title,"class"=>"list-group-item"])."</li>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php $i++;
            }
        } ?>
    </div>
</div>
<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title">Data</h2>
    </div>
    <div class="panel-body responsivediv600">
        <table class="table table-condensed table-striped">
            <thead>
            <?php
            $dataSize=$columns=0;
            for($i=0;$i<count($dpts[0]['Condition']);$i++) {
                echo "<tr>";
                foreach ($dpts as $series) {
                    $columns=count($series['Datapoint'][0]['Data']);
                    if(isset($series['Datapoint'][0]['Condition'])){
                        $columns+=count($series['Datapoint'][0]['Condition']);
                    }
                    echo "<td colspan='$columns'>";
                    echo $series['Condition'][$i]['Quantity']['symbol'] . " = ";
                    if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") {
                        echo $series['Condition'][$i]['number'];
                    } else{
                        echo ((float)$series['Condition'][$i]['number']);
                    }
                    if(isset($series['Condition'][$i]['Unit']['symbol'])) {
                        echo " " . $series['Condition'][$i]['Unit']['symbol'];
                    } else {
                        pr($series['Condition']);
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            // Print table Headers
            echo "<tr>";
            for($i=0;$i<count($dpts);$i++) {
                if (isset($dpts[$i]['Datapoint'][0])) {
					// conditions
					foreach ($dpts[$i]['Datapoint'][0]['Condition'] as $condition) {
						$compnum=$phase=null;
						if(isset($condition['Compohnent']['compnum'])) {
							$compnum=$condition['Compohnent']['compnum'];
						}
						if(!empty($condition['Phase'])) {
							$phase=" (".$condition['Phase']['Phasetype']['name'].")";
						}
						if(is_null($compnum)) {
							echo "<th title=\"".$condition['Quantity']['name'].$phase."\">";
							echo $condition['Quantity']['symbol'];
						} else {
							echo "<th title=\"".$condition['Quantity']['name'].$phase." (Component ".$compnum.")\">";
							echo $condition['Quantity']['symbol']."<sub>".$compnum."</sub>";
						}
						if($condition['Unit']['symbol']&&$condition['Unit']['symbol']!='1') {
							echo " (".$condition['Unit']['symbol'].")"; // print unit if not unitless
						}
						echo "</th>";
					}
					// data
					foreach ($dpts[$i]['Datapoint'][0]['Data'] as $data) {
						$compnum=$phase=null;
						if(isset($data['Compohnent']['compnum'])) {
							$compnum=$data['Compohnent']['compnum'];
						}
						//debug($data);exit;
						$sprop=$data['Sampleprop'];
						if($sprop['phase']) {
							$title=$data['Quantity']['name'].' ('.$sprop['phase'].')';
							$phase=' ('.$sprop['phase'].')';
						} else {
							$title=$data['Quantity']['name'];
							$phase='';
						}
						if(is_null($compnum)) {
							echo "<th title=\"".$title."\">";
							echo $data['Quantity']['symbol'];
						} else {
							echo "<th title=\"".$title." (Component ".$compnum.")\">";
							echo $data['Quantity']['symbol']."<sub>".$compnum."</sub>";
						}
						if($data['Unit']['symbol']&&$data['Unit']['symbol']!='1') {
							echo " (".$data['Unit']['symbol'].")"; // print unit if not unitless
						}
						echo "</th>";
					}
                }
                if (count($dpts[$i]['Datapoint']) > $dataSize) { //count how many rows
                    $dataSize=count($dpts[$i]['Datapoint']);
                }
            }
            echo "</tr>";
			?>
			</thead>
			<tbody>
			<?php
			for($i=0;$i<$dataSize;$i++) { //for each row of data we have
				echo "<tr>";
				foreach($dpts as $series) { //loop through the series
					if(isset($series['Datapoint'][$i])) { //if we have data
						foreach ($series['Datapoint'][$i]['Condition'] as $condition) {
							if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") { //if exponential is requested
								if($condition['number']!==null) {
									echo "<td>".$condition['number']; //if we didn't request exponential then convert to float and display
								} else {
									echo "<td>";
								}
								echo "</td>";
							} else {
								if($condition['number']!==null) {
									echo "<td title='".$condition['id']."'>"; //if we didn't request exponential then convert to float and display
									if($condition['number']>=1&&$condition['number']<10) {
										echo number_format($condition['number'],$condition['accuracy']-1);
									} elseif(abs($condition['number'])>=10&&abs($condition['number'])<100) {
										echo number_format($condition['number'],$condition['accuracy']-2);
									} elseif($condition['number']>=100&&$condition['number']<1000) {
										echo number_format($condition['number'],$condition['accuracy']-3);
									} elseif($condition['number']>=1000&&$condition['number']<10000) {
										echo number_format($condition['number'],$condition['accuracy']-4);
									} else {
										echo sprintf('%.'.($condition['accuracy']-1).'E',$condition['number']);
										//echo number_format($condition['number'],abs($condition['exponent'])+$condition['accuracy']-1);
									}
								} else {
									echo "<td>";
								}


							}
						}
						foreach ($series['Datapoint'][$i]['Data'] as $data) {
							echo "<td>";
							if (isset($_GET['numDisplay'])&&$_GET['numDisplay']=="exp") { //if exponential is requested
								if($data['number']!==null) {
									echo $data['number']; // if we didn't request exponential then convert to float and display
								}
								if((float)$data['error']!==0.0) { //if the error is not 0.0
									echo " ± " . $data['error']; //print error
								}
							} else {
								if($data['number']!==null) {
									if(abs($data['number'])>=1&&abs($data['number'])<10) {
										echo number_format($data['number'],$data['accuracy']-1);
									} elseif(abs($data['number'])>=10&&abs($data['number'])<100) {
										echo number_format($data['number'],$data['accuracy']-2);
									} elseif(abs($data['number'])>=100&&abs($data['number'])<1000) {
										echo number_format($data['number'],$data['accuracy']-3);
									} elseif(abs($data['number'])>=1000&&abs($data['number'])<10000) {
										echo number_format($data['number'],$data['accuracy']-4);
									} else {
										echo number_format($data['number'],abs($data['exponent'])+$data['accuracy']-1);
									}
								}
								if((float)$data['error']!==0.0) {//if the error is not 0.0
									echo " ± " . ((float)$data['error']);//print error
								}
							}
							echo "</td>";
						}
					} else {
						for($p=0;$p<$columns;$p++) {
							echo "<td></td>";
						}
					}
				}
				echo "</tr>";
			}
			?>
			</tbody>
		</table>
   </div>
</div>

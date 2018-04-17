<?php
pr($data);exit;
$system=$data['System'];
$substance=$data['Substance'];
//$dataset=$data['Dataset'];
?>
<div class="row">
    <div class="col-md-6">
        <h2>System</h2>
        <ul>
            <li><?php echo "Name: ".$system['name']; ?></li>
            <li><?php echo "Description: ".$system['description']; ?></li>
            <li><?php echo "Type: ".$system['type']; ?></li>
        </ul>
    </div>
    <div class="col-md-6">
        <h2>Substances</h2>
        <ul>
            <?php
            foreach ($substance as $sub) {
                echo "<li>".$this->Html->link($sub['name'],'/substances/view/'.$sub['id'])."</li>";
            }
            ?>
        </ul>
    </div>
</div>
<h2>Data Set</h2>
<div class="container">
     <table class="table table-striped">
        <thead>
        <tr>
            <th>Refractive Index</th>
            <th>Temperature (&#8451)</th>
            <th>Wavelength (nm)</th>
            <th>Reference</th>
        </tr>
        </thead>
         <tbody>
         <?php
         foreach($data['Data'] as $set) {
             echo "<tr>";
             echo "<td>".(float)$set['data']['n'];
             if (isset($set['data']['u']))
                 echo $set['data']['u'];
             echo "</td>";
             echo "<td>";
             if($set['condition']['n']){
                 echo (float)$set['condition']['n'];
             }else{
                 echo "-";
             }
             echo "</td>";
             echo "<td>".(float)$set['setting']['n'];
             echo "</td>";
             echo "<td>";
             echo $this->Html->link($set['ref'],'http://dx.doi.org/'.$set['ref']);
             echo "</td>";
             echo "</tr>";}
        ?>
        </tbody>
</table>

<p>&nbsp;</p>




<ul>
    <?php
    foreach($data as $d) {
        echo "<li>".$this->Html->link($d,'/datasets/view/'.$d,['target'=>'_blank'])."</li>";
    }
    ?>
</ul>
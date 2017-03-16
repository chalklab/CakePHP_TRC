<?php
//pr($data);
foreach($data as $pub) {
    if(!empty($pub['File'])) {
        foreach($pub['File'] as $file) {
            if(!empty($file['Dataset'])) {
                echo "<h4>".$pub['Publication']['title']."</h4>";
                echo "<ul>";
                foreach($file['Dataset'] as $set) {
                    echo "<li>".$this->Html->link($set['title'], '/datasets/view/' . $set['id'])."</li>";
                }
                echo "</ul>";
            }
        }
    }
}
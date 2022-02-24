<h2>Add Substance</h2>
<?php
    echo $this->Form->create('Substance',['action'=>'add']);
    echo $this->Form->input('name',['type'=>'text','label'=>'Name']);
    echo $this->Form->input('Identifier.type',['type'=>'hidden','value'=>'inchikey']);
    echo $this->Form->input('Identifier.value',['type'=>'text','label'=>'InChIKey']);
    echo $this->Form->end('Add Substance');
?>

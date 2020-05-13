<?php
if($this->Session->read('Auth.User'))
{
    ?>
    <div style="float: right;width: 200px;border-left: 1px solid #666;padding-left: 10px;"?>
    <h2>Navigation</h2>
    <ul>
        <li><a href="<?php echo $this->Html->url('/') ?>">Home</a></li>
        <?php if($this->Session->read('Auth.User.type')=="admin"){?>
            <li><a href="<?php echo $this->Html->url('/datasets/index'); ?>">Data Sets</a></li>
            <li><a href="<?php echo $this->Html->url('/files/index'); ?>">Files </a></li>
            <li><a href="<?php echo $this->Html->url('/textfiles/massprocess'); ?>">Mass Processing</a></li>
            <li><a href="<?php echo $this->Html->url('/files/processing'); ?>">Process Files </a></li>
            <li><a href="<?php echo $this->Html->url('/textfiles/index'); ?>">Text Files</a></li>
        <?php }?>
        <li><a href="<?php echo $this->Html->url('/propertytypes/index'); ?>">Property Types </a></li>
        <li><a href="<?php echo $this->Html->url('/publications/index'); ?>">Publications </a></li>
        <li><a href="<?php echo $this->Html->url('/substances/index'); ?>">Substances</a></li>
        <li><a href="<?php echo $this->Html->url('/systems/index'); ?>">Systems</a></li>
    </ul>
    </div>
    <?php
}
?>

<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container-fluid" id="navfluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar">
                <span class="sr-only">Toggle navigation</span>
            </button>
            <a class="navbar-brand" href="<?php echo $this->Html->url('/'); ?>"><b>ThermoMLConverter</b></a>
        </div>
        <div class="navbar-collapse collapse" id="navbar">
            <?php if ($this->Session->read('Auth.User')) { ?>
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                           aria-expanded="false" style="font-size: 18px;">Extracted Data<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><?php echo $this->Html->link('Properties', '/properties/index'); ?></li>
                            <li><?php echo $this->Html->link('Systems', '/systems/index'); ?></li>
                            <li><?php echo $this->Html->link('Substances', '/substances/index'); ?></li>
                            <li><?php echo $this->Html->link('Files', '/files/index'); ?></li>
                            <li><?php echo $this->Html->link('Text Files', '/textfiles/index'); ?></li>
                            <li><?php echo $this->Html->link('Publications', '/publications/index'); ?></li>
                            <li><?php echo $this->Html->link('Property Types', '/propertytypes/index') ?></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>
            <?php if ($this->Session->read('Auth.User.type') == 'admin'||$this->Session->read('Auth.User.type') == 'superadmin') { ?>
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                           aria-expanded="false" style="font-size: 18px;">Extraction System<span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><?php echo $this->Html->link('Rules', '/rules/index'); ?></li>
                            <li><?php echo $this->Html->link('Rulesets', '/rulesets/index'); ?></li>
                            <li><?php echo $this->Html->link('Rule Snippets', '/rulesnippets/index'); ?></li>
                            <li><?php echo $this->Html->link('Rule Templates', '/ruletemplates/index'); ?></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>
            <?php if ($this->Session->read('Auth.User.type') == 'admin'||$this->Session->read('Auth.User.type') == 'superadmin') { ?>
                <ul class="nav navbar-nav">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                           aria-expanded="false" style="font-size: 18px;">Admin <span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <li><?php echo $this->Html->link('Mass Process', '/textfiles/massprocess'); ?></li>
                            <li><?php echo $this->Html->link('Map', '/publications/map'); ?></li>
                        </ul>
                    </li>
                </ul>
            <?php } ?>
            <ul class="nav navbar-nav navbar-right">
                <div class="navbar-text text-danger">
                    <?php echo $this->Flash->render(); ?>
                </div>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" style="font-size: 18px;" aria-haspopup="true" aria-expanded="false">
                        <?php
                        if($this->Session->read('Auth.User')) {
                            echo $this->Session->read('Auth.User.fullname');
                        } else {
                            echo "My ThermoML";
                        }
                        ?>
                        <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <?php
                        if($this->Session->read('Auth.User')) {
                            echo "<li>".$this->Html->link('Logout','/users/logout')."</li>";
                        } else {
                            echo "<li>".$this->Html->link('Login','/users/login')."</li>";
                        }
                        ?>
                    </ul>
                </li>
            </ul>
        </div><!-- /.nav-collapse -->
    </div><!-- /.container-fluid -->
</nav>
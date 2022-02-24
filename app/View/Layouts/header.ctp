<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container-fluid" id="navfluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar">
                <span class="sr-only">Toggle navigation</span>
            </button>
            <a class="navbar-brand" href="<?php echo $this->Html->url('/'); ?>"><b>ThermoML Converter</b></a>
        </div>
        <div class="navbar-collapse collapse" id="navbar">
			<ul class="nav navbar-nav">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
					   aria-expanded="false" style="font-size: 18px;">Find by...<span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><?php echo $this->Html->link('Datasets', '/datasets/index'); ?></li>
						<li><?php echo $this->Html->link('Keywords', '/keywords/index'); ?></li>
						<li><?php echo $this->Html->link('References', '/references/index'); ?></li>
						<li><?php echo $this->Html->link('Substances', '/substances/index'); ?></li>
						<li><?php echo $this->Html->link('Systems', '/systems/index'); ?></li>
					</ul>
				</li>
			</ul>
            <ul class="nav navbar-nav navbar-right">
                <div class="navbar-text text-danger">
                    <?php echo $this->Flash->render(); ?>
                </div>
            </ul>
        </div><!-- /.nav-collapse -->
    </div><!-- /.container-fluid -->
</nav>

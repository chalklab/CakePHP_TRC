<footer class="footer">
    <div class="container">
        <div class ="col-sm-12">
            <p style="padding-top: 10px;font-size: 18px;">
                <?php
                echo "© Chalk Research Group and the ".$this->Html->link("University of North Florida",'http://www.unf.edu/',['target' =>'_blank'])." 2018-2022";
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Built with&nbsp;&nbsp;";
				echo $this->Html->link($this->Html->image('cake.icon.png',['height'=>'30px']),'https://book.cakephp.org/2/en/index.html',['target'=>'_blank','escape'=>false]);
				echo "&nbsp;&nbsp;";
				echo $this->Html->link($this->Html->image('bootstrap.svg',['height'=>'30px']),'https://getbootstrap.com/docs/3.3/',['target'=>'_blank','escape'=>false]);
				echo "&nbsp;&nbsp;";
				echo $this->Html->link($this->Html->image('jquery.jpg',['height'=>'30px']),'https://jquery.com/',['target'=>'_blank','escape'=>false]);
				?>
            </p>
        </div>
    </div>
</footer>

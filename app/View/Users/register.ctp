<h2 class="form-signin-heading text-center">Please register for an account</h2>
<?php
echo $this->Session->flash('auth');
echo $this->Form->create('User',['url'=>['controller'=>'users','action'=>'register'],'class'=>'form-signin','inputDefaults'=>['label'=>false,'div'=>false]]);
echo $this->Form->input('username', ['type'=>'text','class'=>'form-control','label'=>'Username']);
echo $this->Form->input('password', ['type'=>'password','class'=>'form-control','label'=>'Password']);
echo $this->Form->input('firstname', ['type'=>'text','class'=>'form-control','label'=>'First Name']);
echo $this->Form->input('lastname', ['type'=>'text','class'=>'form-control','label'=>'Last Name']);
echo $this->Form->input('email', ['type'=>'text','class'=>'form-control','label'=>'Email Address']);
?>
<br/>
<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
<?php echo $this->Form->end(); ?>

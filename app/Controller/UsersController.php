<?php

/**
 * Class UsersController
 * controller actions for admin functions
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class UsersController extends AppController
{
    public $uses=['User'];

	/**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('login','logout','register');
    }

	/**
	 * user login
	 * @return void
	 */
	public function login()
	{
		if($this->request->is('post')) {
			if($this->Auth->login()) {
				$this->Flash->set('Welcome!');
				$this->redirect($this->Auth->redirectUrl());
			} else {
				$this->Flash->set('Invalid username or password, try again.');
			}
		}
	}

    /**
     * user logout
	 * @return void
	 */
    public function logout()
    {
        $this->redirect($this->Auth->logout());
    }

    /**
     * add new users
	 * @return void
	 */
    public function register()
    {
        if($this->request->is('post')) {
            $this->User->create();
            if($this->User->save($this->request->data)) {
                $this->Flash->set('User has been created');
                $this->redirect(['action'=>'login']);
            } else {
                $this->Flash->set('User could not be created.');
            }
        }
    }

	// functions requiring login (not in Auth::allow)

	/**
     * view user information
     * @param null $id
	 * @return void
	 */
    public function view($id=null)
    {
        $this->User->id=$id;
        if(!$this->User->exists()) {
            throw new NotFoundException('Invalid user');
        }
        $this->set('user',$this->User->read(null,$id));
    }

    /**
     * delete users
     * @param null $id
	 * @return void
	 */
    public function delete($id=null)
    {
        $this->request->allowMethod('post');
        $this->User->id=$id;
        if(!$this->User->exists()) {
            throw new NotFoundException('Invalid user');
        }
        if($this->User->delete()) {
            throw new NotFoundException('Invalid user');
        }
        $this->Flash->set('User was not deleted');
        $this->redirect(['action'=>'index']);
    }

    /**
     * update user's information
     * @param null $id
	 * @return void
	 */
    public function update($id=null)
    {
        $this->User->id=$id;
        if(!$this->User->exist()) {
            throw new NotFoundException('Invalid user');
        }
        if($this->request->is('post') || $this->request->is('put')) {
            if($this->User->save($this->request->data)) {
                $this->Flash->set('User has been updated');
                $this->redirect(['action'=>'index']);
            }
			$this->Flash->set('User could not be updated, please try again.');
        } else {
		    unset($this->request->data['User']['password']);
			$this->request->data=$this->User->read(null,$id);
		}
    }

}

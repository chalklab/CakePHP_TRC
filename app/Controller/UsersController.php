<?php

/**
 * Class UsersController
 */
class UsersController extends AppController {

    public $uses=array('User');

    /**
     * beforeFilter function
     */
    public function beforeFilter()
    {
        parent::beforeFilter();
        $this->Auth->allow('login','register','logout','webhook');
    }

    /**
     * User login
     */
    public function login()
    {
        if($this->request->is('post')) {
            $passwordTest = $this->User->find('first',['conditions'=>['Username'=>$this->request['data']['User']['username']],'recursive'=>1]);
            $passwordHasher = new SimplePasswordHasher();
            //used to convert a simple SHA1 password into the newer blowfish password
            if($passwordHasher->check($this->request['data']['User']['password'],$passwordTest['User']['password'])){
                $passwordTest['User']['password'] = $this->request['data']['User']['password'];
                $this->User->save($passwordTest);
                echo "Saving USer";
            }
            if($this->Auth->login()) {
                $this->Flash->set('Welcome, '. $this->Auth->user('username'));
                return $this->redirect($this->Auth->redirectUrl());
            } else {
                $this->Flash->set('Invalid username or password, try again.');
            }
        }
    }

    /**
     * User logout
     */
    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }

    /**
     * Add new users
     */
    public function register()
    {
        if($this->request->is('post'))
        {
            $this->User->create();
            $data=$this->request->data;
            if($this->User->save($data)) {
                $this->Session->setFlash('User has been created');
                $this->redirect(['action'=>'login']);
            } else {
                $this->Session->setFlash('User could not be created.');
            }
        }

    }

    /**
     * View user information
     * @param null $id
     */
    public function view($id=null)
    {
        $this->User->id=$id;
        if(!$this->User->exists()) {
            throw new NotFoundException(_('Invalid user'));
        }
        $this->set('user',$this->User->read(null,$id));
    }

    /**
     * Delete  users
     * @param null $id
     * @return mixed
     */
    public function delete($id=null)
    {
        $this->request->allowMethod('post');
        $this->User->id=$id;
        if(!$this->User->exists()) {
            throw new NotFoundException(_('Invalid user'));
        }

        if($this->User->delete()) {
            throw new NotFoundException(_('Invalid user'));
        }

        $this->Session->setFlash(_('User was not deleted'));
        return $this->redirect(['action'=>'index']);
    }

    /**
     * Update user's information
     * @param null $id
     * @return mixed
     */
    public function update($id=null)
    {
        $this->User->id=$id;
        if(!$this->User->exist()) {
            throw new NotFoundException(_('Invalid user'));
        }
        if($this->request->is('post') || $this->request->is('put')) {
            if($this->User->save($this->request->data))
            {
                $this->Session->setFlash(_('User has been updated'));
                return $this->redirect(['action'=>'index']);
            }
            $this->Session->setFlash(_('User could not be updated, please try again.'));
        } else {
            $this->request->data=$this->User->read(null,$id);
            unset($this->request->data['User']['password']);
        }
    }

    /**
     * Index of users (admin only)
     */
    public function index()
    {
        // May need later...
    }

    public function webhook($random)
    {
        /**
         * This script is for easily deploying updates to Github repos to your local server. It will automatically git clone or
         * git pull in your repo directory every time an update is pushed to your $BRANCH (configured below).
         *
         * INSTRUCTIONS:
         * 1. Edit the variables below
         * 2. Upload this script to your server somewhere it can be publicly accessed
         * 3. Make sure the apache user owns this script (e.g., sudo chown www-data:www-data webhook.php)
         * 4. (optional) If the repo already exists on the server, make sure the same apache user from step 3 also owns that
         *    directory (i.e., sudo chown -R www-data:www-data)
         * 5. Go into your Github Repo > Settings > Service Hooks > WebHook URLs and add the public URL
         *    (e.g., http://example.com/webhook.php)
         *
         **/

        // Set Variables
        $LOCAL_ROOT         = "/home/schalk/data";
        $LOCAL_REPO_NAME    = "springer";
        $LOCAL_REPO         = "{$LOCAL_ROOT}/{$LOCAL_REPO_NAME}";
        $REMOTE_REPO        = "git@github.com:stuchalk/Springer.git";
        $BRANCH             = "master";

        if ( $_POST['payload'] ) {
            // Only respond to POST requests from Github

            if( file_exists($LOCAL_REPO) ) {

                // If there is already a repo, just run a git pull to grab the latest changes
                shell_exec("cd {$LOCAL_REPO} && git pull");

                die("done " . mktime());
            } else {

                // If the repo does not exist, then clone it into the parent directory
                shell_exec("cd {$LOCAL_ROOT} && git clone {$REMOTE_REPO}");

                die("done " . mktime());
            }
        } else {
            exit;
        }
    }

}

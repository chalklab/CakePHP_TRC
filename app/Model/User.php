<?php
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');

/**
 * Class User
 * User Model
 */
class User extends AppModel
{

    public $name="User";

    public $virtualFields=['fullname'=>'CONCAT(firstname," ",lastname)'];

    /**
     * Validate user/password
     * @var array
     */
    public $validate = [
        'username' => [
            'required' => [
                'rule' => ['notBlank'],
                'message' => 'A username is required'
            ]
        ],
        'password' => [
            'required' => [
                'rule' => ['notBlank'],
                'message' => 'A password is required'
            ]
        ]
    ];

    /**
     * BeforeSave
     * @param array $options
     * @return bool
     */
    public function beforeSave($options = []) {
        if (!empty($this->data[$this->alias]['password'])) {
            $passwordHasher = new BlowfishPasswordHasher();
            $this->data[$this->alias]['password'] = $passwordHasher->hash(
                $this->data[$this->alias]['password']
            );
        }
        return true;
    }

}

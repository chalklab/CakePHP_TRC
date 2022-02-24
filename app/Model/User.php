<?php
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
App::uses('BlowfishPasswordHasher', 'Controller/Component/Auth');

/**
 * Class User
 * model for the users table
 * @author Chalk Research Group <schalk@unf.edu>
 * @version 2/28/22
 */
class User extends AppModel
{
	// create additional 'virtual' fields built from real fields
    public $virtualFields=['fullname'=>'CONCAT(firstname," ",lastname)'];

    /**
     * validate user/password
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
     * beforeSave
     * @param array $options
     * @return bool
     */
    public function beforeSave($options = []): bool {
        if (!empty($this->data[$this->alias]['password'])) {
            $passwordHasher = new BlowfishPasswordHasher();
            $this->data[$this->alias]['password'] = $passwordHasher->hash(
                $this->data[$this->alias]['password']
            );
        }
        return true;
    }

}

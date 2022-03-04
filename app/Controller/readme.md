## Controllers in CakePHP

Controllers are logic coordinators.  They receive a request from the webserver and direct execution of the PHP scripts
to a particular (public) function in the file.  For instance in the `FilesController.php` file, the `public function most`
is run when the URL sent to the server is `https://example.org/files/most`.

If a function is not for general use, i.e., only for authorized users, then name of the function must be identified
as such, which is implemented using the Auth (Authorization ) component. As shown below only functions named in the
`$this->Auth->allow function` may be accessed without logging in (you can also use `$this->Auth->deny`).
```
/**
* function beforeFilter
*/
public function beforeFilter()
{
	parent::beforeFilter();
	$this->Auth->allow('index','view','most');
}
```

## Controllers in CakePHP

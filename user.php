<?

class User extends DBModel
{
    protected $primaryKey = "id";
    protected $table = 'user';

    protected $dataMap = array(
        'id' => 'id',
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'email' => 'email');

    var $id;
    var $firstName;
    var $lastName;
    var $email;
}
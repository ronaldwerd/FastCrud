FastCrud
========

A PHP Crud library for strongly typed models and easy crud.

If you are not using a framework such as code igniter, this framework allows you to create strongly
typed objects for which bind to database rows.

Features are:

(C)reate: Insert a new record
(R)ead: Get a single record by its primary key or read in multiple records through a search clause
(U)pdate: Update an existing record. If the record has a primary key set then update will be called.
(D)elete: Delete a record by it's primary key.

Example:

<pre>
<?
require_once 'dbmodel.php';

class User extends DBModel
{
    /*
     * Defines the primary key and the table for which this type of model resides in
     */

    protected $primaryKey = "id";
    protected $table = 'user';

    /*
     * This maps the database row names to the model property names.
     */
     
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

/*
 * Saving a record.
 */
 
$u = new User();
$u->email = "youremail@yourdomain.com";
$u->firstName = "Ronald";
$u->lastName = "Premier";
$id = $u->save(); // Returns the database generated Id

/*
 * Reading a record
 */

$u = User::get($id);

/*
 * Finding a record
 * Simply populate an object with the properties you wish search for and 
 * object::findAll($obj) will attempt to find a match.
 * Each result found is an instiated object of model you created.
 */ 

$search = new User();
$search->email = 'youremail@yourdomain.com';
$results = User::findAll($search);


// More documentation to follow later.

</pre>




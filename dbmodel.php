<?

class DBModel
{
    protected static $dbh;

    protected $table;
    protected $primaryKey;

    protected $dataMap;
    protected static $lastInsertedId;


    function __construct()
    {
        self::$dbh = new PDO(DSN, DB_USER, DB_PASS, array(PDO::ATTR_PERSISTENT => true));
    }

    function __destruct()
    {

    }

    public function getLastInsertedId() {
        return self::lastInsertedId;
    }

    protected static function getByDistinct($columnName, $whereClause = null)
    {
        $className = get_called_class();

        $object = new $className();

        $values = array();
        $whereClauseSql = "";

        if (is_array($whereClause)) {
            $whereClauseSql = "WHERE ";

            foreach ($whereClause as $k => $v) {
                $values[] = $v;
                $whereClauseSql .= $k . "=? ";
            }
        }

        $sql = "SELECT DISTINCT " . $columnName . " FROM " . $object->table . " " . $whereClauseSql;
        $stmt = self::$dbh->prepare($sql);
        $stmt->execute($values);
        $stmt->setAttribute(PDO::FETCH_ASSOC, true);

        $results = array();

        while ($r = $stmt->fetch()) {
            $results[] = $r[$columnName];
        }

        return $results;
    }

    protected function generateColumns()
    {
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            print $prop->getName() . "\n";
        }
    }

    function execute($sql)
    {
        $result = self::$dbh->query($sql);

        if($result != false)
        {
            $rows = $result->fetchAll();
            $this->$lastInsertedId = self::$dbh->lastInsertId();
            return $rows;
        }

        return false;
    }


    static function executePrepared($sql, $vars = null)
    {
        $stmt = self::$dbh->prepare($sql);

        if(is_array($vars))
        {
            foreach($vars as $key => $value)
            {
                $stmt->bindValue(":".$key, $value);
            }
        }

        $stmt->execute();

        $rows = $stmt->fetchAll();

        self::$lastInsertedId = self::$dbh->lastInsertId();
        return $rows;
    }

    protected function generateInsertSql($table, $data)
    {

        $sql = "INSERT INTO ".$table." (";

        $params = "";
        $values = "";

        foreach($data as $key => $value)
        {
            $params .= $key.", ";
            $values .= ":".$key.", ";
        }

        $params = substr($params, 0, strlen($params) - 2);
        $values = substr($values, 0, strlen($values) - 2);
        $sql .= $params.") VALUES (".$values.")";

        return $sql;
    }

    protected function generateUpdateSql($table, $data, $clause)
    {
        $sql = "UPDATE ".$table." SET ";

        $params = "";
        $whereParams = "";

        /*
         * Do not duplicate data in the where clause to the row update.
         */

        $similarKeys = array_keys(array_intersect_key($data, $clause));

        foreach($similarKeys as $k)
        {
            unset($data[$k]);
        }

        foreach($data as $key => $value)
        {
            $params .= $key."=:".$key.", ";
        }

        $params = substr($params, 0, strlen($params) - 2);
        $sql .= $params;

        foreach($clause as $key => $value)
        {
            $whereParams .= $key."=:".$key.", ";
        }

        $whereParams = substr($whereParams, 0, strlen($whereParams) - 2);
        $sql .=" WHERE ".$whereParams;

        return $sql;
    }

    protected function generateSelectSql($table, $columns, $andClause = null, $order = null, $orderType = "ASC")
    {
        $whereParams = "";
        $columnsSelected = "";

        if(is_array($columns))
        {
            foreach($columns as $columnName)
                $columnsSelected .= $columnName.", ";

            $columnsSelected = substr($columnsSelected, 0, strlen($columnsSelected) - 2);
        }
        else
        {
            throw Exception("Columns must be an array");
        }


        $sql = "SELECT ".$columnsSelected." FROM ".$table;

        if(is_array($andClause) && $andClause != null)
        {
            foreach($andClause as $key => $value)
            {
                $whereParams .= $key."=:".$key." AND ";
            }

            $whereParams = substr($whereParams, 0, strlen($whereParams) - 5);
            $sql .=" WHERE ".$whereParams;
        }

        if(is_string($order) && $order != null)
        {
            $sql .=" ORDER BY ".$order." ".$orderType;
        }

        return $sql;
    }

    protected static function generateDeleteSql($table, $andClause)
    {
        $sql = "DELETE FROM ".$table." WHERE ";

        $whereClause = "";


        foreach($andClause as $key => $value)
        {
            $whereClause .= $key."=:".$key." AND ";
        }

        $whereClause = substr($whereClause, 0, strlen($whereClause) - 4);

        $sql .= $whereClause;
        return $sql;
    }

    protected function _loadDataRow($data)
    {
        if($this->dataMap != null && is_array($this->dataMap))
        {
            foreach($this->dataMap as $key => $value)
            {
                if(property_exists($this, $value))
                {
                    $this->{$value} = $data[$key];
                }
            }
        }

        return false;
    }

    protected function _convertToArray()
    {
        if($this->dataMap != null && is_array($this->dataMap))
        {
            $dataRow = array();

            foreach($this->dataMap as $key => $value)
            {
                if(property_exists($this, $value))
                {
                    $dataRow[$key] = $this->{$value};
                }
            }

            return $dataRow;
        }

        return false;
    }

    public function save()
    {
        $id = null;

        $data = $this->_convertToArray();
        $objectId = $this->{$this->primaryKey};

        if($objectId != null)
        {
            $map = array_flip($this->dataMap);
            $primaryKeyColumn = $map[$this->primaryKey];

            $sql = $this->generateUpdateSql($this->table, $data, array($primaryKeyColumn => $objectId));
            $this->executePrepared($sql, $data);
            $id = $this->{$this->primaryKey};
        }
        else
        {
            $sql = $this->generateInsertSql($this->table, $data);
            $this->executePrepared($sql, $data);
            $id = $this->lastInsertedId;
        }

        return $id;
    }

    public function delete($id)
    {

    }

    public static function get($id)
    {
        $className = get_called_class();

        $object = new $className();

        $clause = array($object->primaryKey => $id);

        $sql = self::generateSelectSql($object->table, array_keys($object->dataMap), $clause);
        $data = $object->executePrepared($sql, $clause);

        $object->_loadDataRow($data[0]);

        return $object;
    }

    public static function findAll($clause = null)
    {
        $sqlClause = null;

        if(is_object($clause))
        {
            $sqlClause = array();

            foreach($clause->dataMap as $k => $v)
            {

                if($clause->{$v} != null) {
                    $sqlClause[$k] = $clause->{$v};
                }
            }
        }

        $className = get_called_class();

        $object = new $className();
        $sql = self::generateSelectSql($object->table, array_keys($object->dataMap), $sqlClause);
        unset($object);

        $data = self::executePrepared($sql, $sqlClause);
        $objectList = array();

        foreach($data as $row)
        {
            $object = new $className();
            $object->_loadDataRow($row);
            $objectList[] = $object;
        }

        return $objectList;
    }
}

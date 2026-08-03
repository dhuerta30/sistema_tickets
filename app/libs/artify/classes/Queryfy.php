<?php
require dirname(__DIR__, 4) . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Queryfy
{

    public $columns = "*";              //columns of table
    public $openBrackets = "";          //start bracket for select query, set this to '(' for starting bracket
    public $closedBrackets = "";        //close bracket for select query, set this to ')' for closing bracket
    public $dbTransaction = false;      // set true for starting transactions
    public $commitTransaction = false;  //set true for commiting transactions
    public $andOrOperator = "AND";      // whether to use "AND" or "OR" operator
    public $backtick = "`";             // Using backtics 
    public $orderByCols;                // Set order by columns in array format
    public $limit;                      // set limit like "0,10"
    public $groupByCols;                // Set group by columns in array format
    public $havingCondition;            // Set having condition
    public $totalRows;                  // Total rows returned 
    public $lastInsertId;               // Get last insert id
    public $error;                      // Get errors
    public $displayError = false;       // Set true to echo the errors else it will be stored in error variable
    public $rowsChanged;                // rows changed/affected during insert, update and delete operation
    public $dbSQLitePath;               // Path for sqlite
    public $fetchType;                  // set different ways of fetching data
    private $ArtifyErrorCtrl;            // set error controller

    private $whereCondition = "";
    private $dbObj = NULL;
    private $connectionStatus = 0;
    private $sql;
    private $dbType;
    private $dbHostName;
    private $dbName;
    private $dbUserName;
    private $dbPassword;
    private $dbRollBack;
    private $dbTansactionStatus;
    private $characterSet;
    private $values = array();
    private $subSQL = "";
    private $joinString = "";
    private $joinTables;

    /* configuración PHPMailer */
    public static $mail_host = '';
    public static $mail_port = '';
    public static $smtp_auth = '';
    public static $username = '';
    public static $password = '';
    public static $emailfrom = '';
    public static $smtpsecure = '';

    protected $send_email_public = array();

    /*     * ******************* File related variables - use this for various file operations ******************************** */
    public $fileOutputMode = "browser";     // if fileOutputMode="browser", then it will ask for download else it will save
    public $checkFileName = true;           // If true then it checks for illegal character in file name
    public $checkFileNameCharacters = true; // If true then it checks for no. of character in file name, should be less than 255
    public $replaceOlderFile = false;       // If true then it will replace the older file if present at same location
    public $uploadedFileName = "";          // Name of new uploaded file 
    public $fileUploadPath = "../upload/";  // Path of the uploaded file
    public $maxSize = 1000000000000000;               // Max size of file allowed for file upload
    public $fileSavePath = "../save/";     // Default path for saving generated file 
    public $pdfFontName = "helvetica";      // font name for pdf
    public $pdfFontSize = "8";              // font size for pdf
    public $pdfFontWeight = "B";            // font weight for pdf
    public $pdfAuthorName = "Author Name";  // Author name for pdf
    public $pdfSubject = "PDF Subject Name"; // Subject name for pdf
    public $excelFormat = "2007";           // Set format of excel generate (.xlsx or .xls)
    public $outputXML = "";                // Display xml table generated  
    public $rootElement = "root";          // Root Element for the xml
    public $encoding = "utf-8";            // Encoding for the xml file
    public $rowTagName = "";               // If this is set to some valid xml tag name then generated xml will contain this tagname after each subarray of two dimensional array
    public $append = false;                //If true, then will append in the existing file rather than creating a new one(you must set $existingFilePath variable to the location of the existing file)
    public $existingFilePath;              // Complete path of existing file including name to use in append operation
    public $delimiter = ",";               // Delimiter to be used in handling csv files, default is ','
    public $enclosure = '"';               // Enclosure to be used in handling csv files, default is '"' 
    public $isFile = false;                // Whether the supplied xml or html source is file or not
    public $useFirstRowAsTag = false;
    public $outputHTML = "";               // Display html table generated  
    public $tableCssClass = "tblCss";      // css class for the html table
    public $trCssClass = "trCss";          // css class for the html table row (tr)   
    public $htmlTableStyle = "";           // css style for the html table
    public $htmlTRStyle = "";              // css style for the html table row (tr)
    public $htmlTDStyle = "";              // css style for the html table col (td)

    /*     * ****************************************** PDO Functions ********************************************************* */

    /**
     * Constructor 
     * 
     */
    public function __construct()
    {
    }

    public function setErrorCtrl(ArtifyErrorCtrl $ArtifyErrorCtrl)
    {
        $this->ArtifyErrorCtrl = $ArtifyErrorCtrl;
    }

    /**
     * Connect to different types of database(mysql, pgsql, sqlite etc) based on the connection parameter set in config.
     * @param   string   $hostName              Hostname/servername
     * @param   string   $userName              username
     * @param   string   $password              password
     * @param   string   $databaseName          database name
     * @param   string   $dbType                type of database, (mysql, sqlite, pgsql)
     * @param   string   $charset               charset
     */
    public function connect($hostName, $userName, $password, $databaseName, $dbType = "mysql", $charset = "utf8")
    {
        $this->dbType = strtolower($dbType);
        $this->dbHostName = $hostName;
        $this->dbName = $databaseName;
        $this->dbUserName = $userName;
        $this->dbPassword = $password;
        $this->characterSet = $charset;
        $this->dbObj = NULL;
        $this->connectionStatus = 0;

        if ($this->dbType === "sqlite" && !empty($databaseName))
            $this->dbSQLitePath = $databaseName;

        switch ($this->dbType) {
            case "mysql": $this->connectMysql();
                break;
            case "pgsql": $this->connectPGsql();
                break;
            case "sqlite": $this->connectSQLite();
                break;
            case "sqlserver": $this->connectSQLServer();
                break;
            case "oracle": $this->connectOracle();
                break;
            default: $this->setErrors("Please enter valid database type option. Available options are mysql, pgsql and sqlite");
                return false;
        }

        if ($this->connectionStatus == 1) {
            $this->dbObj->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $this->setErrors("Not able to connect to database. Please check details");
            return false;
        }

        if ($this->characterSet && $this->dbType == "mysql")
            $this->dbObj->exec("set names '" . $this->characterSet . "'");

        return $this;
    }

    /**
     * Connect to mysql database based on the connection parameter.
     *
     */
    private function connectMysql()
    {
        try {
            $opts = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
            if (getenv('DB_SSL_CA')) {
                $opts[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA');
            }
            $this->dbObj = new PDO("mysql:host=$this->dbHostName;dbname=$this->dbName", $this->dbUserName, $this->dbPassword, $opts);
            $this->connectionStatus = 1;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Connect to Oracle database based on the connection parameter.
     *
     */
    private function connectOracle(){
        try {
            $server         = $this->dbHostName;
            $db_username    = $this->dbUserName;
            $db_password    = $this->dbPassword;
            $service_name   = "ORCL";
            $sid            = "ORCL";
            $port           = 1521;
            $dbnamedns = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = $server)(PORT = $port)) (CONNECT_DATA = (SERVICE_NAME = $service_name) (SID = $sid)))";

            $this->dbObj = new PDO( "oci:dbname=" . $dbnamedns . ";charset=utf8", $db_username, $db_password, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));   
            $this->connectionStatus = 1;
            $this->backtick = "";
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Connect to pgsql database based on the connection parameter.
     *
     */
    private function connectPGsql()
    {

        try {
            $this->dbObj = new PDO("pgsql:dbname=$this->dbName;host=$this->dbHostName;user=$this->dbUserName;password=$this->dbPassword");
            $this->connectionStatus = 1;
            $this->backtick = "";
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Connect to sqlite database based on the connection parameter.
     *
     */
    private function connectSQLite()
    {

        try {
            $this->dbObj = new PDO("sqlite:$this->dbSQLitePath");
            $this->connectionStatus = 1;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Connect to sql server database based on the connection parameter.
     *
     */
    private function connectSQLServer()
    {

        try {
            $this->dbObj = new PDO("sqlsrv:server=$this->dbHostName;Database=$this->dbName", $this->dbUserName, $this->dbPassword);
            $this->connectionStatus = 1;
            $this->backtick = "";
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Commits result to the database, if there is no rollback
     */
    public function commitTransaction()
    {
        try {
            if ($this->dbObj != NULL) {
                $this->dbObj->commit();
                $this->beginTransaction = false;
            }
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Insert new records in a table using associative array. Instead of writing long insert queries, you needs to pass
     * array of keys(columns) and values(insert values). This function will automatically create query for you and inserts data.
     * @param   string   $dbTableName              The name of the table to insert new records.
     * @param   array    $insertData               Associative array with key as column name and values as column value.
     *
     */
    public function insert($dbTableName, $insertData)
    {

        try {
            if ($this->dbTransaction && $this->dbRollBack == true)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }

            $this->sql = $this->getInsertQuery($dbTableName, $insertData);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute($this->values);
            $this->rowsChanged = $stmt->rowCount();
            $this->lastInsertId = $this->dbObj->lastInsertId();
            $this->resetAll();
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }

            $this->setErrors($e->getMessage());
        }
    }


    public function send_email_public($to, $from, $file = null, $subject = null, $message = '', $html = true)
    {
        //self::sendMail($subject, $to, $message, "sales@xcrud.com", $file);

        require_once(dirname(__FILE__) . "/library/mailer/src/Exception.php");
        require_once(dirname(__FILE__) . "/library/mailer/src/PHPMailer.php");
        require_once(dirname(__FILE__) . "/library/mailer/src/SMTP.php");

        define("HOST", PDOModel::$mail_host);
        define("PORT", PDOModel::$mail_port);
        define("SMTPAUTH", PDOModel::$smtp_auth);
        define("USERNAME", PDOModel::$username);
        define("PASSWORD", PDOModel::$password);
        define("SMTPSECURE", PDOModel::$smtpsecure);

        $mail = new PHPMailer(true);
        //Server settings
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = "smtp.gmail.com";                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                //Enable SMTP authentication
        $mail->Username   = "daniel.telematico@gmail.com";                    //SMTP username
        $mail->Password   = "zdkbgrxsnjmyyzrj";                            //SMTP password
        $mail->SMTPSecure = "tls";            //Enable implicit TLS encryption
        $mail->Port       = 587;                                  //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        $mail->CharSet = 'UTF-8';
        //Recipients
        $mail->setFrom($from, 'Procedimiento');
        $mail->addAddress($to, "");
        //$mail->addAttachment($file, $file);

        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->Send();
        return $mail;
    }

    /**
     * Insert batch of new records in a table using associative array. Instead of writing long insert queries, you needs to pass
     * array of keys(columns) and values(insert values). This function will automatically create query for you and inserts data.
     * @param   string   $dbTableName              The name of the table to insert new records.
     * @param   array    $insertBatchData               Array of Associative array with key as column name and values as column value.
     *
     */
    public function insertBatch($dbTableName, $insertBatchData)
    {

        try {
            if ($this->dbTransaction && $this->dbRollBack == true)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }

            foreach ($insertBatchData as $insertData) {
                $this->sql = $this->getInsertQuery($dbTableName, $insertData);
                $stmt = $this->dbObj->prepare($this->sql);
                $stmt->execute($this->values);
                $this->rowsChanged += $stmt->rowCount();
                $this->lastInsertId = $this->dbObj->lastInsertId();
                $this->resetAll();
            }
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }

            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Insert new records in a table using associative array. MySQL INSERT ON DUPLICATE KEY UPDATE statement 
     * to update data if a duplicate in the UNIQUE index or PRIMARY KEY error occurs when you insert a row into a table.
     * @param   string   $dbTableName              The name of the table to insert new records.
     * @param   array    $insertData               Associative array with key as column name and values as column value.
     * @param   array    $updateData               Associative array with key as column name and values as column value.
     *
     */
    public function insertOnDuplicateUpdate($dbTableName, $insertData, $updateData)
    {

        try {
            if ($this->dbTransaction && $this->dbRollBack == true)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }

            $this->sql = $this->getInsertQuery($dbTableName, $insertData);
            $this->sql .= " ON DUPLICATE KEY UPDATE ";
            $this->sql .= $this->getInsertOnDuplicateUpdateQuery($dbTableName, $updateData);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute($this->values);
            $this->rowsChanged = $stmt->rowCount();
            $this->lastInsertId = $this->dbObj->lastInsertId();
            $this->resetAll();
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }

            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Update existing records in a table using associative array. Instead of writing long update queries, you needs to pass
     * array of keys(columns) and values(update values) and set associative array of where conditions with keys as 
     * columns and value as column value.
     * This function will automatically create query for you and updates data.
     * Note: The WHERE clause specifies which record or records that should be updated. You can set where condition by calling
     * where function. Please note that without where condition, all rows will be updated.
     * @param   string   $dbTableName                  The name of the table to update old records.
     * @param   array    $updateData                  Associative array with key as column name and values as column value.
     *
     */
    public function update($dbTableName, $updateData)
    {

        try {
            if ($this->dbTransaction && $this->dbRollBack == true)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }
            $this->sql = $this->getUpdateQuery($dbTableName, $updateData);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute($this->values);
            $this->rowsChanged = $stmt->rowCount();
            $this->resetAll();
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Update batch of existing records in a table using array of associative array. Instead of writing long update queries, you needs to pass
     * array of keys(columns) and values(update values) and set associative array of where conditions with keys as 
     * columns and value as column value.
     * This function will automatically create query for you and updates data.
     * Note: The WHERE clause specifies which record or records that should be updated. You can set where condition by passing
     * where array of associative array. Please note that without where condition, all rows will be updated.
     * @param   string   $dbTableName                      The name of the table to update old records.
     * @param   array    $updateBatchData                  Array of Associative array with key as column name and values as column value.
     * @param   array    $where                            Array of Associative array with key as column name and values as column value.
     *
     */
    public function updateBatch($dbTableName, $updateBatchData, $where = array())
    {

        try {
            if ($this->dbTransaction && $this->dbRollBack == true)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }
            $loop = 0;
            foreach ($updateBatchData as $updateData) {
                if (isset($where[$loop])) {
                    $operator = isset($where[$loop][2]) ? $where[$loop][2] : "=";
                    $this->where($where[$loop][0], $where[$loop][1], $operator);
                }
                $this->sql = $this->getUpdateQuery($dbTableName, $updateData);
                $stmt = $this->dbObj->prepare($this->sql);
                $stmt->execute($this->values);
                $this->rowsChanged = $stmt->rowCount();
                $this->resetAll();
                $loop++;
            }
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Delete records in a table using associative array. Instead of writing long delete queries, you needs to set
     * where condition using where function.
     * This function will automatically create query for you and deletes records.
     * Note: The WHERE clause specifies which record or records that should be deleted. You can set where condition by calling
     * where function. If you omit the WHERE clause, all records will be deleted!    
     * @param   string   $dbTableName                  The name of the table to delete records.
     *
     */
    public function delete($dbTableName)
    {
        try {
            if ($this->dbTransaction && $this->dbRollBack)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }
            $this->sql = $this->getDeleteQuery($dbTableName);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute($this->values);
            $this->rowsChanged = $stmt->rowCount();
            $this->resetAll();
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Delete records in a table using associative array. Instead of writing long delete queries, you needs to set
     * where condition using where function.
     * This function will automatically create query for you and deletes records.
     * Note: The WHERE clause specifies which record or records that should be deleted. You can set where condition by calling
     * where function. If you omit the WHERE clause, all records will be deleted!    
     * @param   string   $dbTableName                  The name of the table to delete records.
     * @param   array    $where                        Array of Associative array with key as column name and values as column value.
     */
    public function deleteBatch($dbTableName, $where = array())
    {
        try {
            if ($this->dbTransaction && $this->dbRollBack)
                return;

            if ($this->dbTransaction && $this->dbTansactionStatus == 0) {
                $this->dbObj->beginTransaction();
                $this->dbTansactionStatus = 1;
            }

            for ($loop = 0; $loop < count($where); $loop++) {
                if (isset($where[$loop])) {
                    $operator = isset($where[$loop][2]) ? $where[$loop][2] : "=";
                    $this->where($where[$loop][0], $where[$loop][1], $operator);
                }
                $this->sql = $this->getDeleteQuery($dbTableName);
                $stmt = $this->dbObj->prepare($this->sql);
                $stmt->execute($this->values);
                $this->rowsChanged = $stmt->rowCount();
                $this->resetAll();
                $loop++;
            }
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Select records from the database table. You can set columns to be selected using colums function and where clause using
     * where function keys as columns and value as column value. Along with these function parameters,
     * you can set group by columnname, order by columnname, limit, like, in , not in, between clause etc. 
     * This function will automatically create query for you and returns appropriate data.
     * @param   string   $dbTableName                   The name of the table to select records.
     * return   array                                   returns array as result of query.
     */
    public function select($dbTableName)
    {

        try {
            $this->sql = $this->getSelectQuery($dbTableName);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute($this->values);
            $result = $stmt->fetchAll($this->getFetchType());

            if (is_array($result))
                $this->totalRows = count($result);
            $this->resetAll();
            return $result;
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Sets join condition between two tables
     * @param   string   $joinTableName                   The name of the table to joined.
     * @param   string   $joinCondition                   Join condition.
     * @param   string   $joinType                        Type of join INNER JOIN, LEFT JOIN etc.
     */
    public function joinTables($joinTableName, $joinCondition, $joinType = "INNER JOIN")
    {
        $this->joinString .= $joinType . " " . $this->parseTable($joinTableName) . " ON " . $joinCondition . " ";
        $this->joinTables[] = $joinTableName;
        return $this;
    }

    /**
     * Executes any query and return result as array
     * @param   string   $query                     Query to be executed
     * @param   array    $values                    Query to be executed
     * return   array                              returns array as result of query.
     */
    public function DBQuery($sql, $values = array()) {
        try {
            $this->sql = $sql;
            $stmt = $this->dbObj->prepare($this->sql);
            $this->values = $values;
            $stmt->execute($this->values);
            $result = $stmt->fetchAll($this->getFetchType());

            if (is_array($result))
                $this->totalRows = count($result);

            return $result;
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    public function executeQuery($sql)
    {
        try {
            $this->sql = $sql;
            $stmt = $this->dbObj->prepare($this->sql);
            
            $stmt->execute();
            $result = $stmt->fetchAll($this->getFetchType());

            if (is_array($result))
                $this->totalRows = count($result);

            return $result;
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    public function Query($sql, $values = array())
    {
        try {
            $this->sql = $sql;
            $stmt = $this->dbObj->prepare($this->sql);
            
            $this->values = $values;
            $result = $stmt->execute($this->values);

            if (is_array($result))
                $this->totalRows = count($result);

            return $result;
        } catch (PDOException $e) {
            if ($this->dbTransaction == true) {
                $this->dbRollBack = true;
                $this->dbObj->rollBack();
            }
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Sets where condition
     * @param   string   $column                  The name of column for which where condition needs to be applied.
     * @param   string   $value                   value of column to be checked
     * @param   string   $operator                Type of operator =, NOT IN, IN, BETWEEN etc.
     */
    public function where($column, $value, $operator = "=")
    {

        if (empty($this->whereCondition))
            $this->whereCondition = " WHERE ";
        else
            $this->whereCondition .= $this->andOrOperator . " ";

        if (!empty($this->openBrackets)) {
            $this->whereCondition .= $this->openBrackets . " ";
            $this->openBrackets = "";
        }

        if (strtoupper($operator) == "NOT IN" || strtoupper($operator) == "IN") {
            if (is_array($value)) {
                $parameters = array_map(function ($val) {
                    return "?";
                }, $value);
            }
            $this->whereCondition .= implode(" ", $this->parseColumns((array) $column)) . strtoupper($operator) . " (" . implode($parameters, ",") . ") ";
        } else if (strtoupper($operator) == "BETWEEN") {
            $this->whereCondition .= implode(" ", $this->parseColumns((array) $column)) . " BETWEEN ? AND ? ";
        } else {
            $this->whereCondition .= implode(" ", $this->parseColumns((array) $column)) . " " . $operator . " ? ";
        }

        if (!empty($this->closedBrackets)) {
            $this->whereCondition .= $this->closedBrackets . " ";
            $this->closedBrackets = "";
        }

        if (is_array($value))
            $this->values = array_merge($this->values, $value);
        else
            $this->values = array_merge($this->values, array($value));

        return $this;
    }

     public function whereYear($column, $value, $operator = "=")
    {
        if (!empty($this->whereCondition))
            $this->whereCondition .= $this->andOrOperator . " ";
        else
            $this->whereCondition = " WHERE ";

        $this->whereCondition .= "YEAR(" . implode(" ", $this->parseColumns((array) $column)) . ") " . $operator . " ? ";

        $this->values = array_merge($this->values, array($value));

        return $this;
    }

    public function whereYearBetween($column, $startYear, $endYear)
    {
        if (!empty($this->whereCondition))
            $this->whereCondition .= $this->andOrOperator . " ";
        else
            $this->whereCondition = " WHERE ";

        $this->whereCondition .= "YEAR(" . implode(" ", $this->parseColumns((array) $column)) . ") BETWEEN ? AND ? ";

        $this->values = array_merge($this->values, array($startYear, $endYear));

        return $this;
    }

    /**
     * Sets where subquery condition
     * @param   string   $column                  The name of column for which where condition needs to be applied.
     * @param   string   $subquery                subquery to be run
     * @param   string   $operator                Type of operator NOT IN, IN etc.
     * @param   string   $value                   value of column (optional)
     */
    public function where_subquery($column, $subquery, $operator, $value = "")
    {

        if (empty($this->whereCondition))
            $this->whereCondition = " WHERE ";
        else
            $this->whereCondition .= $this->andOrOperator . " ";

        if (!empty($this->openBrackets)) {
            $this->whereCondition .= $this->openBrackets . " ";
            $this->openBrackets = "";
        }

        $this->whereCondition .= implode(" ", $this->parseColumns((array) $column)) . " " . strtoupper($operator) . " (" . $subquery . ") ";

        if (!empty($this->closedBrackets)) {
            $this->whereCondition .= $this->closedBrackets . " ";
            $this->closedBrackets = "";
        }

        if (is_array($value))
            $this->values = array_merge($this->values, $value);
        else if (!empty($value))
            $this->values = array_merge($this->values, array($value));

        return $this;
    }

    /**
     * Sets sub query condition
     * @param   string   $sql                       Subquery to be set
     * @param   string   $alias                     Alias for subquery
     * @param   string   $data                      Values to be set for subquery
     */
    public function subQuery($sql, $alias = "", $data = array())
    {
        $this->subSQL .= ",(" . $sql . ")";

        if (!empty($alias))
            $this->subSQL .= " AS " . $alias;

        if (is_array($data) && count($data) > 0)
            $this->values = array_merge($data, $this->values);

        return $this;
    }

    /**
     * Returns last query executed
     */
    public function getLastQuery()
    {
        return $this->sql;
    }

    /**
     * Set limit - version 1.2
     * @param string   $limit      limit value of query
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set column names - version 1.2
     * @param array   $column    colunm names
     */
    public function setColumns($column = array())
    {
        $this->columns = $column;
        return $this;
    }

    /* Set order by  condition */
    /* @param array   $column    colunm names
     */

    public function orderBy($colunm = array())
    {
        $this->orderByCols = $colunm;
        return $this;
    }

    /**
     * Set whether to display errors or not by default
     * @param boolean   $showError    true or false
     */
    public function displayError($showError = true)
    {
        $this->displayError = $showError;
        return $this;
    }

    /**
     * Set Backtik 
     * @param string   $backticks      set backticks
     */
    public function setBacktiks($backtick = "`")
    {
        $this->backtick = $backtick;
        return $this;
    }

    /**
     * Gets fieldname(Columnname)table 
     * @param   string   $dbTableName                    Tablename for which fields data to be retrived
     * return   array                                    return array of columns
     */
    public function columnNames($dbTableName) {
        try {
            if ($this->dbType === "pgsql")
                $this->sql = "select column_name from INFORMATION_SCHEMA.COLUMNS where table_name = '" . $dbTableName . "'";
            else if ($this->dbType === "sqlite")
                $this->sql = "PRAGMA table_info('" . $dbTableName . "')";
            else if ($this->dbType === "sqlserver")
                $this->sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'$dbTableName'";
            else if ($this->dbType === "oracle")
                $this->sql = "SELECT COLUMN_NAME FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = UPPER('" . $dbTableName . "')";
            else
                $this->sql = "DESCRIBE " . $this->parseTable($dbTableName);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Gets all tables from database
     * return   array                                    return array of tables
     */
    public function getAllTables() {
        try {
            if ($this->dbType === "sqlite")
                $this->sql = "SELECT name FROM sqlite_master WHERE type='table'";
            else if ($this->dbType === "sqlserver")
                $this->sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_CATALOG='$this->dbName'";
            else if ($this->dbType === "oracle")
                $this->sql = "SELECT TABLE_NAME FROM ALL_TABLES WHERE OWNER = UPPER('$this->dbUserName')";               
            else
                $this->sql = "SHOW TABLES FROM " . $this->dbName;
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            $result = $stmt->fetchAll($this->getFetchType());
            return $result;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Gets fieldname(Columnname) and type of fields of table 
     * @param   string   $dbTableName                    Tablename for which fields data to be retrived
     * return   array                                    return array of field details
     */
    public function tableFieldInfo($dbTableName) {
        try {
            if ($this->dbType === "pgsql")
                $this->sql = "select * from INFORMATION_SCHEMA.COLUMNS where table_name = '" . $dbTableName . "'";
            else if ($this->dbType === "sqlite")
                $this->sql = "PRAGMA table_info('" . $dbTableName . "')";
            else if ($this->dbType === "sqlserver")
                $this->sql = "SELECT 
                        c.name 'Field',
                        t.Name 'Type',
                        c.is_nullable 'NULL',
                        c.is_identity  'Extra',
                        ISNULL(i.is_primary_key, 0) 'Key'
                    FROM    
                        sys.columns c
                    INNER JOIN 
                        sys.types t ON c.user_type_id = t.user_type_id
                    LEFT OUTER JOIN 
                        sys.index_columns ic ON ic.object_id = c.object_id AND ic.column_id = c.column_id
                    LEFT OUTER JOIN 
                        sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
                    WHERE
                        c.object_id = OBJECT_ID('$dbTableName')";
            else if ($this->dbType === "oracle")
                $this->sql = "SELECT COLUMN_NAME AS Field, DATA_TYPE AS Type, NULLABLE AS NULL 
                           FROM ALL_TAB_COLUMNS 
                           WHERE TABLE_NAME = UPPER('" . $dbTableName . "') AND OWNER = UPPER('$this->dbUserName')";
            else
                $this->sql = "SHOW FIELDS FROM " . $this->parseTable($dbTableName);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            $result = $stmt->fetchAll($this->getFetchType());
            if ($this->dbType === "sqlite") {
                $sqliteData = array_map(function($tag) {
                    return array(
                        'Type' => $tag['type'],
                        'Field' => $tag['name'],
                        'Extra' => $tag['pk']
                    );
                }, $result);
                return $sqliteData;
            }
            else  if ($this->dbType === "pgsql") {
                $pgsqlData = array_map(function($tag) {
                    return array(
                        'Type' => $tag['data_type'],
                        'Field' => $tag['column_name'],
                        'Extra' => $tag['is_identity']
                    );
                }, $result);
                return $pgsqlData;
            }
            else if ($this->dbType === "oracle") {
                $oracleData = array_map(function($tag) {
                    return array(
                        'Type' => $tag['Type'],
                        'Field' => $tag['Field'],
                        'NULL' => $tag['NULL']
                    );
                }, $result);
                return $oracleData;
            }
            return $result;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Gets primary key of table
     * @param   string   $dbTableName                    Tablename for which primary key to be retrived
     * return   string                                    return primary key column name
     */
    public function primaryKey($dbTableName) {
        try {
            if ($this->dbType === "pgsql")
                $this->sql = "SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS data_type FROM  pg_index i JOIN   pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) WHERE  i.indrelid = '$dbTableName'::regclass AND    i.indisprimary;";
            else if ($this->dbType === "sqlite")
                $this->sql = "pragma table_info ($dbTableName)";
            else if ($this->dbType === "sqlserver")
                  $this->sql = "SELECT Col.Column_name from INFORMATION_SCHEMA.TABLE_CONSTRAINTS Tab, INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE Col WHERE 
                                Col.Constraint_Name = Tab.Constraint_Name
                                AND Col.Table_Name = Tab.Table_Name
                                AND Constraint_Type = 'PRIMARY KEY'
                                AND Col.Table_Name = '$dbTableName'";
            else if ($this->dbType === "oracle")
                $this->sql = "SELECT cols.column_name 
                            FROM all_constraints cons, all_cons_columns cols 
                            WHERE cols.table_name = UPPER('$dbTableName') 
                            AND cons.constraint_type = 'P' 
                            AND cons.constraint_name = cols.constraint_name 
                            AND cons.owner = cols.owner";
            else
                $this->sql = "SHOW INDEXES FROM " . $this->parseTable($dbTableName) . " WHERE Key_name = 'PRIMARY'";

            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            $result = $stmt->fetchAll($this->getFetchType());
            if (count($result) > 0) {
                if ($this->dbType === "pgsql")
                    return $result[0]["attname"];
                else if ($this->dbType === "sqlite")
                    return $result[0]["name"];
                else if ($this->dbType === "oracle")
                    return $result[0]["column_name"];
                else
                    return $result[0]["Column_name"];
            } else
                return "";
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Drops table 
     * @param   string   $dbTableName                    Tablename to be dropped
     */
    public function dropTable($dbTableName)
    {
        try {
            $this->sql = "DROP TABLE " . $this->parseTable($dbTableName);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Truncate('delete from' for sqlite) table 
     * @param   string   $dbTableName                    Tablename to be truncated
     */
    public function truncateTable($dbTableName)
    {
        try {
            if ($this->dbType === "sqlite")
                $this->sql = "DELETE FROM " . $this->parseTable($dbTableName);
            else
                $this->sql = "TRUNCATE TABLE " . $this->parseTable($dbTableName);
            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    /**
     * Author: Daniel Huerta un gran desarrollador
     * create table
     * @param   string   $dbTableName                    Tablename
     * @param   array   $values                           values
     */
    public function create_table($dbTableName, $values)
    {
        try {
            if ($this->dbType === "mysql") {
                $this->sql = "CREATE TABLE IF NOT EXISTS {$dbTableName} ({$values})";
                $stmt = $this->dbObj->prepare($this->sql);
                $stmt->execute();
            }
            return true;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    public function alter_table($dbTableName, $modifications)
    {
        try {
            if ($this->dbType === "mysql") {
                // Separar las modificaciones en un arreglo
                $modificationList = explode(';', trim($modifications));
                // Filtrar modificaciones vacías
                $modificationList = array_filter(array_map('trim', $modificationList));

                // Verificar si hay modificaciones válidas
                if (!empty($modificationList)) {
                    // Iniciar la consulta ALTER TABLE
                    $this->sql = "ALTER TABLE {$dbTableName} ";
                    
                    // Unir las modificaciones con una coma
                    $this->sql .= implode(', ', $modificationList);
                    
                    // Preparar y ejecutar la consulta
                    $stmt = $this->dbObj->prepare($this->sql);
                    $stmt->execute();
                } else {
                    throw new Exception("No se proporcionaron modificaciones válidas.");
                }
            }
            return true;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
            return false; // Devolver falso si ocurre un error
        } catch (Exception $e) {
            $this->setErrors($e->getMessage());
            return false; // Manejo de errores personalizado
        }
    }

    /**
     * Rename table 
     * @param   string   $oldDBTableName                    Tablename to be rename
     * @param   string   $newDBTableName                    New table name
     */
    public function renameTable($oldDBTableName, $newDBTableName)
    {
        try {
            if ($this->dbType === "mysql")
                $this->sql = "RENAME  TABLE " . $this->parseTable($oldDBTableName) . " TO " . $this->parseTable($newDBTableName);
            else
                $this->sql = "ALTER TABLE " . $this->parseTable($oldDBTableName) . " RENAME TO " . $this->parseTable($newDBTableName);

            $stmt = $this->dbObj->prepare($this->sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->setErrors($e->getMessage());
        }
    }

    private function getInsertQuery($dbTableName, $insertData)
    {
        $this->columns = implode(",", $this->parseColumns(array_keys($insertData)));
        $this->values = array_values($insertData);
        $this->parameters = "";

        $this->parameters = array_map(function ($val) {
            return "?";
        }, $insertData);


        $this->parameters = implode(",", $this->parameters);
        return "INSERT INTO " . $this->parseTable($dbTableName) . " ($this->columns) VALUES ($this->parameters)";
    }

    private function getInsertOnDuplicateUpdateQuery($dbTableName, $updateData)
    {
        $columns = implode("=?,", $this->parseColumns(array_keys($updateData))) . "=?";
        if (!isset($this->values))
            $this->values = array();
        $this->values = array_merge($this->values, array_values($updateData));
        return " $columns";
    }

    private function getUpdateQuery($dbTableName, $updateData)
    {
        $this->columns = implode("=?,", $this->parseColumns(array_keys($updateData))) . "=?";
        $this->values = array_merge(array_values($updateData), $this->values);
        $whereCondition = $this->whereCondition;
        $orderByCondition = $this->getorderByCondition();
        $limit = $this->getLimitCondition();

        return "UPDATE " . $this->parseTable($dbTableName) . " SET $this->columns $whereCondition $orderByCondition $limit";
    }

    private function getDeleteQuery($dbTableName)
    {
        $whereCondition = $this->whereCondition;
        $orderByCondition = $this->getorderByCondition();
        $limit = $this->getLimitCondition();

        return "DELETE FROM " . $this->parseTable($dbTableName) . " $whereCondition $orderByCondition $limit";
    }

    private function getSelectQuery($dbTableName)
    {
        if (is_array($this->columns) && count($this->columns) > 0)
            $cols = implode(",", array_values($this->parseColumns($this->columns)));
        else {
            $cols = "$dbTableName.* ";
            if (isset($this->joinTables)) {
                foreach ($this->joinTables as $joinTables) {
                    $cols .= ",$joinTables.* ";
                }
            }
        }

        if (!empty($this->closedBrackets)) {
            $this->whereCondition .= $this->closedBrackets . " ";
            $this->closedBrackets = "";
        }

        $sql = "SELECT " . $cols . $this->subSQL . " FROM " . $this->parseTable($dbTableName) . " " . $this->joinString . " " . $this->whereCondition
            . $this->getGroupByCondition() . $this->getHavingCondition() . $this->getorderByCondition() . $this->getLimitCondition();

        return $sql;
    }

    /* Returns order by  condition */

    private function getorderByCondition()
    {
        $orderBy = "";
        if (is_array($this->orderByCols) && count($this->orderByCols) > 0)
            $orderBy .= " ORDER BY " . implode(",", $this->parseColumns($this->orderByCols));

        return $orderBy;
    }

    /* Returns limit condition */

    private function getLimitCondition()
    {
        $limitBy = "";
        if (!empty($this->limit)) {
            if ($this->dbType === "pgsql") {
                $limit = explode(",", $this->limit);
                $limitBy .= " LIMIT " . $limit[1];
                if (isset($limit[1]))
                    $limitBy .= " OFFSET " . $limit[0];
            } else if ($this->dbType === "sqlserver") {
                $limit = explode(",", $this->limit);
                $limitBy .= " OFFSET " . $limit[0] . " ROWS";
                if (isset($limit[1]))
                    $limitBy .= " FETCH NEXT  " . $limit[1] . " ROWS ONLY";
            } else {
                $limitBy .= " LIMIT " . $this->limit;
            }
        }

        return $limitBy;
    }

    /* Returns group by condition */

    private function getGroupByCondition()
    {
        $groupby = "";
        if (!empty($this->groupByCols))
            $groupby = " GROUP BY " . implode(",", $this->parseColumns($this->groupByCols));

        return $groupby;
    }

    /* Returns having condition */

    private function getHavingCondition()
    {
        $having = "";
        if ($this->havingCondition)
            $having .= " HAVING " . implode(",", $this->havingCondition);

        return $having;
    }

    private function getFetchType()
    {
        switch (strtoupper($this->fetchType == null)) {
            case "BOTH":
                return PDO::FETCH_BOTH;
            case "NUM":
                return PDO::FETCH_NUM;
            case "ASSOC":
                return PDO::FETCH_ASSOC;
            case "OBJ":
                return PDO::FETCH_OBJ;
            case "COLUMN":
                return PDO::FETCH_COLUMN;
            default:
                return PDO::FETCH_ASSOC;
        }
    }

    public function resetAll()
    {
        $this->whereCondition = "";
        $this->joinString = "";
        $this->joinTables = array();
        $this->values = array();
        $this->limit = "";
        $this->columns = array();
        $this->groupByCols = array();
        $this->havingCondition = array();
        $this->orderByCols = array();
        $this->subSQL = "";
    }

    public function resetWhere()
    {
        $this->whereCondition = "";
    }

    public function resetValues()
    {
        $this->values = array();
    }

    public function resetLimit()
    {
        $this->limit = "";
    }

    public function resetError()
    {
        $this->error = array();
    }

    private function parseColumns($cols)
    {
        $columns = array();
        if (is_array($cols) && !empty($this->backtick)) {
            foreach ($cols as $col) {
                if ($this->checkColAggr($col)) {
                    $alias = explode(" as ", strtolower($col));
                    if (isset($alias[1]))
                        $col = trim($alias[0]) . " AS " . $this->backtick . trim($alias[1]) . $this->backtick;
                } else if (preg_match("/\bas\b/i", strtolower($col))) {
                    //list($colName, $table) = explode(" as ", strtolower($col));
                    list($colName, $table) = explode(" as ", $col);
                    if (strpos($colName, ".") !== false) {
                        $colData = explode(".", $colName);
                        $col = $this->backtick . trim($colData[0]) . $this->backtick . "." . $this->backtick . trim($colData[1]) . $this->backtick . " AS " . $this->backtick . trim($table) . $this->backtick;
                    }
                } else if (preg_match("/\basc\b/i", strtolower($col))) {
                    //list($colName) = explode(" asc", strtolower($col));
                    list($colName) = explode(" asc", $col);
                    if (strpos($colName, ".") !== false) {
                        $colData = explode(".", $colName);
                        $col = $this->backtick . trim($colData[0]) . $this->backtick . "." . $this->backtick . trim($colData[1]) . $this->backtick . " ASC";
                    } else {
                        $col = $this->backtick . trim($colName) . $this->backtick . " ASC";
                    }
                } else if (preg_match("/\bdesc\b/i", strtolower($col))) {
                    //list($colName) = explode(" desc", strtolower($col));
                    list($colName) = explode(" desc", $col);
                    if (strpos($colName, ".") !== false) {
                        $colData = explode(".", $colName);
                        $col = $this->backtick . trim($colData[0]) . $this->backtick . "." . $this->backtick . trim($colData[1]) . $this->backtick . " DESC";
                    } else {
                        $col = $this->backtick . trim($colName) . $this->backtick . " DESC";
                    }
                } else {
                    if (strpos($col, ".") !== false) {
                        $val = explode(".", $col);
                        $col = $this->backtick . trim($val[0]) . $this->backtick . "." . $this->backtick . trim($val[1]) . $this->backtick;
                    } else {
                        $col = $this->backtick . $col . $this->backtick;
                    }
                }

                $columns[] = $col;
            }
        } else {
            $columns = $cols;
        }
        return $columns;
    }

    private function parseTable($dbTable)
    {
        $table = $dbTable;
        if (!empty($this->backtick)) {
            if (preg_match("/\bas\b/i", strtolower($dbTable))) {
                list($tableName, $alias) = explode("as", strtolower($dbTable));
                $table = $this->backtick . trim($tableName) . $this->backtick . " AS " . $this->backtick . trim($alias) . $this->backtick;
            } else {
                $table = $this->backtick . trim($dbTable) . $this->backtick;
            }
        }
        return $table;
    }

    private function checkColAggr($column)
    {
        $col = strtolower($column);
        if (strpos($col, "concat") !== false || strpos($col, "sum") !== false || strpos($col, "max") !== false || strpos($col, "min") !== false || strpos($col, "count") !== false || strpos($col, "distinct") !== false) {
            return true;
        }
        return false;
    }

    private function setErrors($error)
    {

        if (isset($this->artifyErrCtrl)) {
            $this->artifyErrCtrl->addError($error, TRUE);
        } else {
            $this->error[] = $error;
            if ($this->displayError)
                echo $error;
        }
    }

    /* Helper functions */

    /**
     * Generates the csv file as output from the array provided. 
     * Returns true if operation performed successfully else return false.
     * 
     * @param   array     $csvArray                 Associative array with key as column name and value as table values.
     * @param   string    $outputFileName           Output csv file name
     *
     */
    public function arrayToCSV($csvArray, $fileName = "file.csv")
    {
        if (!is_array($csvArray)) {
            $this->setErrors("Please provide valid input. ");
            return false;
        }
        if (!$fileName) {
            $this->setErrors("Please provide the csv file name");
            return false;
        }
        if ($this->append && !isset($this->existingFilePath)) {
            $this->setErrors("Please provide existing file path, you want to append data ");
            return false;
        }
        $list = $csvArray;
        if ($this->fileSavePath && !is_dir($this->fileSavePath))
            mkdir($this->fileSavePath);

        if ($this->append)
            $fp = fopen($this->existingFilePath, 'a+');
        else
            $fp = fopen($this->fileSavePath . $fileName, 'w');

        foreach ($list as $fields) {
            fputcsv($fp, $fields, $this->delimiter, $this->enclosure);
        }

        if ($this->fileOutputMode == 'browser') {
            header("Content-type: application/csv");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Pragma: no-cache");
            readfile($this->fileSavePath . $fileName);
            die(); //to prevent page output
        }

        fclose($fp);
        return true;
    }

    /**
     * Generates the xml as output from the array provided. Returns true if operation performed successfully else return false
     * 
     * @param   array     $xmlArray                 Associative array with key as column name and value as table values.
     * @param   string    $outputFileName           Output xml file name
     *
     */
    public function arrayToXML($xmlArray, $outputFileName = "file.xml")
    {
        if (!is_array($xmlArray)) {
            $this->setErrors("Please provide valid input.");
            return false;
        }
        $xmlObject = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"$this->encoding\" ?><$this->rootElement></$this->rootElement>");
        $this->generateXML($xmlArray, $xmlObject, $this->rootElement);
        if ($this->fileOutputMode == "browser")
            echo $xmlObject->asXML();
        else {
            if ($this->fileSavePath && !is_dir($this->fileSavePath))
                mkdir($this->fileSavePath);
            $xmlObject->asXML($this->fileSavePath . $outputFileName);
        }
        return true;
    }

    /**
     * Generates the html table as output from the array provided.
     * 
     * @param   array     $htmlArray                Associative array with key as column name and value as table values.
     * @param   array     $outputFileName           Output file name
     * @param   bool      $isCalledFromPDF          This function is used internally in arrayToPDF() also, to check whether it is called directly                                                               or using the pdf function 

     *
     */
    public function arrayToHTML($htmlArray, $outputFileName = "file.html", $isCalledFromPDF = false)
    {
        if (!is_array($htmlArray)) {
            $this->setErrors("Please provide valid input. ");
            return false;
        }
        $table_output = '<table class="' . $this->tableCssClass . '" style="' . $this->htmlTableStyle . '">';
        $table_head = "";
        if ($this->useFirstRowAsTag == true)
            $table_head = "<thead>";
        $table_body = '<tbody>';
        $loop_count = 0;

        foreach ($htmlArray as $k => $v) {
            if ($this->useFirstRowAsTag == true && $loop_count == 0)
                $table_head .= '<tr class="' . $this->trCssClass . '" style="' . $this->htmlTRStyle . '" id="row_' . $loop_count . '">';
            else
                $table_body .= '<tr class="' . $this->trCssClass . '" style="' . $this->htmlTRStyle . '" id="row_' . $loop_count . '">';

            foreach ($v as $col => $row) {
                if ($this->useFirstRowAsTag == true && $loop_count == 0)
                    $table_head .= '<th style="' . $this->htmlTDStyle . '">' . $row . '</th>';
                else
                    $table_body .= '<td style="' . $this->htmlTDStyle . '">' . $row . '</td>';
            }
            $table_body .= '</tr>';
            if ($this->useFirstRowAsTag == true && $loop_count == 0)
                $table_body .= '</tr></thead>';

            $loop_count++;
        }

        $table_body .= '</tbody>';
        $table_output = $table_output . $table_head . $table_body . '</table>';
        $this->outputHTML = $table_output;
        if ($this->fileOutputMode == "save" && !$isCalledFromPDF) {
            if ($this->fileSavePath && !is_dir($this->fileSavePath))
                mkdir($this->fileSavePath);
            $fp = fopen($this->fileSavePath . $outputFileName, "w+");
            fwrite($fp, $this->outputHTML);
            fclose($fp);
        }


        return true;
    }

    /**
     * Generates the pdf as output from the array provided. Returns true if operation performed successfully else return false
     * 
     * @param   array     $pdfArray                 Associative array with key as column name and value as table values.
     * @param   string    $outputFileName           Output pdf file name
     *
     */
    public function arrayToPDF($pdfArray, $outputFileName = "file.pdf")
    {
        if (!is_array($pdfArray)) {
            $this->setErrors("Please provide valid input. ");
            return false;
        }
        require_once(dirname(__FILE__) . "/library/tcpdf/tcpdf.php");
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetFont($this->pdfFontName, $this->pdfFontWeight, $this->pdfFontSize, '', 'false');
        $pdf->SetAuthor($this->pdfAuthorName);
        $pdf->SetSubject($this->pdfSubject);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->AddPage();
        $this->arrayToHTML($pdfArray, "file.html", true);
        $pdf->writeHTML($this->outputHTML, true, false, true, false, '');
        if ($this->fileOutputMode == "browser")
            $pdf->Output($outputFileName, 'I');
        else {
            if ($this->fileSavePath && !is_dir($this->fileSavePath))
                mkdir($this->fileSavePath);
            $pdf->Output($this->fileSavePath . $outputFileName, 'F');
        }
        return true;
    }

    /**
     * Generates the excel file as output from the array provided. 
     * 
     * @param   array     $excelArray               Associative array with key as column name and value as table values.
     * @param   string    $outputFileName           Output excel file name
     *
     */
    public function arrayToExcel($excelArray, $outputFileName = "file.xlsx") {
        if (!is_array($excelArray)) {
            $this->setErrors("Please provide valid input.");
            return false;
        }

        // Crear un nuevo objeto Spreadsheet
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        if ($this->append && !isset($this->existingFilePath)) {
            $this->setErrors("Please provide existing file path, you want to append data");
            return false;
        }
        if (empty($outputFileName)) {
            if ($this->excelFormat == "2007")
                $outputFileName = "file.xlsx";
            else
                $outputFileName = "file.xls";
        }
        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        
        // Rellenar el archivo de Excel con los datos del array
        $rowIndex = 1;
        foreach ($excelArray as $row) {
            $columnIndex = 'A';
            foreach ($row as $cellValue) {
                $activeWorksheet->setCellValue($columnIndex . $rowIndex, $cellValue);
                $columnIndex++;
            }
            $rowIndex++;
        }


        // Guardar el archivo de Excel en el disco duro
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($this->fileSavePath . $outputFileName);

        
        if ($this->fileOutputMode == "browser") {
                    if ($this->excelFormat == "2007")
                        header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    else
                        header('Content-type: application/vnd.ms-excel');
    
                    header('Content-Disposition: attachment; filename="' . $outputFileName . '"');
                    $writer->save('php://output');
                    die();
                }
        return true;
    }

    private function generateXML($xmlArray, &$xmlObject, $rootElement = "root")
    {
        foreach ($xmlArray as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xmlObject->addChild("$key");
                    $this->generateXML($value, $subnode, $rootElement);
                } else {
                    $this->generateXML($value, $xmlObject, $rootElement);
                }
            } else {
                if (is_numeric($key)) {
                    $key = $rootElement;
                }
                $xmlObject->addChild("$key", "$value");
            }
        }
    }

    /**
     * Return json as output from the array provided.
     * 
     * @param   array     $jsonArray                Associative array with key as column name and value as table values.

     *
     */
    public function arrayToJson($jsonArray)
    {
        return json_encode($jsonArray);
    }

    /**
     * Returns the array as output from the csv provided.
     * 
     * @param   string     $fileName                 Name or path of csv file.
     *
     */
    public function csvToArray($fileName)
    {
        if (empty($fileName)) {
            $this->setErrors("Please provide the csv file name");
            return false;
        }
        $csvArray = array();
        if (($handle = fopen($fileName, "r")) !== FALSE) {
            $rowIndex = 0;
            while (($lineArray = fgetcsv($handle, 0, $this->delimiter)) !== FALSE) {
                for ($colIndex = 0; $colIndex < count($lineArray); $colIndex++) {
                    $csvArray[$rowIndex][$colIndex] = $lineArray[$colIndex];
                }
                $rowIndex++;
            }
            fclose($handle);
        }
        $csvArray = $this->formatOutputArray($csvArray);
        return $csvArray;
    }

    /**
     * Returns the array as output from the excel provided.
     * 
     * @param   string     $fileName                 Name or path of excel file.
     */
    public function excelToArray($fileName)
    {
        if (!$fileName) {
            $this->setErrors("Please provide the excel file name");
            return false;
        }
        $reader = IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load($fileName);

        $worksheet = $spreadsheet->getActiveSheet();
        $excelArray = $worksheet->toArray(null, true, true, false);
        $excelArray = $this->formatOutputArray($excelArray);

        return $excelArray;
    }

    /**
     * Returns the array as output from the xml provided.
     * 
     * @param   string     $xmlSource                 Name or path of xml file.
     *
     */
    public function xmlToArray($xmlSource)
    {
        if ($this->isFile)
            $xml = file_get_contents($xmlSource);
        else
            $xml = $xmlSource;

        $xmlObject = new SimpleXMLElement($xml);
        $decodeArray = @json_decode(@json_encode($xmlObject), 1);
        foreach ($decodeArray as $newDecodeArray) {
            $returnArray = $newDecodeArray;
        }
        return $returnArray;
    }

    private function formatOutputArray($data)
    {
        $output = array();
        $loop = 0;
        if (isset($data) && count($data) > 0) {
            $columns = $data[0];
            foreach ($data as $row) {
                if ($loop > 0)
                    $output[] = array_combine($columns, $row);
                $loop++;
            }
        }
        return $output;
    }

    /**
     * Generates random password.
     * @param   int  $length                            Length of the random password (default is 8)
     * @param   boolean $allow_special_character        whether allo special characters in password or not (default is false)
     * return   string                                  return randomly generated string
     */
    public function getRandomPassword($length = 8, $allow_special_character = false)
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        if ($allow_special_character)
            //$alphabet .= "!@#$%&*(){}[]<>,.";
        return substr(str_shuffle($alphabet), 0, $length);
    }

    /**
     *  Returns pagination
     */
    public function pagination($page = 1, $totalrecords = null, $limit = 10, $adjacents = 1, $base_url = "")
    {
        $pagination = "";
        if ($totalrecords > 0) {
            if (!$limit) $limit = 15;
            if (!$page) $page = 1;

            $prev = $page - 1;
            $next = $page + 1;
            $lastpage = ceil($totalrecords / $limit);
            $lpm1 = $lastpage - 1;

            if ($lastpage > 1) {
                $pagination .= "<nav aria-label=\"Page navigation\"><ul class=\"pagination\">";

                //previous button
                if ($page > 1)
                    $pagination .= "<li><a class='queryfy-page' href=\"$base_url" . $prev . "\" aria-label=\"Previous\"><span aria-hidden=\"true\">&laquo;</span></a></li>";
                else
                    $pagination .= "<li class=\"disabled\"><span aria-hidden=\"true\">&laquo;</span></li>";

                //pages 
                if ($lastpage < 7 + ($adjacents * 2)) {
                    //not enough pages to bother breaking it up
                    for ($counter = 1; $counter <= $lastpage; $counter++) {
                        if ($counter == $page)
                            $pagination .= "<li class=\"active\"><span>$counter</span></li>";
                        else
                            $pagination .= "<li><a class='queryfy-page' href=\"$base_url" . $counter . "\">$counter</a></li>";
                    }
                } elseif ($lastpage >= 7 + ($adjacents * 2)) {
                    //enough pages to hide some
                    //close to beginning; only hide later pages
                    if ($page < 1 + ($adjacents * 3)) {
                        for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                            if ($counter == $page)
                                $pagination .= "<li class=\"active\"><span>$counter</span></li>";
                            else
                                $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $counter . "\">$counter</a></li>";
                        }
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lpm1 . "\">...</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lpm1 . "\">$lpm1</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lastpage . "\">$lastpage</a></li>";
                    } elseif ($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                        //in middle; hide some front and some back
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=1\">1</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=2\">2</a></li>";
                        $pagination .= "<li class=\"elipses\">...</li>";
                        for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                            if ($counter == $page)
                                $pagination .= "<li class=\"active\"><span>$counter</span></li>";
                            else
                                $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $counter . "\">$counter</a></li>";
                        }
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lpm1 . "\">...</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lpm1 . "\">$lpm1</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lastpage . "\">$lastpage</a></li>";
                    } else {
                        //close to end; only hide early pages
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=1\">1</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=2\">2</a></li>";
                        $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $lpm1 . "\">...</a></li>";
                        for ($counter = $lastpage - (1 + ($adjacents * 3)); $counter <= $lastpage; $counter++) {
                            if ($counter == $page)
                                $pagination .= "<li class=\"active\"><span>$counter</span></li>";
                            else
                                $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $counter . "\">$counter</a></li>";
                        }
                    }
                }

                //next button
                if ($page < $counter - 1)
                    $pagination .= "<li><a class='queryfy-page' href=\"$base_url?page=" . $next . "\" aria-label=\"Next\"><span aria-hidden=\"true\">&raquo;</span></a></li>";
                else
                    $pagination .= "<li class=\"disabled\"><span aria-hidden=\"true\">&raquo;</span></li>";

                $pagination .= "</ul></nav>";
            }
        }

        return $pagination;
    }

    public function simplepagination($page = 1, $totalrecords = null, $limit = 10, $base_url = "", $parametro = "")
    {
        $pagination = "";

        if ($totalrecords > 0) {
            if (!$limit) $limit = 15;
            if (!$page) $page = 1;

            $lastpage = ceil($totalrecords / $limit);

            if ($lastpage > 1) {
                $pagination .= "<nav aria-label=\"Page navigation\"><ul class=\"pagination\">";

                if ($page > 1)
                    $pagination .= "<li class=\"page-item\"><a class='page-link text-dark' href=\"$base_url?$parametro=" . ($page - 1) . "\" aria-label=\"Previous\"><span aria-hidden=\"true\">&laquo;</span></a></li>";
                else
                    $pagination .= "<li class=\"disabled\"><span class='page-link text-dark' aria-hidden=\"true\">&laquo;</span></li>";

                if ($page < $lastpage)
                    $pagination .= "<li><a class='page-link text-dark' href=\"$base_url?$parametro=" . ($page + 1) . "\" aria-label=\"Next\"><span aria-hidden=\"true\">&raquo;</span></a></li>";
                else
                    $pagination .= "<li class=\"disabled\"><span class='page-link text-dark' aria-hidden=\"true\">&raquo;</span></li>";

                $pagination .= "</ul></nav>";
            }
        }

        return $pagination;
    }
}

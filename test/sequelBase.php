<?php
abstract class Sql_Test_Base extends PHPUnit_Framework_TestCase {
    protected $Sql, $DB;

    function setUp() {
        $this->DB = new PDO("sqlite::memory:");
        $this->Sql = new Sequel($this->DB);
        $this->create_database();
        $this->insert_default_rows();
    }

    protected function create_database() {
        $this->DB->exec(
            "CREATE TABLE IF NOT EXISTS A (
                id INT AUTO_INCREMENT PRIMARY KEY,
                a CHAR(3) UNIQUE,
                b INT
            )"
        );
    }

    protected function insert_default_rows() {
        $this->DB->prepare(
            "INSERT INTO A (id, a, b) VALUES (?, ?, ?)"
        )->execute(array(1, "foo", 5));
        $this->DB->prepare(
            "INSERT INTO A (id, a, b) VALUES (?, ?, ?)"
        )->execute(array(2, "bar", 6));
    }
}

?>

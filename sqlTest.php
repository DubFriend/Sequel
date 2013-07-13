<?php
require_once "sql.php";

class Sql_Test extends PHPUnit_Framework_TestCase {

    private $Sql, $DB;

    function setUp() {
        $this->DB = new PDO("sqlite::memory:");
        $this->Sql = new Sequel($this->DB);
        $this->create_database();
        $this->insert_default_rows();
    }

    private function create_database() {
        $this->DB->exec(
            "CREATE TABLE IF NOT EXISTS A (
                id INT AUTO_INCREMENT PRIMARY KEY,
                a CHAR(3),
                b INT
            )"
        );
    }

    private function insert_default_rows() {
        $this->DB->prepare(
            "INSERT INTO A (id, a, b) VALUES (?, ?, ?)"
        )->execute(array(1, "foo", 5));
        $this->DB->prepare(
            "INSERT INTO A (id, a, b) VALUES (?, ?, ?)"
        )->execute(array(2, "bar", 6));
    }


    function test_results_count() {
        $Results = $this->Sql->query("sElecT * FROM A");
        $this->assertEquals(2, $Results->count());
    }

    function test_results_next() {
        $Results = $this->Sql->query("SELECT * FROM A");
        $this->assertEquals(
            array("id" => 1,"a" => "foo", "b" => 5),
            $Results->next()
        );
    }

    function test_results_foreach_loop() {
        $actualResults = array();
        foreach($this->Sql->query("SELECT * FROM A") as $key => $val) {
            $actualResults[$key] = $val;
        }

        $this->assertEquals(
            array(
                array("id" => 1, "a" => "foo", "b" => 5),
                array("id" => 2,"a" => "bar", "b" => 6)
            ),
            $actualResults
        );
    }

    function test_results_foreach_loop_empty_results() {
        $ResultsObject = $this->Sql->query(
            "SELECT * FROM A WHERE a = ?",
            array("wrong")
        );
        $actualResults = array();
        foreach($ResultsObject as $key => $val) {
            $actualResults[$key] = $val;
        }
        $this->assertEquals(array(), $actualResults);
    }

    /**
     * @expectedException Sequel_Exception
     * @expectedExceptionMessage Does not support rewind.
     */
    function test_results_foreach_loop_next_allready_called() {
        $ResultsObject = $this->Sql->query("SELECT * FROM A");
        $ResultsObject->next();
        $actualResults = array();
        foreach($ResultsObject as $key => $val) {
            $actualResults[$key] = $val;
        }
    }

    function test_one() {
        $this->assertEquals(
            $this->Sql->one("SELECT * FROM A WHERE a = 'foo'"),
            array("id" => 1, "a" => "foo", "b" => 5)
        );
    }

    function test_one_no_result() {
        $this->assertEquals(
            $this->Sql->one("SELECT * FROM A WHERE a = 'wrong'"),
            false
        );
    }

    function test_get() {
        $this->assertEquals(
            $this->Sql->get("A", array("a" => "foo"))->to_array(),
            array(array("id" => 1, "a" => "foo", "b" => 5))
        );
    }

    function test_get_one() {
        $this->assertEquals(
            $this->Sql->get_one("A", array("a" => "foo")),
            array("id" => 1, "a" => "foo", "b" => 5)
        );
    }

    function test_insert() {
        $this->Sql->query(
            "INSERT INTO A (id, a, b) VALUES (? ,?, ?)",
            array(3, "baz", 7)
        );
        $Results = $this->DB->query("SELECT * FROM A WHERE id='3'");
        $Results->setFetchMode(PDO::FETCH_ASSOC);
        $this->assertEquals(
            array("id" => 3, "a" => "baz", "b" => 7),
            $Results->fetch()
        );
    }

    function test_insert_id() {
        $id = $this->Sql->query(
            "INSERT INTO A (id, a, b) VALUES (? ,?, ?)",
            array(3, "baz", 7)
        );
        $this->assertEquals(3, $id);
    }

    function test_update() {
        $this->Sql->query("UPDATE A SET a = 'edit' WHERE id='1'");
        $Results = $this->DB->query("SELECT * FROM A WHERE id='1'");
        $Results->setFetchMode(PDO::FETCH_ASSOC);
        $this->assertEquals(
            array("id" => 1, "a" => "edit", "b" => 5),
            $Results->fetch()
        );
    }

    function test_delete() {
        $this->Sql->query("DELETE FROM A WHERE id='1'");
        $Results = $this->DB->query("SELECT * FROM A WHERE id='1'");
        $this->assertEquals(
            false,
            $Results->fetch()
        );
    }

    function test_results_to_array() {
        $ResultsObject = $this->Sql->query("SELECT * FROM A");
        $this->assertEquals(
            array(
                array("id" => 1, "a" => "foo", "b" => 5),
                array("id" => 2,"a" => "bar", "b" => 6)
            ),
            $ResultsObject->to_array()
        );
    }

    //function test_transaction_query_is_held() {}
    //function test_transaction_commit() {}
    /**
     * @depends test_results_next
     * @depends test_get_one
     */
    function test_transaction_roll_back() {
        $this->Sql->begin_transaction();
        $this->Sql->query("INSERT INTO A (id) VALUES ('3')");
        $this->Sql->roll_back();
        $this->assertFalse($this->Sql->get_one("A", array("id" => 3)));
    }
}
?>

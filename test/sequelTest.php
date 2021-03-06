<?php
require_once "sequel.php";

class Sql_Test extends PHPUnit_Framework_TestCase {
    public $Sql, $DB;

    function setUp() {
        $this->DB = new PDO("sqlite::memory:");
        $this->Sql = new Sequel($this->DB);
        $this->create_database();
        $this->insert_default_rows();
    }

    function create_database() {
        $this->DB->exec(
            "CREATE TABLE IF NOT EXISTS A (
                id INT AUTO_INCREMENT PRIMARY KEY,
                a CHAR(3) UNIQUE,
                b INT,
                `select` INT
            )"
        );
    }

    function insert_default_rows() {
        $this->DB->prepare(
            "INSERT INTO A (id, a, b) VALUES (?, ?, ?)"
        )->execute(array(1, "foo", 5));
        $this->DB->prepare(
            "INSERT INTO A (id, a, b, `select`) VALUES (?, ?, ?, ?)"
        )->execute(array(2, "bar", 6, 1));
    }

    function test_results_count() {
        $Results = $this->Sql->query("sElecT * FROM A");
        $this->assertEquals(2, $Results->count());
    }

    function test_results_count_limit() {
        $Results = $this->Sql->query("SELECT * FROM A LIMIT 1, 1");
        $this->assertEquals(2, $Results->count());
    }

    function test_results_count_limit_case_insensitive() {
        $Results = $this->Sql->query("SELECT * FRoM A LImIT 1, 1");
        $this->assertEquals(2, $Results->count());
    }

    function test_results_next() {
        $Results = $this->Sql->query("SELECT * FROM A");
        $this->assertEquals(
            array("id" => 1,"a" => "foo", "b" => 5, "select" => null),
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
                array("id" => 1, "a" => "foo", "b" => 5, "select" => null),
                array("id" => 2,"a" => "bar", "b" => 6, "select" => 1)
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

    /**
     * @expectedException Sequel_Exception
     * @expectedExceptionMessage Does not support rewind.
     */
    function test_rewind_throws_exception_after_next_called() {
        $results = $this->Sql->query("SELECT * FROM A");
        $results->next();
        $results->rewind();
    }

    function test_one() {
        $this->assertEquals(
            $this->Sql->one("SELECT * FROM A WHERE a = 'foo'"),
            array("id" => 1, "a" => "foo", "b" => 5, "select" => null)
        );
    }

    function test_one_no_results() {
        $this->assertFalse($this->Sql->one("SELECT * FROM A WHERE a = 'wrong'"));
    }

    function test_select() {
        $this->assertEquals(
            $this->Sql->select("A", array("a" => "foo"))->toArray(),
            array(array("id" => 1, "a" => "foo", "b" => 5, "select" => null))
        );
    }

    function test_select_no_where() {
        $this->assertEquals(
            $this->Sql->select('A')->toArray(),
            array(
                array("id" => 1, "a" => "foo", "b" => 5, "select" => null),
                array("id" => 2,"a" => "bar", "b" => 6, "select" => 1)
            )
        );
    }

    function test_select_wraps_in_backticks() {
        $this->assertEquals(
            $this->Sql->select('A', array('select' => 1))->toArray(),
            array(array("id" => 2,"a" => "bar", "b" => 6, "select" => 1))
        );
    }

    function test_select_doesnt_wrap_if_allready_wrapped() {
        $this->assertEquals(
            $this->Sql->select('A', array('`select`' => 1))->toArray(),
            array(array("id" => 2,"a" => "bar", "b" => 6, "select" => 1))
        );
    }

    function test_selectOne() {
        $this->assertEquals(
            $this->Sql->selectOne("A", array("a" => "foo")),
            array("id" => 1, "a" => "foo", "b" => 5, "select" => null)
        );
    }

    function test_selectOne_wraps_in_backticks() {
        $this->assertEquals(
            $this->Sql->selectOne("A", array('select' => 1)),
            array("id" => 2, "a" => "bar", "b" => 6, "select" => 1)
        );
    }

    function test_selectOne_no_results() {
        $this->assertFalse($this->Sql->selectOne("A", array("a" => "wrong")));
    }

    /**
     * @depends test_selectOne
     */
    function test_insert() {
        $this->Sql->insert("A", array("id" => 3, "a" => "baz"));
        $this->assertEquals(
            $this->Sql->selectOne("A", array("id" => 3)),
            array("id" => 3, "a" => "baz", "b" => null, "select" => null)
        );
    }

    /**
     * @depends test_selectOne
     */
    function test_insert_wraps_in_backticks() {
        $this->Sql->insert("A", array("id" => 3, "select" => 5));
        $this->assertEquals(
            $this->Sql->selectOne("A", array("id" => 3)),
            array("id" => 3, "a" => null, "b" => null, "select" => 5)
        );
    }

    /**
     * @depends test_selectOne
     */
    function test_insert_fail_unique_key_constraint() {
        $this->assertFalse(
            $this->Sql->insert("A", array("id" => 3, "a" => "foo"))
        );
        $this->assertFalse($this->Sql->selectOne("A", array("id" => 3)));
    }

    /**
     * @depends test_selectOne
     */
    function test_update() {
        $this->assertTrue($this->Sql->update(
            "A",
            array("a" => "editA", "b" => 7),
            array("id" => 2, "a" => "bar")
        ));
        $this->assertEquals(
            array("id" => 2, "a" => "editA", "b" => 7, "select" => 1),
            $this->Sql->selectOne("A", array("id" => 2))
        );
    }

    /**
     * @depends test_selectOne
     */
    function test_update_wraps_in_backticks() {
        $this->assertTrue($this->Sql->update(
            "A",
            array("select" => 2),
            array("select" => 1)
        ));
        $this->assertEquals(
            array("id" => 2, "a" => "bar", "b" => 6, "select" => 2),
            $this->Sql->selectOne("A", array("id" => 2))
        );
    }

    function test_update_fail() {
        $this->assertFalse(
            $this->Sql->update("A", array("a" => "bar"), array("id" => 1))
        );
    }

    /**
     * @depends test_selectOne
     */
    function test_delete() {
        $this->assertTrue($this->Sql->delete("A", array("id" => 2)));
        $this->assertFalse($this->Sql->selectOne("A", array("id" => 2)));
    }

    /**
     * @depends test_selectOne
     */
    function test_delete_wraps_in_backticks() {
        $this->assertTrue($this->Sql->delete("A", array("select" => 1)));
        $this->assertFalse($this->Sql->selectOne("A", array("id" => 2)));
    }

    function test_query_insert() {
        $this->Sql->query(
            "INSERT INTO A (id, a, b) VALUES (? ,?, ?)",
            array(3, "baz", 7)
        );
        $Results = $this->DB->query("SELECT * FROM A WHERE id='3'");
        $Results->setFetchMode(PDO::FETCH_ASSOC);
        $this->assertEquals(
            array("id" => 3, "a" => "baz", "b" => 7, "select" => null),
            $Results->fetch()
        );
    }

    function test_query_insert_id() {
        $id = $this->Sql->query(
            "INSERT INTO A (id, a, b) VALUES (? ,?, ?)",
            array(3, "baz", 7)
        );
        $this->assertEquals(3, $id);
    }

    function test_query_update() {
        $this->Sql->query("UPDATE A SET a = 'edit' WHERE id='1'");
        $Results = $this->DB->query("SELECT * FROM A WHERE id='1'");
        $Results->setFetchMode(PDO::FETCH_ASSOC);
        $this->assertEquals(
            array("id" => 1, "a" => "edit", "b" => 5, "select" => null),
            $Results->fetch()
        );
    }

    function test_query_delete() {
        $this->Sql->query("DELETE FROM A WHERE id='1'");
        $Results = $this->DB->query("SELECT * FROM A WHERE id='1'");
        $this->assertEquals(
            false,
            $Results->fetch()
        );
    }

    function test_results_toArray() {
        $ResultsObject = $this->Sql->query("SELECT * FROM A");
        $this->assertEquals(
            array(
                array("id" => 1, "a" => "foo", "b" => 5, "select" => null),
                array("id" => 2,"a" => "bar", "b" => 6, "select" => 1)
            ),
            $ResultsObject->toArray()
        );
    }

    /**
     * @depends test_results_next
     * @depends test_selectOne
     */
    function test_transaction_rollBack() {
        $this->Sql->beginTransaction();
        $this->Sql->query("INSERT INTO A (id) VALUES ('3')");
        $this->Sql->rollBack();
        $this->assertFalse($this->Sql->selectOne("A", array("id" => 3)));
    }

}
?>

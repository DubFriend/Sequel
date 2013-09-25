<?php
// require_once "sequel.php";
// require_once "sequelBase.php";

// class Sql_Table_Test extends Sql_Test_Base {
//     private $Table;

//     function setUp() {
//         parent::setUp();
//         $this->Table = $this->Sql->table("A");
//     }

//     function test_query_select() {
//         $this->assertEquals(
//             $this->Table->query("SELECT * wHerE id = 1")->next(),
//             array("id" => 1, "a" => "foo", "b" => 5)
//         );
//     }

//     function test_query_insert() {
//         $this->assertEquals(
//             3, $this->Table->query("INSERT (a, b) VALUES (?, ?)", array("baz", 7))
//         );
//     }

//     function test_query_update() {
//         $this->assertTrue($this->Table->query("UPDATE SET a = 'new' WHERE id = 1"));
//         $this->assertEquals(
//             'new', $this->Sql->selectOne("A", array('id' => 1))['a']
//         );
//     }

//     function test_query_delete() {
//         $this->assertTrue($this->Table->query("DELETE WHERE id = 1"));
//         $this->assertFalse($this->Sql->one("A", array('id' => 1)));
//     }
// }
?>

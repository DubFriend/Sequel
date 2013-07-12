<?php
//Wrapper to abstract the method of database access
//(currently implented with PDO)
class Sequel_Exception extends Exception {}

class Sequel {
    private $Connection, $table;

    function __construct($Connection, $defaultTable = null) {
        $this->Connection = $Connection;
        $this->table = null;
    }

    private function query_type($query) {
        $words = explode(" ", $query);
        if($words) {
            return strtoupper($words[0]);
        }
        else {
            throw new Sequel_Exception("Invalid Query");
        }
    }

    function query($query, array $values = array()) {
        $Statement = $this->Connection->prepare($query);
        $isSuccess = $Statement->execute($values);
        $type = $this->query_type($query);
        if($type === "SELECT") {
            return new Sequel_Results(array(
                "results" => $Statement,
                "query" => $query,
                "values" => $values,
                "connection" => $this->Connection
            ));
        }
        else if($type === "INSERT") {
            return $this->Connection->lastInsertId();
        }
        else {
            return $isSuccess;
        }
    }

    function get($table, array $whereEquals = array()) {
        $whereArray = array();
        foreach($whereEquals as $key => $value) {
            $whereArray[] = "$key = ?";
        }
        return $this->query(
            "SELECT * FROM $table WHERE " . implode(" AND ", $whereArray),
            array_values($whereEquals)
        );
    }

    function get_one($table, array $whereEquals = array()) {
        return $this->get($table, $whereEquals)->next();
    }

    function one($query, array $values = array()) {
        return $this->query($query, $values)->next();
    }
}



//Results Set Wrapper returned by calls to select
class Sequel_Results implements Iterator {
    private $Connection,
            $predicate,
            $values,

            $Results,
            $isIterationStarted = false,
            $count = null,
            $key = 0,
            $current;

    function __construct(array $fig = array()) {
        $this->Results = $fig['results'];
        $this->predicate = substr(
            $fig['query'],
            strpos(strtoupper($fig['query']), "FROM")
        );
        $this->values = $fig['values'];
        $this->Connection = $fig['connection'];
        $this->Results->setFetchMode(PDO::FETCH_ASSOC);
    }

    function to_array() {
        $arrayResults = array();
        while($row = $this->next()) {
            $arrayResults[] = $row;
        }
        return $arrayResults;
    }

    //rowCount doesnt work for sqlite :(
    function count() {
        if($this->count === null) {
            $sql= "SELECT count(*) " . $this->predicate;
            $sth = $this->Connection->prepare($sql);
            $sth->execute($this->values);
            $rows = $sth->fetch(\PDO::FETCH_NUM);
            $this->count = $rows[0];
        }
        return $this->count;
    }

    //Iterator Interface...

    function rewind() {
        if(!$this->isIterationStarted) {
            $this->isIterationStarted = true;
            $this->current = $this->Results->fetch();
        }
        else {
            throw new Sequel_Exception("Does not support rewind.");
        }
    }

    function valid() {
        return ($this->current !== false);
    }

    function current() {
        return $this->current;
    }

    function key() {
        return $this->key;
    }

    function next() {
        $this->isIterationStarted = true;
        $this->key += 1;
        $this->current = $this->Results->fetch();
        return $this->current;
    }
}
?>

<?php
//Wrapper to abstract the method of database access
//(currently implented with PDO)
class Sequel_Exception extends Exception {}

class Sequel {
    private $Connection;

    function __construct($Connection) {
        $this->Connection = $Connection;
    }

    function query($query, array $values = array()) {
        $Statement = $this->Connection->prepare($query);
        $isSuccess = $Statement->execute($values);
        switch($this->query_type($query)) {
            case "SELECT":
                return new Sequel_Results(
                    new Sequel_Counter(array(
                        "connection" => $this->Connection,
                        "values" => $values,
                        "query" => $query
                    )),
                    new Sequel_Iterator($Statement)
                );
                break;
            case "INSERT":
                return $this->Connection->lastInsertId();
                break;
            default:
                return $isSuccess;
        }
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

    function one($query, array $values = array()) {
        return $this->query($query, $values)->next();
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

    function begin_transaction() {
        return $this->Connection->beginTransaction();
    }

    function commit() {
        return $this->Connection->commit();
    }

    function roll_back() {
        return $this->Connection->rollBack();
    }
}





//Results Set Wrapper returned by calls to select
class Sequel_Results implements Iterator {
    private $Counter, $Iterator;
    function __construct($Counter, $Iterator) {
        $this->Counter = $Counter;
        $this->Iterator = $Iterator;
    }

    function to_array() {
        $arrayResults = array();
        while($row = $this->Iterator->next()) {
            $arrayResults[] = $row;
        }
        return $arrayResults;
    }

    function count() { return $this->Counter->count(); }

    //does not support rewind (here to make Iterator interface happy)
    function rewind() { return $this->Iterator->rewind(); }

    function valid() { return $this->Iterator->valid(); }
    function current() { return $this->Iterator->current(); }
    function key() { return $this->Iterator->key(); }
    function next() { return $this->Iterator->next(); }
}


class Sequel_Iterator implements Iterator {
    private $Results,
            $isIterationStarted = false,
            $key = 0,
            $current;

    function __construct($Results) {
        $this->Results = $Results;
        $this->Results->setFetchMode(PDO::FETCH_ASSOC);
    }

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
        return $this->current !== false;
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


class Sequel_Counter {
    private $Connection,
            $values,
            $query,
            $count = null;

    function __construct(array $fig = array()) {
        $this->Connection = $fig['connection'];
        $this->values = $fig['values'];
        $this->query = $fig['query'];
    }

    private function predicate() {
        return substr($this->query, strpos(strtoupper($this->query), "FROM"));
    }

    //rowCount doesnt work for sqlite :(
    function count() {
        if($this->count === null) {
            $sql= "SELECT count(*) " . $this->predicate();
            $sth = $this->Connection->prepare($sql);
            $sth->execute($this->values);
            $rows = $sth->fetch(\PDO::FETCH_NUM);
            $this->count = $rows[0];
        }
        return $this->count;
    }
}
?>

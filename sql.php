<?php
//Wrapper to abstract the method of database access
//(currently implented with PDO)
class Sequel_Exception extends Exception {}

class Sequel {
    private $Connection;

    function __construct($Connection) {
        $this->Connection = $Connection;
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
        $Results = $this->Connection->prepare($query);
        $Results->execute($values);
        $type = $this->query_type($query);
        if($type === "SELECT") {
            return new Sequel_Results(array(
                "results" => $Results,
                "query" => $query,
                "values" => $values,
                "connection" => $this->Connection
            ));
        }
        else if($type === "INSERT") {
            return $this->Connection->lastInsertId();
        }
    }
}


//Results Set Wrapper returned by calls to select
class Sequel_Results implements Iterator {
    private $Results,
            $Connection,
            $predicate,
            $values,
            $count = null,
            $key = -1,
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
        $this->next();
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

    function rewind() {
        if($this->key !== 0) {
            throw new Sequel_Exception("Sequel_Results does not support rewind.");
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
        $this->key += 1;
        $hold = $this->current;
        $this->current = $this->Results->fetch();
        return $hold;
    }
}
?>

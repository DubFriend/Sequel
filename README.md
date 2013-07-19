# Sequel
Sequel is a lightweight PDO wrapper that simplifies the PDO interface and enables "foreach" iteration.

```php
foreach($Sql->query("SELECT * FROM A") as $row) {
    ...
}
```
### Instantiate a Sequel object.
```php
$Sql = new Sequel(new PDO("your connection settings..."));
```


## Sequel Methods


### query
```php
$Sql->query(statement, values);
```
The query method takes a sql statement and array of corresponding values, and returns a response according to the statement type (select, update, insert, delete).

- **SELECT**
returns a Sequel_Results object.

```php
//returns rows with an id of 5
$results = $Sql->query("SELECT * FROM table WHERE id = ?", array(5));
```

- **UPDATE, DELETE**

returns true if the operation was successful, else returns false.
- **INSERT**
returns insert id.

### one
```php
$Sql->one("SELECT ... ", values);
```
returns the first row of a results set from a select statement

### select
```php
//equivalent of $Sql->query("SELECT * FROM table WHERE id = ?", array(5))
$Sql->select("table", array("id" => 5));
```

### selectOne
```php
$Sql->selectOne("table", array("id" => 5));
```
returns first result of select query.

### insert
```php
//equivalent of $Sql->query("INSERT INTO table (colA, colB) VALUES (?, ?)", array("foo", "bar"));
$insertId = $Sql->insert("table", array("colA" => "foo", "colB" => "bar"));
```

### update
```php
//equivalent of $Sql->query("UPDATE table SET a = ? WHERE id = ?", array("baz", 5));
$isSuccess = $Sql->update("table", array("a" => "baz"), array("id" => 5));
```

### delete
```php
//equivalent of $Sql->query("DELETE FROM table WHERE id = ?", array(5));
$isSuccess = $Sql->delete("table", array("id" => 5));
```

### beginTransaction
calls PDO's beginTransaction
```php
$Sql->beginTransaction();
```

### commit
calls PDO's commit
```php
$Sql->commit();
```

### rollBack
calls PDO's rollBack
```php
$Sql->rollBack();
```


## Sequel_Results Methods


Sequel_Results objects are returned by Sequel's select methods.
```php
$Results = $Sql->query("SELECT ...", array(...));
```
and
```php
$Results = $Sql->select(...);
```

### toArray
returns all results as a PHP array
```php
$Results->toArray();
```

### count
returns number of results
```php
$Results->count();
```

### rewind
rewind should not be used (will throw if not the first call to rewind and before any calls to the "next" method).  It is only here to satisfy the Iterator interface (and thus allow Sequel_Results to be used in foreach loops).

### valid
returns true as long as the current cursor position is pointing at a valid element.

### current
returns the current element.

### key
returns the cursor's index position.

### next
Advances cursor position and returns the next row. Returns false when at the end of the list.
```php
$row = $Results->next();
```


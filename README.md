# Sequel
## Sequel is a lightweight PDO wrapper that simplifies the PDO interface and enables "foreach iteration"

### Example
```php
foreach($this->Sql->query("SELECT * FROM A") as $row) {
    //do stuff
}
```

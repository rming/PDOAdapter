## TESTS

###database migrate
```
#need permission to write logs
chmod 777 logs
#create table
php dbMigrate.php createTable
#insert row
php dbMigrate.php insert
#insert 100 rows
php dbMigrate.php insertBatch

```

###instant model test case
````
php testInstantModel.php
````

###model test case
````
php testModel.php
````

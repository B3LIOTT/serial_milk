# Serial Milk

Although some people don't understand that putting milk after cereal is a no-brainer (I don't put milk in my cereal), exploiting php's deserialization is fun until you have to write your serialized object.

# Usage
```bash
php serial_milk.php <php file> <class name> [arg1 arg2 ...]
```

Params:
- php file is the file containing the class you want to serialize
- class name is the class name (no way)
- [arg1, ...] are needed args, it is optionnal (if some args are missing this program will let you define them)


A python version is available for those who can't use php:
```bash
Usage: serial_milk.py <php file>
```

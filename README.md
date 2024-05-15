# flags

For example usage, see the [example](example/example.php)

## special cases

- To set a bool true simply use it (e.g. `-var`). To set it's value it is required to use the equal sign (e.g. -var=false)
- `-help` or `--help` will always print DocStings
- combing custom types, nullables, default values, and shadow methods can be tricky to make sure that everything that OUGHT to be nullable.

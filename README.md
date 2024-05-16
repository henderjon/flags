# flags

For example usage, see the [example](example/example.php)

## special cases

- To set a bool true simply use it (e.g. `-var`). To set it's value it is required to use the equal sign (e.g. -var=false)
- `-help` or `--help` will always print DocStings
- when combing custom types, nullables, default values, and shadow methods, it can be tricky to make sure that everything that OUGHT to be nullable, is so
- errors/exception in the vein of `Argument #1 ($v) must be of type $TYPE, null given` that means that the expected type of the property, shadow function parameter, and shadow function return type don't match. Specifically, nullable types require everything else to be nullable, especially when it's a complex type that is nullable and defaults to null as a value.

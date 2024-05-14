# flags

## simple usage:

```php

class opts implements Flags\FlagDocInterface {
	public readonly string $dir;

	public function doc(): string {
		return "Usage: script.php --dir <directory>\n";
	}
}

$options = (new Flags\Flags(new opts))->parse($argv);
```

## not simple usage:

```php

class opts implements Flags\FlagDocInterface {
	public readonly string $dir;
	public readonly string $upper;

	public function upper(string $v):string{
		return strtoupper($v);
	}

	public function doc(): string {
		return "Usage: script.php --dir <directory>\n";
	}
}

$options = (new Flags\Flags(new opts))->parse($argv);
```

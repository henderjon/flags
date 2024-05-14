# flags

## simple usage:

```php
use Flags\FlagDocInterface;

class opts implements FlagDocInterface {
	public readonly string $dir;

	public function doc(): string {
		return "Usage: script.php --dir <directory>\n";
	}
}

$options = (new Flags\Flags(new opts))->parse($argv);
```

## not simple usage:

```php
use Flags\FlagDocInterface;

class opts implements FlagDocInterface {
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

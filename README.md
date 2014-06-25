Assetic Codekit Filters
=======================

This project is to support the proprietary and [undocumented][1] Codekit extensions so
that you can build a Codekit project without that application.

[1]: https://incident57.com/codekit/help.html

Javascript and CoffeeScript
---------------------------

For these languages, Codekit supports prepending and appending other scripts within the
output. When a file contains comments like these:

```javascript
// @codekit-prepend "a.coffee"
// @codekit-append "b.js"
```

```coffee
# @codekit-prepend "a.coffee"
# @codekit-append "b.js"
```

You can use this filter to support those directives.

For example:

```php
<?php

$coffee = new \Assetic\Filter\CoffeeScriptFilter('/usr/local/bin/coffee');
$coffee->setBare(true);

$asset = new FileAsset('script.coffee', array(
		new \Bangpound\Assetic\Filter\CodekitCoffeeScriptFilter($coffee),
		$coffee,
));
```

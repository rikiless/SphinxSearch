Sphinx Search
===

Handler for Sphinx API Client. It contains most used functions to setup field weights, filter, sorting and multi-quering.

Tested on Sphinx Server 2.1.9 / 2.2.4 and sphinx PECL package 1.3.2.

Requirements
---

This package requires PHP 5.4.

- [Sphinx PHP Client 2.0.1](http://sphinxsearch.googlecode.com/svn/tags/REL_2_0_1/api/)

Installation
---

The best way to install this package is using [Composer](https://getcomposer.org/)

```sh
$ composer require "rikiless/sphinx-search:@dev"
```

### Nette Framework

If you are using Nette Framework you can simply register service:

```neon
sphinx:
		host: localhost
		port: 9312

services:
	- Rikiless\Sphinx\Search(%sphinx%)
```

In presenter:

```php
class Presenter ...
{

	/** @var Rikiless\Sphinx\Search @inject */
	public $fulltextSearch;

}
```

Use
---

#### Examples

Simple query:

```php
$fulltext = new Rikiless\Sphinx\Search([
    'host' => 'localhost',
    'port' => 9312
]);

try {
 	/** @var Rikiless\Sphinx\Data $results */
    $results = $fulltext->query('search something');
	var_dump($results->getMatchesList());
	
} catch (Rikiless\Sphinx\Exception $e) {
    print $e->getMessage();
}
```

Multiple queries with basic setup:

```php
$search = 'search something';

$this->fulltextSearch->setIndex('myindex');
$this->fulltextSearch->setLogComment(sprintf('Fulltext query on %s', $this->domain));
$this->fulltextSearch->setFieldWeights([
    'name' => 10,
	'content' => 5,
	'subject_name' => 3,
	'city' => 2,
	'contact_person' => 2
]);

$this->fulltextSearch->resetFilters();
$this->fulltextSearch->setFilterRange('position', 0, 19999999);
$this->fulltextSearch->addQuery($search);

$this->fulltextSearch->resetFilters();
$this->fulltextSearch->setFilterRange('position', 20000000, 29999999);
$this->fulltextSearch->addQuery($search);

try {
    $results = $this->fulltextSearch->runQueries();
} catch (Rikiless\Sphinx\Exception $e) {
    print $e->getMessage();
}

foreach ($results as $row) {
	/** @var Rikiless\Sphinx\Data $row */
	var_dump($row->getMatches());
}
```

Sources
---

- http://sphinxsearch.com
- http://php.net/manual/en/book.sphinx.php
- http://sphinxsearch.com/wiki/doku.php?id=php_api_docs
- http://pecl.php.net/package/sphinx

# amphp-mysqli-query

`zobe/amphp-mysqli-query` is a non-blocking mysqli query processor built on the [amp concurrency framework](https://github.com/amphp/amp).


**Requirements**

- PHP 7
- [`amphp/amp`](https://github.com/amphp/amp) 1.2
- mysqli
- mysqlnd


**Project Goal**

- Perform parallel processing of QUERIES only.
- Do not crash.
- Catch all errors reasonably and deliver to caller.


**Installation**

```bash
$ composer require zobe/amphp-mysqli-query
```

**License**

The MIT License (MIT). Please see [LICENSE](./LICENSE) for more information.


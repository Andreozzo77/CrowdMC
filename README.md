This section comprises some of the coding and maintaining practices that we adopt to keep code maintainability and readability. This will serve as a reference for future contributors of this repo.

1. [PSR-2 Coding Style](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) and [PSR-4 Autoloader](https://github.com/php-fig/fig-standards/blob/97df150a0dcdb7f035d61402e1a36fae5c0acc4b/accepted/PSR-4-autoloader.md) by PHP Framework Interoperability Group.
2. Multi-byte functions. `strtolower()`, `strtoupper()`, `strlen()`, `preg_match()`, etc. got superseded by `mb_strtolower()`, `mb_strtoupper()`, `mb_strlen` and `mb_ereg_replace()`, respectively. (There is no such equal to `str_replace()`, `trim()`...). The full function list is available [here](https://www.php.net/manual/en/ref.mbstring.php). We enforce these to avoid [unexpected results](https://stackoverflow.com/questions/6722277/why-use-multibyte-string-functions-in-php) by operating multi-byte strings with single-byte functions. Examples are Japanese, Chinese and Korean. `žščř`
3. Insert backslash to call built-in PHP functions (references the global namespace). More are found [here](https://stackoverflow.com/questions/4790020/what-does-a-backslash-do-in-php-5-3).
4. All commands should extend `kenygamer\Core\command\BaseCommand`.
5. The ItemFactory is no longer used. Call `kenygamer\Core\util\ItemUtils::get()` instead.
6. Do not hardcode messages. Instead, retrieve messages from language containers instead.
7. Encapsulate Import / `use` statements in curly brackets. This will save time i.e in namespace refactoring.
8. Use full representation of the class in variable naming, i.e `$player` for Player, `$event` for Event, `$block` for Block, and `$tile` for Tile.
9. Use PHPDoc wherever possible, i.e `@var`, `@param`, `@return`, `@throws`, `@see`, `@deprecated`, for documentation purposes. PHPDoc taglines like `@class`, `@package`, `@author`, `@website`, `@link`, `@copyright` re not necessary though. Document arrays in the form `/** @var array key => value */` where key and value are the types of the key and value. Multi-dimensional arrays DO NOT document dimensions.
10. Configuration files are obsolete. Prefer `kenygamer\Core\util\SQLiteConfig` and use only `Config` files for manual configuration, such as kits, server settings, etc.
11. Server resets do not need intervention. Use the `reset.php` script in `environment_files/`. This should also be updated regularly.
12. To commit, we use the `commit.php` script. On the other hand, we reserve `git` commands to actions like switching through branches with `git checkout`.

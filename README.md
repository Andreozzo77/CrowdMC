#  CrowdMC
Home of CrowdMC, a factions/prison server for Minecraft: Broken Edition.

## Synopsis
The CrowdMC files are now open source. A couple of tips before you start tinkering with the largest server leak in MCPE history.

1. Not all is sparkles in [CrowdMC_worlds](https://github.com/ecokevinkevin/CrowdMC_worlds). These worlds are authority of a third-party who has legal rights over these worlds. Its resale is not only a crime. Files are public. However, this is possible thanks to a legal loophole, which prohibits their resale, but not their distribution. This would be antithetical, so don't take my example. At this very time, worlds cost a rough of $1,000.

2. The [CrowdMC/wiki](https://github.com/CrowdMC/wiki) repository outlines very basic information for rookies, not necessarily least important, to gain an insight into the CrowdMC gameplay.

3. The [CrowdMC/bugs](https://github.com/CrowdMC/bugs) repository is a read-only view of the bugs in CrowdMC. The in-game server has the potential to report bugs directly to the VCS. This repository has existed for a long time, due to its one-sided nature, it was only recently opened.

4. The [CrowdMC/lang](https://github.com/CrowdMC/lang) repository is likely one of the repos that most deserves a great deal of praise. The CrowdMC i18n internationalization update is known by only a small fraction of people and is the largest step forward to sustainability software brought to the world of Minecraft.  This update required making huge, unprecedented efforts in a work pipeline of more than a month.  Broadly speaking, this update allows you to chat, that is, receive and send messages, in your language.  Not only that, but the entire server is available in your desired language. This has never been done before, no doubt, it cost a fortune, and it did not have time to escalate. There are 1,467 strings at the time of writing, with a reductionist approach.

5. Finally yet importantly, is the much-vaunted codebase of [CrowdMC](https://github.com/ecokevinkevin/CrowdMC). 

### Repo overview
TL;DR: All you need to make your own CrowdMC, can be found here.

The long story:

The hierarchy consists of a complex structure, as the most infallible proof of our exquisite development.  A quick glance of the above:

- `./github/workflows`: Contains files related to interaction with GitHub actions.
- `.circleci/`: Contains configuration files related to Continuous Integration.
- `BlockPets/`: This is a fork of BlockPets, with many bug patches to support the latest PM version. This is not in-line with the original BlockPets, thus it is self-maintained. 
- `FactionsPro`: This is fork of FactionsPro, with many custom additions. This is not in-line with the original FactionsPro, thus it is self-maintained.
- `MineReset`: This is fork of MineReset, with many custom additions. This is not in-line with the original MineReset, thus it is self-maintained.
- `PureEntitiesX`: This is fork of PureEntitiesX, with many bug patches to support the latest PM version. This is not in-line with the original PureEntitiesX, thus it is self-maintained. 
- `Specter`: This is fork of Specter, with many custom additions. This is not in-line with the original Specter, thus it is self-maintained.
- `CustomEnchants`: This is fork of CustomEnchants, with many custom additions and bug fixes. This is not in-line with the original CustomEnchants, thus it is self-maintained.
- `Core`: This is the core plugin, intended for primitive functions used across this plugin and other plugins (i.e `\kenygamer\Core\Main::mt_rand()` drop-in replacement of the Mersenne Twister algorithm, by using instead [atmospheric noise](https://www.random.org/); the permission manager, or even mini-games). All code follows a pragmatic style that adheres to PSR-2 and PSR-4 Coding Styles.
- `LegacyCore`: This is the previous core plugin. Code authors are both self and outsider hands, which explains some scattered protocol violations. This was home of a major rewrite that was taking place up to the last day.  
- `environment_files/`: This tree is a bit of mystic and esoteric to the Minecraft world, but it is an approach highly considered by professional software engineers. To put it in perspective, CrowdMC operated in a test environment (development) and a runtime environment (production). A brief description of what comes in:

  * `/production/`:

    * `bot/`: The multi-purposed Discord bot of CrowdMC. This bot features moderation, tickets, logging, chat relay, RCON, games, and so much more.  One of the most notable features is the direct integration with PayPal, which allows you to create a whole payment gateway logic, just like, and inspired by Buycraft.

    * `elitestar/`: This directory contains some plugins and datafiles required by the main server.

    * `html/`: This directory has the Apache web server files of the main server.  Inside it you can find the website, and most importantly the API. The API follows a RESTful design, and is primarily used to interconnect the in-game server and the bot (profiles, skins, chat relay, server status, etc.).

    * `pocketmine/`: This directory has files that are not in-line with the PM workflow. This includes but is not limited to performance enhancements, bug fixes, and premature PRs.

    * `status.crowdmc.us.to/`: This directory has files related to the status monitoring page of CrowdMC.  This alerts if some systems are not working.  It also monitors the player list and the network upstream / downstream speed.

    * `system/`: This directory has files related to the Linux operating system.

    * `html/timeonline/`: My great attachment to deontology eventually prompted me to create a method to track my staff activity, and graph this information.  This directory serves that purpose.

    * `EliteStar.zip`: This is the resource pack used by EliteStar.zip.  This resource pack must stay under a specific size, since the client requests all downloads at the same time instead of one-by-one like it used to, causing bandwidth overloads.

    * `reset.php`: We use this file to reset the server datafiles, guaranteeing data integrity.

  * `development/`:

    * `pocketmine/`: This directory has files used to simplify interaction with VCS. 

    * `service/`: This directory has daemon files used in the development server. 

    * `plugins/filemap.yml`: This configuration file delineates which files are mapped by GitHubConnection. 

    * `html/`: This directory has various files used for testing and debugging purposes.

- `phpstan-baseline.neon`: This is a file containing the PHPStan rules, used for static analysis.  PHPStan is used in conjunction with CircleCI.

### 1. How long did you pour into CrowdMC? 
I spent thousands of hours working at CrowdMC.

### 2. How many people worked in CrowdMC?
I was a solo developer. At times, I even moderated the server on my own, like when I didn't feel right moderators had access to built-in commands in PM like `/ban` (a moderation system came upon later with proper logging). The server also ran out of my pocket.

### 3. What coding tools to use for CrowdMC? 
I coded in precarious conditions, due to simply never being able to afford a computer. This is a major setback for modern programmers who are fairly familiar with auto-completion tools. Believe me, if you have a computer and use a proper IDE, your fingers will thank you.

### 4. What is the value of CrowdMC? 
The value of CrowdMC is the legacy it leaves to succeeding servers. CrowdMC had a jump to fame, but it was largely sneaky. To top it off, popular server owners hovered over the server in off-peak hours, stealing ideas and communications. This was the inducement to run the server in stealth mode, which caused the player base to downfall. At such point, we had reached an impasse, and tried to sell the server.  We did a public tender in August 2020, but bidding closed with the highest bid being $1,500. The tender is later canceled, and the CrowdMC files are now public after almost a year.

### 5. What do I need to make my own CrowdMC?

You will need a minimum knowledge of PHP programming, otherwise you will only suffer. Alongside that, you will need experience in Linux systems to work with multiple environments, and experience in web application architectures to work with abstraction layers like APIs.

Keep in mind, CrowdMC isn't your typical server where you can simply drag and drop plugins and voilà (we made CrowdMC a «scalable and maintainable server»), so you may need to juggle to get things rolling (we heralded this since archaic times.)

## Coding conventions
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

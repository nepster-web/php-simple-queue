PHP Simple Queue Change Log
===========================

A changelog of all notable changes made to this library.

- *ENH*: Enhance or modify
- *FIX*: Bug fix or a small change

<br>

1.0.0 under development
----------------------


1.0.0-RC under development
----------------------


1.0.0-Beta under development
----------------------


1.0.0-Alpha-5 May 9, 2021
---------------------------
- *FIX*: [#28](https://github.com/nepster-web/php-simple-queue/issues/28) - set format for datetime


1.0.0-Alpha-4 April 7, 2021
---------------------------
- *ENH*: [#22](https://github.com/nepster-web/php-simple-queue/issues/22) - implementation [Context](./src/Context.php) for jobs and processors
- *ENH*: Improved documentation


1.0.0-Alpha-3 March 31, 2021
---------------------------
- *ENH*: [composer.json](./composer.json) updating package versions
- *ENH*: Implemented abstraction for ([Transport](./src/Transport/DoctrineDbalTransport.php))
- *ENH*: Config expanded (job registration and processor registration)
- *ENH*: Refactoring class architecture (tests updating)
- *ENH*: Improved documentation
- *FIX*: Fixed data loss when redelivery message


1.0.0-Alpha-2 March 17, 2021
----------------------------
- *ENH*: added work with jobs
- *ENH*: added work with processors
- *ENH*: added serializer fo message body
- *ENH*: added [MessageHydrator](./src/MessageHydrator.php) (for change system properties)
- *ENH*: added base [Config](./src/Config.php)
- *ENH*: expanded consumer work algorithms
- *ENH*: increased test coverage
- *ENH*: improved documentation
- *ENH*: updated Dockerfile in example (strict version for: php, composer, xdebug)


1.0.0-Alpha March 13, 2021
--------------------------
- *ENH*: Repository configuration (travis, scrutinizer, php cs, etc)
- *ENH*: Add simple example with consume and produce
- *ENH*: Ability to run example with docker


Release February 15, 2021
-------------------------
- Create guide
- Create example
- Create tests
- Initial release
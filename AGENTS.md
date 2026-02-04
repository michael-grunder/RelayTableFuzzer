## General instructions

* Prefer modern PHP syntax and idioms. We won't run this on anything < 8.4.
* Prefer modularity and generic code over duplication unless there is a good
  performance reason for it.
* Run vendor/bin/phpstan analyze and fix any reported issues.
* Run vendor/bin/phpunit to make sure tests pass.
* Remember to update `README.md` if the changes change what is documented.
* After each change update  `CHANGELOG.md`. As changes are added they go
  under `## Unreleased` and then at time of tag will be formalized.

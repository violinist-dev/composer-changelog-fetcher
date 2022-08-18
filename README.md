# composer-changelog-fetcher

[![Violinist enabled](https://img.shields.io/badge/violinist-enabled-brightgreen.svg)](https://violinist.io)
[![Test](https://github.com/violinist-dev/composer-changelog-fetcher/actions/workflows/test.yml/badge.svg)](https://github.com/violinist-dev/composer-changelog-fetcher/actions/workflows/test.yml)
[![Coverage Status](https://coveralls.io/repos/github/violinist-dev/composer-changelog-fetcher/badge.svg?branch=master)](https://coveralls.io/github/violinist-dev/composer-changelog-fetcher?branch=master)

## Installation

You probably want this either as a dev dependency, in which case you would install it like so:

```
composer require --dev violinist-dev/composer-changelog-fetcher
```

Or you might want to install it as a global tool, in which case you would do this:

```
composer global require violinist-dev/composer-changelog-fetcher
```

## Usage

You probably want to invoke this within a project. Let's say you run `composer outdated`:

```
symfony/http-foundation    v3.4.22    v3.4.23    Symfony HttpFoundation Component
```

...and then you want to know what changed. Let's assume your bin directory is in `vendor/bin/`:

```
./vendor/bin/changelog-fetcher fetch -p symfony/http-foundation -f v3.4.22 -t v3.4.23
```

..then you might get output like this:

```
9a96d77: Apply php-cs-fixer rule for array_key_exists() (https://github.com/symfony/http-foundation/commit/9a96d77)
```

You can also get this output as json (in this example piped into `jq` for readability):

```
./vendor/bin/changelog-fetcher fetch -p symfony/http-foundation -f v3.4.22 -t v3.4.23 -d ~/Sites/violinist -o json | jq
[
  {
    "hash": "9a96d77",
    "message": "Apply php-cs-fixer rule for array_key_exists()",
    "link": "https://github.com/symfony/http-foundation/commit/9a96d77"
  }
]

```

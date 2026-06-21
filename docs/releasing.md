# Building and Releasing

## Build PHAR locally

Requires [Box](https://github.com/box-project/box):

```bash
composer global require humbug/box   # once
composer build-phar
php build/modulith.phar list
```

## Releasing

Push a `v*` tag to trigger the [Release workflow](../.github/workflows/release.yml), which builds `modulith.phar` and attaches it to a GitHub release automatically.

```bash
git tag v1.0.0
git push origin v1.0.0
```

### Manual release

```bash
export GITHUB_TOKEN=ghp_...
composer publish-release 1.0.0
# or
bash scripts/publish-release.sh --from-tag v1.0.0
```

## CI

The [CI workflow](../.github/workflows/ci.yml) runs on every push and pull request:

- PHPUnit on PHP 8.4 and 8.5
- PHPCS (PSR-12)
- PHPStan (level 8)

## Publishing to Packagist

1. Push this repository to its own GitHub repository
2. Submit `modular-monolith/installer` on [packagist.org](https://packagist.org)
3. Tag a release (`v1.0.0`) — Packagist picks it up automatically

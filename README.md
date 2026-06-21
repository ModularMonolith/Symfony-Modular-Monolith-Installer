# Modular Monolith Installer

Interactive CLI for creating projects from [`modular-monolith/symfony-application`](../).

Publish this directory as its own Composer package (`modular-monolith/installer`) in a separate repository, or install locally for development.

## Install globally

```bash
composer global require modular-monolith/installer
```

Ensure Composer's global `bin` directory is on your `PATH`.

## Install PHAR (no Composer)

Download the PHAR from [GitHub Releases](https://github.com/ModularMonolith/Symfony-Application/releases) (assets named `modulith.phar`):

```bash
curl -fsSL -o modulith.phar \
  https://github.com/ModularMonolith/Symfony-Application/releases/latest/download/modulith.phar
chmod +x modulith.phar
./modulith.phar new my-project
```

## Usage

```bash
modulith new my-project
```

Options:

| Option | Description |
|--------|-------------|
| `--repository=URL` | VCS URL when the template is not on Packagist (default: GitHub template repo) |
| `-n, --no-interaction` | Skip prompts; keep TodoList example and auto-generate `APP_SECRET` |

## What it does

1. Runs `composer create-project modular-monolith/symfony-application <directory>`
2. Optionally sets `APP_SECRET` in `.env`
3. Optionally removes the TodoList example module from `src/` and `tests/`

## Local development

```bash
cd installer
composer install
./bin/modulith new /tmp/my-test-project
```

## Build PHAR locally

Requires [Box](https://github.com/box-project/box):

```bash
cd installer
composer global require humbug/box   # once
composer build-phar
php build/modulith.phar list
```

## Releasing

Releases are automated when an installer tag is pushed to this repository.

### Tag convention

```bash
git tag installer-v1.0.0
git push origin installer-v1.0.0
```

The [Installer Release](../.github/workflows/installer-release.yml) workflow will:

1. Install production dependencies
2. Build `build/modulith.phar` with [Box](https://github.com/box-project/box)
3. Create or update a GitHub release with the PHAR attached

### Manual release (maintainers)

```bash
cd installer
export GITHUB_TOKEN=ghp_...
composer publish-release 1.0.0
# or
bash scripts/publish-release.sh --from-tag installer-v1.0.0
```

## CI

[Installer CI](../.github/workflows/installer-ci.yml) runs on changes under `installer/`:

- Verifies the CLI boots
- Builds the PHAR with Box
- Runs `modulith.phar list`

## Publishing to Packagist

1. Move this `installer/` folder to its own Git repository (optional)
2. Tag a release (`installer-v1.0.0` or package-specific tag)
3. Submit `modular-monolith/installer` on [packagist.org](https://packagist.org)

The main template package is `modular-monolith/symfony-application` in the parent repository.

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Extension Overview

TYPO3 extension (`ok_exchange365_mailer`) that sends emails via Microsoft Exchange 365 using the MS Graph API instead of SMTP. Uses the OAuth 2.0 client credentials flow.

**Compatibility:** TYPO3 12.4 LTS – 14.x | PHP 8.1 – 8.5

The extension is only two PHP classes; the real complexity is in *how configuration reaches the transport* and in the parallel TypoScript / site-set configuration paths.

## Architecture

### Core Components

- `Classes/Mail/Transport/Exchange365Transport.php` — Symfony `AbstractTransport` implementation. Resolves configuration, authenticates, converts the message, and calls Graph.
- `Classes/Lowlevel/EventListener/ModifyBlindedConfigurationOptionsEventListener.php` — blinds `transport_exchange365_{tenantId,clientId,clientSecret}` in the backend Configuration module (shows `ab******yz`).

### How the transport is selected

There is no DSN factory. TYPO3 activates this transport when `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport']` is the **fully-qualified class name** `OliverKroener\OkExchange365\Mail\Transport\Exchange365Transport` (via env var `TYPO3_CONF_VARS__MAIL__transport`, `settings.php`, or `config.mail.transport` in TypoScript). TYPO3's mailer instantiates the class directly, passing `$GLOBALS['TYPO3_CONF_VARS']['MAIL']` as the `$mailSettings` constructor argument — which is exactly why the class is excluded from the `Configuration/Services.yaml` autoloading resource. Do not re-add it to autowiring. The `__toString()` return value `exchange365api` is only a display name.

### Configuration resolution (`getConfiguration()`)

This is the part most likely to be broken by a well-meaning edit:

1. **Mail settings are the baseline** — `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_exchange365_*']`, normalised into short keys (`tenantId`, `clientId`, …) by `getMailSettingsConfiguration()`. This is the only source available in backend, CLI and scheduler contexts.
2. **Frontend TypoScript overlays it per key** — `plugin.tx_okexchange365mailer.settings.exchange365.*`, read only when `$GLOBALS['TYPO3_REQUEST']` has `applicationType === 1`. Site-set settings arrive through this same path, because site settings are flattened into TypoScript constants.

`getTypoScriptConfiguration()` reads the `frontend.typoscript` request attribute unconditionally. On TYPO3 12.4.0 — the one supported version predating that attribute — it is absent, so the method returns `null` and the mail-settings baseline stands. The old `version_compare()` gate and its `$GLOBALS['TSFE']->tmpl->setup` fallback were removed deliberately; `TSFE->tmpl` no longer exists on 13/14, so do not restore them.

Two rules encoded in `getConfiguration()` that must survive refactors:

- An **empty** TypoScript value means "not configured here" and must not shadow the mail setting — hence the `$value !== '' && $value !== null` guard rather than a plain `??`/array merge.
- `saveToSentItems` is **exempt** from that guard: a site setting of `false` flattens to an empty constant and genuinely means false.

`validateConfiguration()` hard-requires `tenantId`, `clientId`, `clientSecret`. Everything else has a fallback chain.

### Sender resolution

`graphSenderUserId` (the mailbox in the `/users/{id}/sendMail` path) is deliberately decoupled from the message `From` header, so *Send As* / *Send On Behalf* works. Resolution order:

```
conf.graphSenderUserId → $graphMessage['from'] → conf.fromEmail → MAIL.defaultMailFromAddress → RuntimeException
```

`!empty()` is used instead of `??` throughout, because `getMailSettingsConfiguration()` returns empty strings (not nulls) for unset values.

### Why the `(string)` casts exist

Both classes declare `strict_types=1`, and `$conf` is typed `array<string, mixed>` — its values come from operator-supplied configuration. The casts on `tenantId`/`clientId`/`clientSecret` (into `ClientCredentialContext(string, string, string)`), on the resolved `$graphSenderUserId` (into `byUserId(string)`), and on the blinded credential in the event listener (into `mb_substr()`) are what keep a non-string config value coercing as it always did instead of raising a `TypeError`. **Neither tool below catches their removal** — see the tooling gotchas.

### Frontend configuration: two mutually exclusive paths

| Path | TYPO3 | Files |
|---|---|---|
| Site set `oliverkroener/ok-exchange365-mailer` (preferred, since 4.3.0) | 13 / 14 | `Configuration/Sets/Exchange365Mailer/{config,settings.definitions}.yaml`, `setup.typoscript` |
| Static template *[kroener.DIGITAL] Exchange 365 Mailer* | 12 | `Configuration/TypoScript/{constants,setup}.typoscript`, registered in `Configuration/TCA/Overrides/sys_template.php` |

The set's `setup.typoscript` is a one-line `@import` of the static template's `setup.typoscript` — the site-setting keys are named identically to the TypoScript constants so the mapping file is reused verbatim. **When adding or renaming a setting, change all four places**: `constants.typoscript`, `setup.typoscript`, `settings.definitions.yaml`, and `getMailSettingsConfiguration()`.

Integrators must use the set **or** the static template, never both: site settings are applied to constants *before* template records, so a lingering static template overwrites set values with its empty defaults.

The set's labels are inline English strings in `settings.definitions.yaml`. TYPO3 core (`YamlSetDefinitionProvider`) also supports a `labels:` key — or an auto-detected `Configuration/Sets/<Set>/labels.xlf` — deriving `settings.<key>`, `settings.description.<key>` and `categories.<key>` trans-unit IDs automatically. That was verified on the v14 core in `vendor/` only; **confirm it behaves the same on 13.4 before switching**, or the settings UI renders raw `LLL:` keys there. The extension has no `Resources/Private/Language/` at all today — nothing else in it is user-facing (no backend module, TCA fields, Fluid templates or flash messages), only developer-facing exception and log text.

## Important Behavior

- **Sender display name**: Graph uses the **Display name** configured on the mailbox in Exchange Online. `MAIL.defaultMailFromName` / `defaultMailFromAddress` have **no effect** on the name recipients see; it must be changed in the Microsoft 365 / Exchange Admin Center.
- **Errors are wrapped**: `doSend()` catches everything, logs at `alert` level, and rethrows a `\RuntimeException`. Graph's original message is appended to the text — keep it, it is the only diagnostic integrators get.

## Dependencies

- `microsoft/microsoft-graph` ^2 — Graph SDK
- `oliverkroener/ok-typo3-helper` ^3 — provides `MSGraphMailApiService::convertToGraphMessage()`, which turns the Symfony `SentMessage` into `['from' => …, 'message' => …]`. Message-format bugs (attachments, HTML parts, recipients) usually belong in *that* package, not here.

## Development Commands

There is **no test suite**. PHP tooling runs **through DDEV from the parent project root** (`/home/oliver/typo3-14`), where `phpstan/phpstan`, `saschaegerer/phpstan-typo3` and `typo3/coding-standards` are shared dev dependencies — they are not installed inside this package.

```bash
# Static analysis — level 8, config at packages/ok_exchange365_mailer/phpstan.neon
ddev exec vendor/bin/phpstan analyse -c packages/ok_exchange365_mailer/phpstan.neon

# Code style — TYPO3 CGL via the root .php-cs-fixer.dist.php
ddev exec vendor/bin/php-cs-fixer fix packages/ok_exchange365_mailer
ddev exec vendor/bin/php-cs-fixer fix packages/ok_exchange365_mailer --dry-run --diff
```

Both are currently green (PHPStan: no errors; fixer: 0 of 5 files) — but two things they will *not* tell you:

- **The TYPO3 CGL preset does not enforce `declare(strict_types=1)`.** `\TYPO3\CodingStandards\CsFixerConfig` is `@PER-CS1.0` + `@DoctrineAnnotation`, neither of which includes `declare_strict_types` — a file missing it passes the fixer silently. Check it by eye on new classes.
- **PHPStan level 8 does not flag `mixed` → `string`.** That is a level-9 (`checkExplicitMixed`) check, so every `$conf[...]` value flowing into the Graph SDK's typed parameters is invisible at the configured level.

Also expected, not a misconfiguration: the run prints *"Paths from configuration have been overridden"* because the root `.php-cs-fixer.dist.php` finder already covers `packages/` and the path argument supersedes it.

Documentation is rendered locally with the official TYPO3 render-guides Docker image (run from this package directory, no DDEV):

```bash
make docs        # pulls the latest image, renders Documentation/ → Documentation-GENERATED-temp/
make docs-fast   # same without --pull
make docs-watch  # inotify loop, re-renders on change (needs `make watch-install` once)
```

`Documentation-GENERATED-temp/` is gitignored output — never edit it, edit `Documentation/*.rst`.

## Releasing

The version appears in five places and they drift easily. Find them all with `grep -rn "<version>" . --exclude-dir=Documentation-GENERATED-temp --exclude-dir=.git`; for 4.3.0 they are:

- `composer.json` → `version`
- `ext_emconf.php` → `version`
- `README.md` → the version badge URL
- `Documentation/guides.xml` → `release="…"`
- `Documentation/**/*.rst` → any `..  versionadded::` directive (currently `Configuration/SiteSets.rst`)

The `typo3-toolkit:typo3-bump-version` skill exists to keep these in sync. Note that `composer validate` warns about the `version` field being present — that is intentional here for TER/`ext_emconf.php` parity, not something to "fix".

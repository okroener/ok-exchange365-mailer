# Microsoft Exchange 365 Mailer (ok_exchange365_mailer)

[![TYPO3 12](https://img.shields.io/badge/TYPO3-12-orange?logo=typo3)](https://get.typo3.org/version/12)
[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange?logo=typo3)](https://get.typo3.org/version/13)
[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange?logo=typo3)](https://get.typo3.org/version/14)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/version-4.3.0-green)](https://github.com/oliverkroener/ok_exchange365_mailer)

A TYPO3 extension for sending emails via Microsoft Exchange 365 using the MS Graph API instead of SMTP. Uses OAuth 2.0 client credentials flow for secure, token-based authentication.

## Features

- Send emails through Microsoft Graph API — no SMTP required
- OAuth 2.0 client credentials flow for server-to-server authentication
- Supports backend (environment variables / TYPO3 settings) and frontend configuration, the latter via a TYPO3 13/14 **site set** or the classic TypoScript static template
- Compatible with Powermail, TYPO3 Form Framework, and other form extensions
- Optional saving of sent emails to the sender's "Sent Items" folder
- Automatic credential blinding in TYPO3's configuration module
- Works with shared mailboxes and Application Access Policies
- **Send As / Send On Behalf** — optional `graphSenderUserId` decouples the
  Graph mailbox used for `/users/{id}/sendMail` from the visible `From`
  header, so a configured mailbox can send on behalf of another

## Requirements

- **TYPO3**: 12.4 LTS, 13.4 LTS, or 14.x
- **PHP**: 8.1 – 8.5
- **Dependencies**:
  - `microsoft/microsoft-graph` ^2
  - `oliverkroener/ok-typo3-helper` ^3

## Installation

Install via Composer (recommended):

```bash
composer require oliverkroener/ok-exchange365-mailer
```

### Local path repository

If using a local path (e.g., in a monorepo), add the repository to your root `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/ok_exchange365_mailer"
        }
    ]
}
```

Then install:

```bash
composer require oliverkroener/ok-exchange365-mailer:@dev
```

## Configuration

### 1. Register an Azure App

Register an application in Microsoft Entra ID (formerly Azure AD) with `Mail.Send` and `User.ReadBasic.All` application permissions. Grant admin consent. See the [full Azure setup guide](Documentation/Azure.rst).

### 2. Configure TYPO3

Set the mail transport to `Exchange365Transport` and provide your Azure credentials.

**Via environment variables (.env):**

| Variable | Description |
|----------|-------------|
| `TYPO3_CONF_VARS__MAIL__transport` | `OliverKroener\OkExchange365\Mail\Transport\Exchange365Transport` |
| `TYPO3_CONF_VARS__MAIL__transport_exchange365_tenantId` | Microsoft Entra ID Tenant ID |
| `TYPO3_CONF_VARS__MAIL__transport_exchange365_clientId` | Azure Application (Client) ID |
| `TYPO3_CONF_VARS__MAIL__transport_exchange365_clientSecret` | Azure Application Secret Value |
| `TYPO3_CONF_VARS__MAIL__transport_exchange365_fromEmail` | Sender email address (must exist in Exchange 365) |
| `TYPO3_CONF_VARS__MAIL__transport_exchange365_graphSenderUserId` | *(optional)* Graph mailbox/user ID used for `/users/{id}/sendMail`. When set, this mailbox sends the message; the visible `From` header still comes from the message or `fromEmail`. Use for *Send As* / *Send On Behalf*. |
| `TYPO3_CONF_VARS__MAIL__transport_exchange365_saveToSentItems` | `1` to save to Sent Items, `0` to skip (default: `0`) |

**Via the site set (TYPO3 13 / 14, for frontend forms):**

Add the set to your site configuration, then edit the values under
*Site Management → Sites → Settings*:

```yaml
# config/sites/<identifier>/config.yaml
dependencies:
  - oliverkroener/ok-exchange365-mailer
```

**Via TypoScript (TYPO3 12, for frontend forms):**

Include the static template *[kroener.DIGITAL] Exchange 365 Mailer*, then:

```typoscript
plugin.tx_okexchange365mailer.settings.exchange365 {
    tenantId = your-tenant-id
    clientId = your-client-id
    clientSecret = your-client-secret
    fromEmail = service@your-domain.com
    # Optional: route via a different mailbox using Send As / Send On Behalf
    # graphSenderUserId = service@your-domain.com
    saveToSentItems = 1
}
```

The environment variables are the baseline for every context (frontend, backend, CLI,
scheduler). In the frontend, non-empty site-set or TypoScript values override them **per
setting** — leave a value empty to fall back. Use the site set *or* the static template
on TYPO3 13/14, never both. Full details: [Site Set Configuration](Documentation/Configuration/SiteSets.rst).

### Send As / Send On Behalf

The optional `graphSenderUserId` (or `transport_exchange365_graphSenderUserId`)
decouples the **Graph mailbox** used for the API call from the **visible
`From` address** of the message:

- The Graph endpoint `/users/{id}/sendMail` is called against
  `graphSenderUserId`.
- The message `From` header still resolves through
  `$graphMessage['from']` → `fromEmail` → `defaultMailFromAddress`, so
  recipients see the configured sender, not the Graph mailbox.

The configured Graph mailbox must hold *Send As* or *Send On Behalf*
permission on the visible sender mailbox in Exchange. See the
[Microsoft Graph documentation on sending mail from another user](https://learn.microsoft.com/en-us/graph/outlook-send-mail-from-other-user).

When `graphSenderUserId` is unset (or empty), the same value resolution chain
is used to pick the Graph mailbox — i.e. the visible sender also sends the
message, which is the standard single-mailbox setup.

### Sender Display Name

The Graph API uses the **Display name** configured on the mailbox in Exchange Online. TYPO3's `defaultMailFromName` has no effect. Configure the display name in the Microsoft 365 Admin Center or Exchange Admin Center.

## Architecture

| Component | Description |
|-----------|-------------|
| `Exchange365Transport` | Custom Symfony mailer transport; handles OAuth2 auth and sends via Graph API |
| `ModifyBlindedConfigurationOptionsEventListener` | PSR-14 event listener; blinds credentials in TYPO3 configuration module |
| `MSGraphMailApiService` (from ok-typo3-helper) | Converts Symfony email messages to Microsoft Graph format |

```
Classes/
├── Mail/Transport/
│   └── Exchange365Transport.php
└── Lowlevel/EventListener/
    └── ModifyBlindedConfigurationOptionsEventListener.php
Configuration/
├── Services.yaml
├── Sets/Exchange365Mailer/          # TYPO3 13/14 site set
│   ├── config.yaml
│   ├── settings.definitions.yaml
│   └── setup.typoscript
├── TCA/Overrides/sys_template.php   # static template (TYPO3 12)
└── TypoScript/
    ├── constants.typoscript
    └── setup.typoscript
```

## Changelog

- **Inline images (`cid:`) fix** — broken inline images in received mails were
  caused by Microsoft Graph not carrying over the `Content-ID` of inline
  attachments. Fixed in the related dependency
  `oliverkroener/ok-typo3-helper` **3.1.2** (`MSGraphMailApiService` now sets
  the attachment's `Content-ID`). No change needed here — ensure
  `ok-typo3-helper` is `>= 3.1.2`; the `^3` constraint already allows it.

## License

GPL-2.0-or-later

## Author — Oliver Kroener

### Automated. Scaled. Done.

Web3 · Cloud · Automation

Technology is only valuable when it solves a real problem. For over 30 years I've been translating between business and tech — so your investment in digitalisation doesn't stall at proof-of-concept but delivers measurable results.

- Website: [oliver-kroener.de](https://www.oliver-kroener.de)
- Web3: [web3.oliver-kroener.de](https://web3.oliver-kroener.de/)
- Email: [ok@oliver-kroener.de](mailto:ok@oliver-kroener.de)
- Web3 Email: [oliverkroener@ethermail.io](mailto:oliverkroener@ethermail.io)

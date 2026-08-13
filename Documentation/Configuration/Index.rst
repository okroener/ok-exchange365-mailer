:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This section covers all aspects of configuring the Microsoft Exchange 365 Mailer extension for TYPO3.

After completing the :ref:`Azure Configuration <azure>`, you need to configure the TYPO3 extension with the values obtained from Microsoft Entra ID.

..  toctree::
    :titlesonly:

    Essential
    SiteSets
    Frontend

Quick Configuration Overview
============================

The extension requires the following key configuration variables:

1. **Mail Transport**: Set to use Exchange365Transport
2. **Tenant ID**: Your Microsoft Entra ID tenant identifier
3. **Client ID**: Your Azure application identifier
4. **Client Secret**: Your Azure application secret
5. **From Email**: The sender email address
6. **Graph Sender User ID** *(optional)*: Mailbox used for the Microsoft Graph
   ``/users/{id}/sendMail`` call. Decouples the Graph mailbox from the
   visible ``From`` header for *Send As* / *Send On Behalf* scenarios.
7. **Save to Sent Items**: Whether to save emails to sent folder

For detailed step-by-step instructions, see:

- :ref:`Essential Configuration <essential>` - For server-side email sending (recommended for production)
- :ref:`Site Set Configuration <sitesets>` - For frontend email sending on TYPO3 v13 and v14
- :ref:`Frontend Configuration <frontend>` - The TypoScript constants, required on TYPO3 v12

Configuration Methods
=====================

You can configure this extension using:

**Essential Configuration** (Recommended)
    - Environment variables (`.env` file)
    - TYPO3 :file:`config/system/settings.php`
    - TYPO3 Admin Panel (if available)

**Site Set** (TYPO3 v13 and v14, for Forms)
    - Activated per site, edited in :guilabel:`Site Management > Sites`
    - The modern replacement for the static TypoScript template

**Frontend Configuration** (TYPO3 v12, for Forms)
    - TypoScript configuration via the static template
    - Required for Powermail, Form Framework, and other frontend forms

Choose the method that best fits your deployment workflow and security requirements.

Configuration Precedence
========================

The two sources are merged **per setting**, not all-or-nothing:

1. The global mail settings (``$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_exchange365_*']``)
   are the baseline and apply in every context — frontend, backend, command line and
   scheduler.
2. In the **frontend**, any non-empty value from the site set or from TypoScript
   overrides the corresponding mail setting. Values left empty fall through to the
   baseline.

This lets you keep the credentials in environment variables while still adjusting, for
example, ``fromEmail`` per site in the backend.

..  attention::
    **Security Recommendation**: Use backend configuration with environment variables for production environments to avoid exposing sensitive Azure credentials in TypoScript or in :file:`config/sites/<identifier>/settings.yaml`.
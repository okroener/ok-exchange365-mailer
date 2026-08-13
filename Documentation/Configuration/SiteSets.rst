:navigation-title: Site Sets

..  _sitesets:

==========================
Site Set Configuration
==========================

..  versionadded:: 4.3.0
    The extension ships a **site set** for TYPO3 v13 and v14. On those versions it
    replaces the static TypoScript template as the recommended way to configure the
    frontend. TYPO3 v12 is unaffected and keeps using the
    :ref:`static template <frontend>`.

Site sets bundle the extension's TypoScript together with typed, labelled settings.
Integrators activate the set per site and edit the values in the TYPO3 backend
instead of hand-writing TypoScript constants.

The set provided by this extension is:

..  code-block:: text

    oliverkroener/ok-exchange365-mailer

Activating the set
==================

Add the set to the ``dependencies`` of the site configuration in
:file:`config/sites/<identifier>/config.yaml`:

..  code-block:: yaml
    :caption: config/sites/main/config.yaml

    base: 'https://example.org/'
    rootPageId: 1
    dependencies:
      - oliverkroener/ok-exchange365-mailer

Alternatively, select **[kroener.DIGITAL] Exchange 365 Mailer** in the backend under
:guilabel:`Site Management > Sites`, in the :guilabel:`Sets` tab of the site record.

..  attention::
    Use the site set **or** the static template :guilabel:`[kroener.DIGITAL] Exchange
    365 Mailer` on a :guilabel:`sys_template` record — never both. Site settings are
    added to the TypoScript constants *before* template records, so a static template
    that is still included would override the values of the set with its own empty
    defaults.

Editing the settings
====================

Once the set is active, the values are editable under :guilabel:`Site Management >
Sites`, in the :guilabel:`Settings` of the site record. They are grouped into
**Credentials** and **Email Settings**:

..  confval:: Tenant ID

    :type: string
    :Default: (empty)

    OAuth2 tenant ID of the Microsoft Entra ID directory.

..  confval:: Client ID

    :type: string
    :Default: (empty)

    OAuth2 client ID (application ID) of the registered Entra ID app.

..  confval:: Client Secret

    :type: string
    :Default: (empty)

    OAuth2 client secret of the registered Entra ID app. See the security warning
    below before entering a value here.

..  confval:: From Email

    :type: string
    :Default: (empty)

    Email address the messages are sent from.

..  confval:: Graph Sender User ID

    :type: string
    :Default: (empty)

    Optional Microsoft Graph mailbox used for the ``/users/{id}/sendMail`` call,
    for *Send As* / *Send On Behalf* scenarios. The visible ``From`` header is
    unaffected.

..  confval:: Save sent emails

    :type: boolean
    :Default: true

    Store a copy of every sent message in the mailbox "Sent Items" folder.

The full setting keys are identical to the TypoScript constants documented in
:ref:`frontend`, for example
``plugin.tx_okexchange365mailer.settings.exchange365.tenantId``. Values entered in
the backend are written to :file:`config/sites/<identifier>/settings.yaml`:

..  code-block:: yaml
    :caption: config/sites/main/settings.yaml

    plugin.tx_okexchange365mailer.settings.exchange365.tenantId: 00000000-0000-0000-0000-000000000000
    plugin.tx_okexchange365mailer.settings.exchange365.fromEmail: service@example.org
    plugin.tx_okexchange365mailer.settings.exchange365.saveToSentItems: true

Every value left empty falls back to the corresponding
:ref:`global mail setting <essential>`
(``$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_exchange365_*']``), evaluated per
key rather than all-or-nothing.

..  danger::
    **Do not put the client secret into site settings on production.**

    :file:`config/sites/<identifier>/settings.yaml` is a plaintext file that is
    usually committed to version control. Configure the credentials through
    environment variables as described in :ref:`essential` and leave
    ``tenantId``, ``clientId`` and ``clientSecret`` empty in the site set — the
    per-key fallback means the remaining settings (``fromEmail``,
    ``graphSenderUserId``, ``saveToSentItems``) still work from the backend.

Setting the transport
=====================

The site set only supplies the credentials. The transport itself must still be
activated, preferably via the environment variable described in :ref:`essential`:

..  code-block:: bash

    TYPO3_CONF_VARS__MAIL__transport="OliverKroener\\OkExchange365\\Mail\\Transport\\Exchange365Transport"

or, for the frontend only, via TypoScript:

..  code-block:: typoscript

    config.mail.transport = OliverKroener\OkExchange365\Mail\Transport\Exchange365Transport

Scope
=====

Site settings reach the mail transport as TypoScript constants, which exist in the
**frontend** only. Mail sent from the backend, the command line or the scheduler has
no site context and always uses the global
``$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_exchange365_*']`` settings. Configure
those as well if you send mail outside of frontend requests.

..  seealso::
    - :ref:`Essential Configuration <essential>` — environment variables and
      :file:`settings.php`, recommended for production
    - :ref:`Frontend Configuration <frontend>` — the TypoScript constants, still
      required on TYPO3 v12
    - `Site sets (TYPO3 Explained) <https://docs.typo3.org/permalink/t3coreapi:site-sets>`_

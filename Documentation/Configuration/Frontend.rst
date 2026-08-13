:navigation-title: Frontend

..  _frontend:

======================
Frontend Configuration
======================

The Exchange 365 Mailer extension supports frontend email sending through popular TYPO3 extensions like **Powermail**, **Form Framework**, and other form extensions. This requires additional TypoScript configuration to work properly.

..  attention::
    Frontend configuration is **required** for any page that uses forms or email functionality. Without proper TypoScript setup, frontend forms will not be able to send emails through Exchange 365.

..  note::
    **On TYPO3 v13 and v14, use the** :ref:`site set <sitesets>` **instead.** It provides
    the same settings under the same names, but activated per site and editable in the
    backend. This page describes the static TypoScript template, which remains the only
    option on TYPO3 v12. Do not use both at the same time — see :ref:`sitesets` for
    details.

Frontend Configuration Overview
===============================

Frontend email sending requires the same core parameters as the backend configuration, but they must be configured via **TypoScript** instead of environment variables or :file:`config/system/settings.php`.

..  note::
    Every parameter left empty falls back to the corresponding
    ``$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_exchange365_*']`` setting. The two
    sources are merged per parameter, so you can configure the credentials through
    environment variables and override only individual values here.

..  figure:: /_Images/image-frontend.png
    :alt: TYPO3 TypoScript configuration showing Exchange365 frontend parameters
    :class: with-shadow
    :scale: 100

Required TypoScript Parameters
==============================

The following 5 parameters must be configured in your TypoScript setup for frontend email functionality:

..  rst-class:: bignums-xxl

1.  **Transport Class Configuration**

    Set the mail transport to use the Exchange365Transport class for frontend operations.

    ..  code-block:: typoscript

        config.mail.transport = OliverKroener\OkExchange365\Mail\Transport\Exchange365Transport

    ..  note::
        This tells TYPO3 to use the Exchange 365 transport instead of the default SMTP transport for frontend emails.

2.  **Tenant ID**

    Configure your Microsoft Entra ID tenant identifier.

    ..  code-block:: typoscript

        plugin.tx_okexchange365mailer.settings.exchange365.tenantId = your-tenant-id-here

    ..  attention::
        Replace `your-tenant-id-here` with the actual Tenant ID from your :ref:`Azure Configuration <azure>` (step 4).

3.  **Client ID**

    Set your Azure application's client identifier.

    ..  code-block:: typoscript

        plugin.tx_okexchange365mailer.settings.exchange365.clientId = your-client-id-here

    ..  attention::
        Replace `your-client-id-here` with the actual Client ID from your :ref:`Azure Configuration <azure>` (step 4).

4.  **Client Secret**

    Configure the Azure application's client secret.

    ..  code-block:: typoscript

        plugin.tx_okexchange365mailer.settings.exchange365.clientSecret = your-client-secret-here

    ..  warning::
        - Replace `your-client-secret-here` with the actual Secret **Value** from your :ref:`Azure Configuration <azure>` (step 7)
        - **Security Risk**: TypoScript is visible in the frontend source. Consider using :ref:`essential` with environment variables for production
        - Never expose this value in public repositories or frontend debugging

5.  **From Email Address**

    Set the sender email address for frontend-generated emails.

    ..  code-block:: typoscript

        plugin.tx_okexchange365mailer.settings.exchange365.fromEmail = service@your-domain.com

    ..  note::
        - Replace `service@your-domain.com` with a valid email address from your Exchange 365 environment
        - This email must exist as a **SharedMailbox** or **User Mailbox** in your organization
        - If not specified, falls back to `config.mail.defaultMailFromAddress`

6.  **Graph Sender User ID (Optional)**

    Optionally set the Microsoft Graph mailbox/user ID used for the API
    call ``/users/{id}/sendMail``. This is the **mailbox the API call is
    made through**, which can differ from the visible message ``From``
    address — useful for *Send As* or *Send On Behalf* scenarios.

    ..  code-block:: typoscript

        plugin.tx_okexchange365mailer.settings.exchange365.graphSenderUserId = account1@your-domain.com

    ..  note::
        - **Optional.** When unset, falls back to the message ``From`` address, then ``fromEmail``, then ``config.mail.defaultMailFromAddress``.
        - The Azure application must have ``Mail.Send`` permission on the configured mailbox.
        - Requires *Send As* or *Send On Behalf* permission in Exchange when the Graph sender differs from the visible ``From``. See `Send mail from another user (Microsoft Graph) <https://learn.microsoft.com/en-us/graph/outlook-send-mail-from-other-user>`_.

7.  **Save to Sent Items (Optional)**

    Determine whether frontend emails should be saved to the sender's "Sent Items" folder.

    ..  code-block:: typoscript

        plugin.tx_okexchange365mailer.settings.exchange365.saveToSentItems = 1

    ..  note::
        - Set to `1` to save emails to Sent Items folder
        - Set to `0` to skip saving emails to Sent Items folder
        - Default value is `0` if not specified

Complete TypoScript Configuration Example
==========================================

Here's a complete TypoScript setup example for frontend email functionality:

..  code-block:: typoscript

    # Exchange 365 Frontend Mail Configuration
    config {
        mail {
            # Set transport to Exchange 365
            transport = OliverKroener\OkExchange365\Mail\Transport\Exchange365Transport
            
            # Optional: Default mail settings
            defaultMailFromAddress = service@your-domain.com
            defaultMailFromName = Your Organization Name
        }
    }

    # Exchange 365 specific configuration
    plugin.tx_okexchange365mailer {
        settings {
            exchange365 {
                # Azure/Microsoft Entra ID Configuration
                tenantId = your-tenant-id-here
                clientId = your-client-id-here
                clientSecret = your-client-secret-here
                
                # Email Configuration
                fromEmail = service@your-domain.com
                # Optional: distinct Graph sender mailbox (Send As / Send On Behalf).
                # Leave empty to use fromEmail as the Graph user ID (default).
                # graphSenderUserId = account1@your-domain.com
                saveToSentItems = 1
            }
        }
    }

Integration with Form Extensions
================================

Powermail Integration
---------------------

When using **Powermail**, the extension will automatically use the configured Exchange 365 transport for sending emails:

..  code-block:: typoscript

    plugin.tx_powermail {
        settings {
            setup {
                # Powermail will use the global mail configuration
                # No additional configuration needed
            }
        }
    }

TYPO3 Form Framework Integration
--------------------------------

For the **TYPO3 Form Framework**, ensure your form configuration references the global mail settings:

..  code-block:: yaml

    # In your form configuration (YAML)
    finishers:
      -
        identifier: EmailToReceiver
        options:
          # Uses global mail configuration automatically
          recipientAddress: 'recipient@example.com'
          recipientName: 'Recipient Name'

Configuration File Locations
=============================

Place your TypoScript configuration in one of these locations:

**Template Records**
    Add the configuration to your main TypoScript template record in the TYPO3 backend.

**Static Files**
    Create or modify files in your site package:
    
    - `Configuration/TypoScript/setup.typoscript`
    - `Configuration/TypoScript/constants.typoscript` (for constants)

**Page TSconfig** (Not recommended for mail settings)
    Only use for page-specific overrides, not for global mail configuration.


Security Considerations for Frontend
====================================

..  danger::
    **TypoScript Security Warning**
    
    TypoScript configuration is potentially visible in frontend source code and through various debugging tools:
    
    - **Client Secret Exposure**: Never use sensitive secrets in TypoScript on production sites
    - **Recommended Approach**: Use :ref:`essential` with environment variables
    - **Alternative**: Use TYPO3's encrypted configuration features for sensitive data
    - **Monitoring**: Regularly audit your TypoScript for exposed credentials

Best Practices
==============

1. **Environment-Specific Configuration**
   
   Use different Azure applications for different environments:
   
   ..  code-block:: typoscript
   
       [applicationContext == "Development"]
           plugin.tx_okexchange365mailer.settings.exchange365.clientId = dev-client-id
           plugin.tx_okexchange365mailer.settings.exchange365.tenantId = dev-tenant-id
       [END]
       
       [applicationContext == "Production"]
           plugin.tx_okexchange365mailer.settings.exchange365.clientId = prod-client-id
           plugin.tx_okexchange365mailer.settings.exchange365.tenantId = prod-tenant-id
       [END]

2. **Conditional Loading**
   
   Only load Exchange 365 configuration when needed:
   
   ..  code-block:: typoscript
   
       [siteIdentifier == "main-site"]
           <INCLUDE_TYPOSCRIPT: source="FILE:EXT:site_package/Configuration/TypoScript/exchange365.typoscript">
       [END]

3. **Fallback Configuration**
   
   Always provide fallback settings:
   
   ..  code-block:: typoscript
   
       config.mail {
           defaultMailFromAddress = fallback@your-domain.com
           defaultMailFromName = Fallback Sender
       }

Testing Frontend Configuration
==============================

To test your frontend configuration:

1. **Create a test form** using Powermail or Form Framework
2. **Submit the form** and verify email delivery
3. **Check TYPO3 logs** for any authentication or sending errors
4. **Verify in Exchange 365** that emails appear in the sender's mailbox (if saveToSentItems is enabled)

..  tip::
    Use TYPO3's mail spooling functionality during development to prevent sending actual emails while testing configuration.

Troubleshooting Frontend Issues
===============================

Common issues and solutions:

**Emails not sending**
    - Verify all 5 parameters are correctly configured in TypoScript
    - Check that the Azure application has proper permissions
    - Ensure the sender email exists in Exchange 365

**Authentication errors**
    - Verify Tenant ID and Client ID are correct
    - Check that the Client Secret is valid and not expired
    - Confirm Azure admin consent has been granted

**Permission errors**
    - Ensure the Azure application has `Mail.Send` permission
    - Verify the sender email address exists in your Exchange 365 environment
    - Check that the application can send on behalf of the specified user

..  seealso::
    For backend configuration details, see :ref:`Essential Configuration <essential>`.
    For Azure setup instructions, see :ref:`Azure Configuration <azure>`.
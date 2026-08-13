..  include:: /Includes.rst.txt

..  _start:

=============================
Microsoft Exchange 365 Mailer
=============================

:Extension key:
   ok_exchange365_mailer

:Package name:
   oliverkroener/ok-exchange365-mailer

:Version:
   |release|

:Language:
   en

:Author:
   `Oliver Kroener <https://www.oliver-kroener.de>`__ <ok@oliver-kroener.de>

:License:
   This document is published under the
   `Open Publication License <https://www.opencontent.org/openpub/>`__.

:Rendered:
   |today|

----

A TYPO3 extension for sending emails via Microsoft Exchange 365 using the
MS Graph API instead of SMTP. Uses OAuth 2.0 client credentials flow for
secure, token-based authentication.

..  attention::
    Since **Q3 2025**, Microsoft has enforced stricter access policies in *some* Exchange 365 tenants. You may need to configure an **Application Access Policy** to restrict app permissions to specific mailboxes. See :ref:`Exchange Online Setup <exchange-setup>`.

    This can also be used to restrict sending to *only* specific sender addresses (e.g., a **shared mailbox**).

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: 📖 Introduction

        Learn what this extension does, its features, and system requirements.

        ..  card-footer:: :ref:`Learn more <introduction>`
            :button-style: btn btn-primary

    ..  card:: 📦 Installation

        Install the extension via Composer and activate it in your TYPO3 project.

        ..  card-footer:: :ref:`Get started <installation>`
            :button-style: btn btn-primary

    ..  card:: 🔑 Microsoft Entra ID Setup

        Register an app in Microsoft Entra ID (formerly Azure AD) and configure
        API permissions for Graph API mail sending.

        ..  card-footer:: :ref:`Configure Azure <azure>`
            :button-style: btn btn-primary

    ..  card:: 🛡️ Exchange Online Setup

        Configure Application Access Policies to restrict app permissions to
        specific mailboxes using PowerShell.

        ..  card-footer:: :ref:`View guide <exchange-setup>`
            :button-style: btn btn-primary

    ..  card:: ⚙️ Configuration

        Set up the extension via environment variables, TYPO3 settings, or
        TypoScript for frontend form integration.

        ..  card-footer:: :ref:`Configure <configuration>`
            :button-style: btn btn-primary

    ..  card:: ❓ FAQ

        Answers to frequently asked questions about installation, configuration,
        and usage.

        ..  card-footer:: :ref:`Read FAQ <faq>`
            :button-style: btn btn-primary

    ..  card:: ✉️ Contact

        Get in touch with the author for support, questions, or contributions.

        ..  card-footer:: :ref:`Get in touch <contact>`
            :button-style: btn btn-primary

..  toctree::
    :maxdepth: 2
    :titlesonly:
    :hidden:

    Introduction/Index
    Installation
    Azure
    ExchangeSetup/Index
    Configuration/Index
    Faq
    GetHelp
    Contact/Index

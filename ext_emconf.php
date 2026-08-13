<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Exchange 365 Mail Extension for OAuth2',
    'description' => 'A TYPO3 extension for sending emails using Exchange 365 and Microsoft Graph API',
    'tags' => 'exchange365, mail, smtp, oauth2, microsoft graph',
    'category' => 'plugin',
    'author' => 'Oliver Kroener',
    'author_email' => 'ok@oliver-kroener.de',
    'author_company' => 'https://www.oliver-kroener.de',
    'state' => 'stable',
    'version' => '4.3.0',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.5.99',
            'typo3' => '12.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

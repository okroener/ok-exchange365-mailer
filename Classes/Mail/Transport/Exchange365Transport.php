<?php

declare(strict_types=1);

namespace OliverKroener\OkExchange365\Mail\Transport;

use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use OliverKroener\Helpers\MSGraphApi\MSGraphMailApiService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use TYPO3\CMS\Core\Adapter\EventDispatcherAdapter;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Exchange365Transport extends AbstractTransport
{
    /**
     * @var array<string, mixed>
     */
    private array $mailSettings;
    private LoggerInterface $logger;

    /**
     * Constructor for Exchange365Transport
     *
     * @param array<string, mixed> $mailSettings Mail configuration settings
     * @param EventDispatcherInterface|null $dispatcher Event dispatcher instance (optional)
     * @param LoggerInterface|null $logger Logger instance (optional)
     */
    public function __construct(array $mailSettings, ?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null)
    {
        $eventDispatcherAdapter = GeneralUtility::makeInstance(EventDispatcherAdapter::class);

        parent::__construct($dispatcher ?? $eventDispatcherAdapter);

        // Initialize the logger using TYPO3's logging system
        $this->logger = $logger ?? GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->mailSettings = $mailSettings;
    }

    /**
     * Sends the email using Microsoft Graph API.
     *
     * @param SentMessage $message The email message to be sent.
     * @throws \RuntimeException If sending fails.
     */
    protected function doSend(SentMessage $message): void
    {
        $graphSenderUserId = '';

        try {
            // Get configuration from different sources
            $conf = $this->getConfiguration();

            // Validate required configuration
            $this->validateConfiguration($conf);

            // Setup authentication context
            $tokenRequestContext = new ClientCredentialContext(
                (string)$conf['tenantId'],
                (string)$conf['clientId'],
                (string)$conf['clientSecret']
            );

            $graphServiceClient = new GraphServiceClient($tokenRequestContext);

            // Convert to Microsoft Graph message format
            $graphMessage = MSGraphMailApiService::convertToGraphMessage($message);

            // Resolve the Microsoft Graph sender mailbox/user ID for the
            // /users/{id}/sendMail endpoint. This is intentionally separate
            // from the message From address so Send As / Send On Behalf
            // scenarios can target a different mailbox than the visible sender.
            // Empty strings from getMailSettingsConfiguration() must be
            // treated as "unset", so use !empty() instead of a bare ?? chain.
            $graphSenderUserId = (string)(!empty($conf['graphSenderUserId'])
                ? $conf['graphSenderUserId']
                : ($graphMessage['from']
                    ?? (!empty($conf['fromEmail']) ? $conf['fromEmail'] : null)
                    ?? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
                    ?? ''));

            if (empty($graphSenderUserId)) {
                throw new \RuntimeException('No Microsoft Graph sender user ID could be resolved. Configure graphSenderUserId, fromEmail, or TYPO3 MAIL.defaultMailFromAddress.');
            }

            // Prepare request body
            $requestBody = new SendMailPostRequestBody();
            $requestBody->setMessage($graphMessage['message']);
            // Ensure boolean conversion for saveToSentItems
            $requestBody->setSaveToSentItems((bool)($conf['saveToSentItems'] ?? false));

            // Send the email using Microsoft Graph API
            $graphServiceClient->users()->byUserId($graphSenderUserId)->sendMail()->post($requestBody)->wait();

            $this->logger->debug('Mail sent successfully with ' . self::class . ' via Graph sender ' . $graphSenderUserId);
        } catch (\Exception $e) {
            $errorMessage = 'Sending mail' . ($graphSenderUserId ? " via Graph sender {$graphSenderUserId}" : '') . ' failed: ' . $e->getMessage();
            $this->logger->alert($errorMessage, ['exception' => $e]);
            throw new \RuntimeException('Sending mail with Exchange365 mailer failed. Please check credentials setup. Error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get configuration by merging TypoScript on top of the mail settings.
     *
     * The mail settings are the baseline, so backend, CLI and scheduler contexts
     * always have a configuration. Frontend TypoScript - which is also how the
     * site set's settings arrive, since site settings are flattened into
     * TypoScript constants - overlays individual values on top of it.
     *
     * @return array<string, mixed>
     */
    private function getConfiguration(): array
    {
        $conf = $this->getMailSettingsConfiguration();

        foreach ($this->getTypoScriptConfiguration() ?? [] as $key => $value) {
            // An empty TypoScript value means "not configured here" and must not
            // shadow the mail settings. saveToSentItems is exempt: a site setting
            // of false is flattened into an empty constant and does mean false.
            if ($key === 'saveToSentItems' || ($value !== '' && $value !== null)) {
                $conf[$key] = $value;
            }
        }

        return $conf;
    }

    /**
     * Get configuration from the frontend TypoScript setup.
     *
     * Returns null outside the frontend, and also on TYPO3 12.4.0, where the
     * "frontend.typoscript" request attribute does not exist yet - the caller
     * then keeps the mail settings baseline.
     *
     * @return array<string, mixed>|null
     */
    private function getTypoScriptConfiguration(): ?array
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        // Check if frontend mode (applicationType 1 = frontend)
        if ($request?->getAttribute('applicationType') !== 1) {
            return null;
        }

        $frontendTypoScript = $request->getAttribute('frontend.typoscript');
        if ($frontendTypoScript === null) {
            return null;
        }

        $fullTypoScript = $frontendTypoScript->getSetupArray();

        return $fullTypoScript['plugin.']['tx_okexchange365mailer.']['settings.']['exchange365.'] ?? null;
    }

    /**
     * Get configuration from mail settings
     *
     * @return array<string, mixed>
     */
    private function getMailSettingsConfiguration(): array
    {
        return [
            'tenantId' => $this->mailSettings['transport_exchange365_tenantId'] ?? '',
            'clientId' => $this->mailSettings['transport_exchange365_clientId'] ?? '',
            'clientSecret' => $this->mailSettings['transport_exchange365_clientSecret'] ?? '',
            'fromEmail' => $this->mailSettings['transport_exchange365_fromEmail'] ?? '',
            'graphSenderUserId' => $this->mailSettings['transport_exchange365_graphSenderUserId'] ?? '',
            'saveToSentItems' => $this->mailSettings['transport_exchange365_saveToSentItems'] ?? '0',
        ];
    }

    /**
     * Validate required configuration values
     *
     * @param array<string, mixed> $conf
     * @throws \RuntimeException
     */
    private function validateConfiguration(array $conf): void
    {
        $requiredFields = ['tenantId', 'clientId', 'clientSecret'];

        foreach ($requiredFields as $field) {
            if (empty($conf[$field])) {
                throw new \RuntimeException("Exchange 365 configuration missing required field: {$field}");
            }
        }
    }

    /**
     * Returns the name of the transport.
     *
     * @return string The transport name.
     */
    public function __toString(): string
    {
        return 'exchange365api';
    }
}

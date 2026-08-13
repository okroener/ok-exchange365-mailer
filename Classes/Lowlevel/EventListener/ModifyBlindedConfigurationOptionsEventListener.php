<?php

declare(strict_types=1);

namespace OliverKroener\OkExchange365\Lowlevel\EventListener;

use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

final class ModifyBlindedConfigurationOptionsEventListener
{
    /**
     * @var list<string>
     */
    private const BLINDED_MAIL_SETTINGS = [
        'transport_exchange365_clientId',
        'transport_exchange365_tenantId',
        'transport_exchange365_clientSecret',
    ];

    public function __invoke(ModifyBlindedConfigurationOptionsEvent $event): void
    {
        $options = $event->getBlindedConfigurationOptions();

        if ($event->getProviderIdentifier() === 'confVars') {
            $options = $this->modifyBlindedConfigurationOptions($options);
        }

        $event->setBlindedConfigurationOptions($options);
    }

    /**
     * Blind exchange 365 credentials in ConfigurationOptions
     *
     * @param array<string, mixed> $blindedConfigurationOptions
     * @return array<string, mixed>
     */
    public function modifyBlindedConfigurationOptions(array $blindedConfigurationOptions): array
    {
        foreach (self::BLINDED_MAIL_SETTINGS as $key) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['MAIL'][$key])) {
                $value = (string)$GLOBALS['TYPO3_CONF_VARS']['MAIL'][$key];
                $blindedConfigurationOptions['TYPO3_CONF_VARS']['MAIL'][$key] =
                    mb_substr($value, 0, 2) .
                    '******' .
                    mb_substr($value, -2, 2);
            }
        }

        return $blindedConfigurationOptions;
    }
}

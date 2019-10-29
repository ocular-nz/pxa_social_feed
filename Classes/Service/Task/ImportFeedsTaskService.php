<?php
declare(strict_types=1);

namespace Pixelant\PxaSocialFeed\Service\Task;

use Pixelant\PxaSocialFeed\Domain\Model\Configuration;
use Pixelant\PxaSocialFeed\Domain\Model\Token;
use Pixelant\PxaSocialFeed\Domain\Repository\ConfigurationRepository;
use Pixelant\PxaSocialFeed\Exception\UnsupportedTokenType;
use Pixelant\PxaSocialFeed\Feed\FacebookFeedFactory;
use Pixelant\PxaSocialFeed\Feed\FeedFactoryInterface;
use Pixelant\PxaSocialFeed\Feed\InstagramFactory;
use Pixelant\PxaSocialFeed\Feed\TwitterFactory;
use Pixelant\PxaSocialFeed\Feed\YoutubeFactory;
use Pixelant\PxaSocialFeed\Service\Expire\FacebookAccessTokenExpireService;
use Pixelant\PxaSocialFeed\Service\Notification\NotificationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class ImportFeedsTaskService
 * @package Pixelant\PxaSocialFeed\Service\Task
 */
class ImportFeedsTaskService
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * feeds repository
     * @var ConfigurationRepository
     */
    protected $configurationRepository;

    /**
     * @var NotificationService
     */
    protected $notificationService = null;

    /**
     * TaskUtility constructor.
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->configurationRepository = $this->objectManager->get(ConfigurationRepository::class);
    }

    /**
     * Import logic
     *
     * @param array $configurationUids
     * @return bool
     */
    public function import(array $configurationUids, bool $runAllConfigurations): bool
    {
        /** @var Configuration[] $configurations */
        $configurations = $runAllConfigurations ? $this->configurationRepository->findAll() : $this->configurationRepository->findByUids($configurationUids);

        foreach ($configurations as $configuration) {
            // Reset
            $factory = null;
            switch (true) {
                case $configuration->getToken()->isFacebookType():
                    // Check if access is valid
                    $this->checkFacebookAccessToken($configuration->getToken());
                    $factory = GeneralUtility::makeInstance(FacebookFeedFactory::class);
                    break;
                case $configuration->getToken()->isInstagramType():
                    // Check if access is valid
                    $this->checkFacebookAccessToken($configuration->getToken());
                    $factory = GeneralUtility::makeInstance(InstagramFactory::class);
                    break;
                case $configuration->getToken()->isTwitterType():
                    $factory = GeneralUtility::makeInstance(TwitterFactory::class);
                    break;
                case $configuration->getToken()->isYoutubeType():
                    $factory = GeneralUtility::makeInstance(YoutubeFactory::class);
                    break;
                default:
                    // @codingStandardsIgnoreStart
                    throw new UnsupportedTokenType("Token type '{$configuration->getToken()->getType()}' is not supported", 1562837370194);
                    // @codingStandardsIgnoreEnd
            }

            if (isset($factory)) {
                $this->importFeed($factory, $configuration);
            }
        }

        return true;
    }

    /**
     * Update feed configuration
     *
     * @param FeedFactoryInterface $feedFactory
     * @param Configuration $configuration
     */
    protected function importFeed(FeedFactoryInterface $feedFactory, Configuration $configuration): void
    {
        $source = $feedFactory->getFeedSource($configuration);
        $updater = $feedFactory->getFeedUpdater();

        // Create/Update feed
        $updater->update($source);
        // Save changes
        $updater->persist();

        // Remove items from feed that are not valid anymore
        $updater->cleanUp($configuration);
        // Save changes
        $updater->persist();
    }

    /**
     * Check if facebook token expire, send notification if yes
     *
     * @param Token $token
     */
    protected function checkFacebookAccessToken(Token $token): void
    {
        $expireTokenService = GeneralUtility::makeInstance(FacebookAccessTokenExpireService::class, $token);

        if (!$expireTokenService->tokenRequireCheck() || !$this->notificationService->canSendEmail()) {
            return;
        }

        if ($expireTokenService->hasExpired()) {
            $this->notificationService->notify(
                LocalizationUtility::translate('email.access_token', 'PxaSocialFeed'),
                LocalizationUtility::translate('email.access_token_expired', 'PxaSocialFeed')
            );
        } elseif ($expireTokenService->willExpireSoon(5)) {
            $this->notificationService->notify(
                LocalizationUtility::translate('email.access_token', 'PxaSocialFeed'),
                LocalizationUtility::translate(
                    'email.access_token_soon_expired',
                    'PxaSocialFeed',
                    [$expireTokenService->expireWhen()]
                )
            );
        }
    }
}

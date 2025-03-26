<?php

namespace WapplerSystems\FeUserManager\Controller;

use Doctrine\DBAL\Exception;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsController]
class FeUserController extends ActionController
{

    protected ?ModuleData $moduleData = null;
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        protected readonly IconFactory           $iconFactory,
        protected readonly PageRenderer          $pageRenderer,
        protected readonly BackendUriBuilder     $backendUriBuilder,
        protected readonly PageRepository        $pageRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ModuleProvider        $moduleProvider,
        protected readonly BackendLayoutRenderer $backendLayoutRenderer,
        protected readonly BackendLayoutView     $backendLayoutView,
    )
    {
    }

    public function initializeAction(): void
    {
        $this->moduleData = $this->request->getAttribute('moduleData');
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:fe_user_manager/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
    }


    /**
     * @throws Exception
     */
    public function indexAction(): ResponseInterface
    {

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');
        $queryBuilder = $connection->createQueryBuilder();
        $restrictions = $queryBuilder->getRestrictions();
        $restrictions->removeByType(HiddenRestriction::class);
        $queryBuilder->setRestrictions($restrictions);

        $users = $queryBuilder
            ->select('uid', 'username', 'email', 'disable')
            ->from('fe_users')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->moduleTemplate->assign('users', $users);

        if (empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '') || empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? '')) {
            $this->addFlashMessage('Bitte konfigurieren Sie die E-Mail-Adresse und den Namen des Absenders in den Einstellungen.', '', ContextualFeedbackSeverity::ERROR);
        }

        return $this->moduleTemplate->renderResponse('FeUser/Index');
    }


    protected function initializeView(): void
    {
        $this->moduleTemplate->assignMultiple([
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
    }

    public function activateUserAction(int $user): ResponseInterface
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');
        $queryBuilder = $connection->createQueryBuilder();
        $restrictions = $queryBuilder->getRestrictions();
        $restrictions->removeByType(HiddenRestriction::class);
        $queryBuilder->setRestrictions($restrictions);

        $userRecord = $queryBuilder
            ->select('uid', 'username', 'email', 'first_name', 'last_name')
            ->from('fe_users')
            ->executeQuery()
            ->fetchAssociative();

        if ($userRecord) {


            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][1742999266] = 'EXT:fe_user_manager/Resources/Private/Templates/Email/';

            $email = new FluidEmail();
            $email
                ->setRequest($this->request)
                ->to($userRecord['username'])
                ->from(new Address($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'], $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']))
                ->subject('Ihr Konto wurde aktiviert')
                ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
                ->setTemplate('ActivationEmail')
                ->assign('user', $userRecord);
            GeneralUtility::makeInstance(MailerInterface::class)->send($email);

            $connection->update(
                'fe_users',
                ['disable' => 0],
                ['uid' => $user]
            );
        }

        $this->addFlashMessage('E-Mail an '.$userRecord['username'].' gesendet.', '', ContextualFeedbackSeverity::OK);


        $uri = $this->backendUriBuilder->buildUriFromRoute('fe_user_manager');
        return new RedirectResponse($uri);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

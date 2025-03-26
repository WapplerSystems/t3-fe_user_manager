<?php

namespace WapplerSystems\FeUserManager\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

class FeUserController extends ActionController
{
    public function indexAction(): ResponseInterface
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');
        $queryBuilder = $connection->createQueryBuilder();

        $users = $queryBuilder
            ->select('uid', 'username', 'email', 'disable')
            ->from('fe_users')
            ->executeQuery()
            ->fetchAllAssociative();

        $html = '<h1>FE User Liste</h1><table border="1" cellpadding="5"><tr><th>UID</th><th>Name</th><th>Email</th><th>Status</th><th>Aktion</th></tr>';

        foreach ($users as $user) {
            $uri = (new UriBuilder())->buildUriFromRoute('fe_user_manager/activate', ['user' => $user['uid']]);
            $button = $user['disable']
                ? '<a href="' . $uri . '">Aktivieren</a>'
                : 'Aktiv';

            $html .= "<tr>
                        <td>{$user['uid']}</td>
                        <td>{$user['username']}</td>
                        <td>{$user['email']}</td>
                        <td>" . ($user['disable'] ? 'Deaktiviert' : 'Aktiv') . "</td>
                        <td>{$button}</td>
                    </tr>";
        }

        $html .= '</table>';

        return new HtmlResponse($html);
    }

    public function activateUserAction(int $user): ResponseInterface
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('fe_users');

        $userRecord = $connection->select(
            ['uid', 'email'],
            'fe_users',
            ['uid' => $user]
        )->fetchAssociative();

        if ($userRecord) {
            $connection->update(
                'fe_users',
                ['disable' => 0],
                ['uid' => $user]
            );

            // E-Mail senden
            $mail = GeneralUtility::makeInstance(MailMessage::class);
            $mail->setSubject('Ihr Konto wurde aktiviert')
                ->setFrom([$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']])
                ->setTo([$userRecord['email']])
                ->setBody('Hallo, Ihr Konto wurde soeben aktiviert.', 'text/plain')
                ->send();
        }

        // Redirect zurück
        $uri = (new UriBuilder())->buildUriFromRoute('fe_user_manager');
        return new \TYPO3\CMS\Core\Http\RedirectResponse($uri);
    }
}

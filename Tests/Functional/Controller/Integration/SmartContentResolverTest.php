<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HeadlessBundle\Tests\Functional\Controller\Integration;

use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\HeadlessBundle\Tests\Functional\BaseTestCase;
use Sulu\Bundle\HeadlessBundle\Tests\Traits\CreateAccountTrait;
use Sulu\Bundle\HeadlessBundle\Tests\Traits\CreateContactTrait;
use Sulu\Bundle\HeadlessBundle\Tests\Traits\CreateMediaTrait;
use Sulu\Bundle\HeadlessBundle\Tests\Traits\CreatePageTrait;
use Sulu\Bundle\HeadlessBundle\Tests\Traits\CreateSnippetTrait;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadCollectionTypes;
use Sulu\Bundle\MediaBundle\DataFixtures\ORM\LoadMediaTypes;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class SmartContentResolverTest extends BaseTestCase
{
    use CreateAccountTrait;
    use CreateContactTrait;
    use CreateMediaTrait;
    use CreatePageTrait;
    use CreateSnippetTrait;

    private KernelBrowser $websiteClient;

    private static CollectionInterface $collection;
    private static PageDocument $contentPage1;
    private static PageDocument $contentPage2;
    private static SnippetDocument $snippet1;
    private static SnippetDocument $snippet2;
    private static MediaInterface $media1;
    private static MediaInterface $media2;
    private static ContactInterface $contact1;
    private static ContactInterface $contact2;
    private static AccountInterface $account1;
    private static AccountInterface $account2;

    public static function setUpBeforeClass(): void
    {
        self::initPhpcr();

        // Purge contacts and accounts to ensure test isolation
        self::purgeContactsAndAccounts();

        // Load media fixtures
        $collectionTypeFixture = new LoadCollectionTypes();
        $collectionTypeFixture->load(self::getEntityManager());
        $mediaTypeFixture = new LoadMediaTypes();
        $mediaTypeFixture->load(self::getEntityManager());

        // Create content pages for pages provider
        self::$contentPage1 = self::createPage([
            'title' => 'Content Page One',
            'url' => '/content-page-one',
            'template' => 'default',
        ]);

        self::$contentPage2 = self::createPage([
            'title' => 'Content Page Two',
            'url' => '/content-page-two',
            'template' => 'default',
        ]);

        // Create snippets for snippets provider
        self::$snippet1 = self::createSnippet([
            'title' => 'Smart Snippet One',
            'template' => 'default',
        ]);

        self::$snippet2 = self::createSnippet([
            'title' => 'Smart Snippet Two',
            'template' => 'default',
        ]);

        // Create media for media provider
        self::$collection = self::createCollection('Smart Content Collection', 'de');
        self::$media1 = self::createMedia('Smart Media One', self::$collection, 'de');
        self::$media2 = self::createMedia('Smart Media Two', self::$collection, 'de');
        self::getEntityManager()->flush();

        // Create contacts for contacts provider
        self::$contact1 = self::createContact(['firstName' => 'Smart', 'lastName' => 'Contact One']);
        self::$contact2 = self::createContact(['firstName' => 'Smart', 'lastName' => 'Contact Two']);
        self::getEntityManager()->flush();

        // Create accounts for accounts provider
        self::$account1 = self::createAccount(['name' => 'Smart Account One']);
        self::$account2 = self::createAccount(['name' => 'Smart Account Two']);
        self::getEntityManager()->flush();

        // Pages provider tests
        self::createPage([
            'title' => 'Smart Content Pages',
            'url' => '/smart-content-pages',
            'template' => 'smart-content-providers',
        ]);

        self::createPage([
            'title' => 'Smart Content Pages Empty',
            'url' => '/smart-content-pages-empty',
            'template' => 'smart-content-providers',
        ]);

        // Snippets provider test
        self::createPage([
            'title' => 'Smart Content Snippets',
            'url' => '/smart-content-snippets',
            'template' => 'smart-content-providers',
        ]);

        // Media provider test
        self::createPage([
            'title' => 'Smart Content Media',
            'url' => '/smart-content-media',
            'template' => 'smart-content-providers',
        ]);

        // Contacts provider test
        self::createPage([
            'title' => 'Smart Content Contacts',
            'url' => '/smart-content-contacts',
            'template' => 'smart-content-providers',
        ]);

        // Accounts provider test
        self::createPage([
            'title' => 'Smart Content Accounts',
            'url' => '/smart-content-accounts',
            'template' => 'smart-content-providers',
        ]);

        static::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        $this->websiteClient = $this->createWebsiteClient();
    }

    /**
     * @dataProvider pagesProviderProvider
     */
    public function testPagesProvider(string $url, string $fixture): void
    {
        $this->websiteClient->request('GET', $url . '.json');

        $response = $this->websiteClient->getResponse();
        $this->assertResponseContent(
            $fixture,
            $response,
            Response::HTTP_OK
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function pagesProviderProvider(): iterable
    {
        yield 'pages content' => [
            '/smart-content-pages',
            'smart-content/pages__basic.json',
        ];

        yield 'pages empty (only test page itself)' => [
            '/smart-content-pages-empty',
            'smart-content/pages__empty.json',
        ];
    }

    /**
     * @dataProvider snippetsProviderProvider
     */
    public function testSnippetsProvider(string $url, string $fixture): void
    {
        $this->websiteClient->request('GET', $url . '.json');

        $response = $this->websiteClient->getResponse();
        $this->assertResponseContent(
            $fixture,
            $response,
            Response::HTTP_OK
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function snippetsProviderProvider(): iterable
    {
        yield 'snippets content' => [
            '/smart-content-snippets',
            'smart-content/snippets__basic.json',
        ];
    }

    /**
     * @dataProvider mediaProviderProvider
     */
    public function testMediaProvider(string $url, string $fixture): void
    {
        $this->websiteClient->request('GET', $url . '.json');

        $response = $this->websiteClient->getResponse();
        $this->assertResponseContent(
            $fixture,
            $response,
            Response::HTTP_OK
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function mediaProviderProvider(): iterable
    {
        yield 'media content' => [
            '/smart-content-media',
            'smart-content/media__basic.json',
        ];
    }

    /**
     * @dataProvider contactsProviderProvider
     */
    public function testContactsProvider(string $url, string $fixture): void
    {
        $this->websiteClient->request('GET', $url . '.json');

        $response = $this->websiteClient->getResponse();
        $this->assertResponseContent(
            $fixture,
            $response,
            Response::HTTP_OK
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function contactsProviderProvider(): iterable
    {
        yield 'contacts content' => [
            '/smart-content-contacts',
            'smart-content/contacts__basic.json',
        ];
    }

    /**
     * @dataProvider accountsProviderProvider
     */
    public function testAccountsProvider(string $url, string $fixture): void
    {
        $this->websiteClient->request('GET', $url . '.json');

        $response = $this->websiteClient->getResponse();
        $this->assertResponseContent(
            $fixture,
            $response,
            Response::HTTP_OK
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function accountsProviderProvider(): iterable
    {
        yield 'accounts content' => [
            '/smart-content-accounts',
            'smart-content/accounts__basic.json',
        ];
    }

    private static function purgeContactsAndAccounts(): void
    {
        $em = self::getEntityManager();
        $connection = $em->getConnection();

        // Disable foreign key checks temporarily
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        // Purge contacts and accounts
        $connection->executeStatement('DELETE FROM co_account_contacts');
        $connection->executeStatement('DELETE FROM co_contacts');
        $connection->executeStatement('DELETE FROM co_accounts');

        // Re-enable foreign key checks
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}

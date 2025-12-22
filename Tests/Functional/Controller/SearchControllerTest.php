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

namespace Sulu\Bundle\HeadlessBundle\Tests\Functional\Controller;

use CmsIg\Seal\EngineInterface;
use Sulu\Bundle\HeadlessBundle\Tests\Functional\BaseTestCase;
use Sulu\Bundle\HeadlessBundle\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class SearchControllerTest extends BaseTestCase
{
    use CreatePageTrait;

    private KernelBrowser $websiteClient;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        self::bootKernel();

        /** @var EngineInterface $engine */
        $engine = self::getContainer()->get(EngineInterface::class);

        // Drop and recreate schema to ensure fresh indexes
        $task = $engine->dropSchema(['return_slow_promise_result' => true]);
        $task->wait();
        $task = $engine->createSchema(['return_slow_promise_result' => true]);
        $task->wait();

        self::createPage(
            [
                'title' => 'Sulu is awesome',
                'url' => '/awesome-sulu',
            ]
        )->getUuid();

        self::createPage(
            [
                'title' => 'SEAL is awesome',
                'url' => '/awesome-seal',
            ]
        );

        // Clear entity manager to ensure fresh state for routing
        self::getEntityManager()->clear();

        static::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        $this->websiteClient = $this->createWebsiteClient();
    }

    /**
     * @return \Generator<mixed[]>
     */
    public static function provideAttributes(): \Generator
    {
        yield [
            'SEAL',
            'website',
            'search__get_seal.json',
        ];

        yield [
            'awesome',
            'website',
            'search__get_awesome.json',
        ];
    }

    /**
     * @dataProvider provideAttributes
     */
    public function testGetAction(string $query, string $index, string $expectedPatternFile): void
    {
        $this->websiteClient->request('GET', '/api/search?q=' . $query . '&index=' . $index);

        $response = $this->websiteClient->getResponse();
        $this->assertInstanceOf(Response::class, $response);

        $this->assertResponseContent(
            $expectedPatternFile,
            $response,
            Response::HTTP_OK
        );
    }
}

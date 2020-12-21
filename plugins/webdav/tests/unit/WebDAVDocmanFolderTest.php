<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Webdav;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Sabre_DAV_Exception_Conflict;
use Sabre_DAV_Exception_FileNotFound;
use Sabre_DAV_Exception_Forbidden;
use Sabre_DAV_Exception_MethodNotAllowed;
use Sabre_DAV_Exception_RequestedRangeNotSatisfiable;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVDocmanFolder
 */
class WebDAVDocmanFolderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildListNoChildrens(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([]);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->assertSame([], $webDAVDocmanFolder->getChildList());
    }

    /**
     * Testing when the User can't access/read the child node
     */
    public function testGetChildListUserCanNotAccess(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $item = \Mockery::spy(\Docman_Item::class);
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(false);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->assertEquals([], $webDAVDocmanFolder->getChildList());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    public function testGetChildListDuplicateName(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns(Mockery::spy(Project::class));

        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('SameName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $children = $webDAVDocmanFolder->getChildList();

        $this->assertTrue(isset($children['SameName']));
        $this->assertSame(1, sizeof($children));
        $this->assertSame('duplicate', $children['SameName']);
    }

    /**
     * Testing when the folder contain some items
     */
    public function testGetChildListFilled(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns(Mockery::spy(Project::class));

        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('AnotherName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $children = $webDAVDocmanFolder->getChildList();

        $this->assertTrue(isset($children['SameName']));
        $this->assertTrue(isset($children['AnotherName']));
        $this->assertSame(2, sizeof($children));
    }

    public function testGetChildrenDoesntListLinksWiki(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns(Mockery::spy(Project::class));

        $item1 = new \Docman_Folder(['title' => 'leFolder', 'parent_id' => '115']);
        $item2 = new \Docman_Link(['title' => 'leLink', 'link_url' => 'https://example.com']);
        $docmanItemFactory = \Mockery::mock(\Docman_ItemFactory::class, ['getChildrenFromParent' => [$item1, $item2]]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $children = $webDAVDocmanFolder->getChildren();

        self::assertCount(1, $children);
        self::assertEquals('leFolder', array_pop($children)->getName());
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildrenNoChildrens(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVDocmanFolder->shouldReceive('getChildList')->andReturns([]);

        $this->assertSame([], $webDAVDocmanFolder->getChildren());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    public function testGetChildrenDuplicateName(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $webDAVDocmanFolder->shouldReceive('getChildList')->andReturns(['SomeName' => 'duplicate']);

        $this->assertSame([], $webDAVDocmanFolder->getChildren());
    }

    /**
     * Testing when the folder contain some items
     */
    public function testGetChildrenFilled(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns(Mockery::spy(Project::class));

        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('AnotherName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $children = $webDAVDocmanFolder->getChildren();

        $this->assertTrue(isset($children['SameName']));
        $this->assertTrue(isset($children['AnotherName']));
        $this->assertSame(2, sizeof($children));
    }

    /**
     * Testing when the folder have no childrens
     */
    public function testGetChildNotFound(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([]);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_FileNotFound::class);
        $webDAVDocmanFolder->getChild('whatever');
    }

    /**
     * Testing when the item is duplicated
     */
    public function testGetChildDuplicatedSameCase(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns(Mockery::spy(Project::class));

        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('SameName');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $utils->shouldReceive('retrieveName')->andReturns('SameName');
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_Conflict::class);
        $webDAVDocmanFolder->getChild('SameName');
    }

    /**
     * Testing when the item is duplicated
     */
    public function testGetChildDuplicatedDifferentCase(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns(Mockery::spy(Project::class));

        $item1 = \Mockery::spy(\Docman_Folder::class);
        $item1->shouldReceive('getTitle')->andReturns('SameName');
        $item2 = \Mockery::spy(\Docman_Folder::class);
        $item2->shouldReceive('getTitle')->andReturns('samename');
        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item1, $item2]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $utils->shouldReceive('retrieveName')->andReturns('SameName');
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_Conflict::class);
        $webDAVDocmanFolder->getChild('SameName');
    }

    public function testGetChildIsWiki(): void
    {
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);

        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([new \Docman_Wiki(['title' => 'leWiki', 'wiki_page' => 'HomePage'])]);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);

        $utils->shouldReceive("retrieveName")->andReturnArg(0);

        $webDAVDocmanFolder = new \WebDAVDocmanFolder(
            UserTestBuilder::aUser()->build(),
            ProjectTestBuilder::aProject()->build(),
            new \Docman_Folder([]),
            $utils
        );

        $this->expectException(\Sabre_DAV_Exception_BadRequest::class);
        $webDAVDocmanFolder->getChild('leWiki');
    }

    /**
     * Testing when the folder have childrens
     */
    public function testGetChildSuccess(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();
        $base_folder = new \Docman_Folder(['title' => 'leFolder', 'parent_id' => 121]);

        $item = \Mockery::spy(\Docman_Folder::class);
        $item->shouldReceive('getTitle')->andReturns('SomeName');

        $expected_folder = new \WebDAVDocmanFolder($user, $project, $item);

        $docmanItemFactory = \Mockery::spy(\Docman_ItemFactory::class);
        $docmanItemFactory->shouldReceive('getChildrenFromParent')->andReturns([$item]);

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $docmanPermissionManager = \Mockery::spy(\Docman_PermissionsManager::class);
        $docmanPermissionManager->shouldReceive('userCanAccess')->andReturns(true);
        $utils->shouldReceive('getDocmanPermissionsManager')->andReturns($docmanPermissionManager);
        $utils->shouldReceive('getDocmanItemFactory')->andReturns($docmanItemFactory);
        $utils->shouldReceive('retrieveName')->andReturns('SomeName');

        $webDAVDocmanFolder = new \WebDAVDocmanFolder($user, $project, $base_folder, $utils);
        self::assertEquals($expected_folder, $webDAVDocmanFolder->getChild('SomeName'));
    }

    public function testCreateDirectoryNoWriteEnabled(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);
        $webDAVDocmanFolder->createDirectory('name');
    }

    public function testCreateDirectorySuccess(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $project = \Mockery::spy(\Project::class);
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns($project);

        $item = \Mockery::spy(\Docman_Item::class);
        $webDAVDocmanFolder->shouldReceive('getItem')->andReturns($item);

        $webDAVDocmanFolder->createDirectory('name');
    }

    public function testDeleteDirectoryNoWriteEnabled(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);
        $webDAVDocmanFolder->delete();
    }

    public function testSetNameNoWriteEnabled(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_MethodNotAllowed::class);
        $webDAVDocmanFolder->setName('newName');
    }

    public function testSetNameSuccess(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $project = \Mockery::spy(\Project::class);
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns($project);

        $item = \Mockery::spy(\Docman_Item::class);
        $webDAVDocmanFolder->shouldReceive('getItem')->andReturns($item);

        $webDAVDocmanFolder->setName('newName');
    }

    public function testCreateFileNoWriteEnabled(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(false);
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $this->expectException(Sabre_DAV_Exception_Forbidden::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }

    public function testCreateFileBigFile(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->never();
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $project = \Mockery::spy(\Project::class);
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns($project);

        $item = \Mockery::spy(\Docman_Item::class);
        $webDAVDocmanFolder->shouldReceive('getItem')->andReturns($item);

        $webDAVDocmanFolder->shouldReceive('getMaxFileSize')->andReturns(23);

        $this->expectException(Sabre_DAV_Exception_RequestedRangeNotSatisfiable::class);
        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }

    public function testCreateFileSucceed(): void
    {
        $webDAVDocmanFolder = \Mockery::mock(\WebDAVDocmanFolder::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $utils = \Mockery::spy(\WebDAVUtils::class);
        $utils->shouldReceive('isWriteEnabled')->andReturns(true);
        $utils->shouldReceive('processDocmanRequest')->once();
        $webDAVDocmanFolder->shouldReceive('getUtils')->andReturns($utils);

        $project = \Mockery::spy(\Project::class);
        $webDAVDocmanFolder->shouldReceive('getProject')->andReturns($project);

        $item = \Mockery::spy(\Docman_Item::class);
        $webDAVDocmanFolder->shouldReceive('getItem')->andReturns($item);

        $webDAVDocmanFolder->shouldReceive('getMaxFileSize')->andReturns(2000);

        $data = fopen(dirname(__FILE__) . '/_fixtures/test.txt', 'r');
        $webDAVDocmanFolder->createFile('name', $data);
    }
}

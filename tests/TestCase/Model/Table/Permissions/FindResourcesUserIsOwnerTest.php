<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */

namespace App\Test\TestCase\Model\Table\Permissions;

use App\Model\Entity\Permission;
use App\Test\Lib\AppTestCase;
use App\Utility\UuidFactory;
use Cake\ORM\TableRegistry;

class FindResourcesUserIsOwnerTest extends AppTestCase
{
    public $fixtures = ['app.Alt0/permissions', 'app.Alt0/groups_users', 'app.Base/resources'];

    /**
     * Test subject
     *
     * @var \App\Model\Table\PermissionsTable
     */
    public $Permissions;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Permissions = TableRegistry::get('Permissions');
    }

    public function testFindResourcesUserIsOwner_OwnsNothing_Case0()
    {
        $userId = UuidFactory::uuid('user.id.irene');
        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEmpty($resources);
    }

    public function testFindResourcesUserIsOwner_SoleOwnerNotSharedResource_Case1()
    {
        $userId = UuidFactory::uuid('user.id.jean');
        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], UuidFactory::uuid('resource.id.mailvelope'));
    }

    public function testFindResourcesUserIsOwner_SoleOwnerSharedResourceWithUser_Case2()
    {
        $userId = UuidFactory::uuid('user.id.kathleen');
        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], UuidFactory::uuid('resource.id.mocha'));
    }

    public function testFindResourcesUserIsOwner_SharedResourceWithMe_Case3()
    {
        $userId = UuidFactory::uuid('user.id.lynne');
        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEmpty($resources);
    }

    public function testFindResourcesUserIsOwner_SoleOwnerSharedResourceWithGroup_Case4()
    {
        $userId = UuidFactory::uuid('user.id.marlyn');
        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], UuidFactory::uuid('resource.id.nodejs'));

        // Check groups users
        $resources = $this->Permissions->findSharedResourcesUserIsSoleOwner($userId, true)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], UuidFactory::uuid('resource.id.nodejs'));
    }

    public function testFindResourcesUserIsOwner_SoleOwnerSharedResourceWithSoleManagerEmptyGroup_Case5()
    {
        $userId = UuidFactory::uuid('user.id.nancy');
        $resourceOId = UuidFactory::uuid('resource.id.openpgpjs');
        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], UuidFactory::uuid('resource.id.openpgpjs'));

        // Check groups users
        $resources = $this->Permissions->findResourcesUserIsOwner($userId, true)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], $resourceOId);
    }

    public function testFindResourcesUserIsOwner_indirectlyOwnerSharedResourceWithSoleManagerEmptyGroup_Case7()
    {
        $userId = UuidFactory::uuid('user.id.nancy');
        $groupLId = UuidFactory::uuid('group.id.leadership_team');
        $resourceOId = UuidFactory::uuid('resource.id.openpgpjs');

        // CONTEXTUAL TEST CHANGES Remove the direct permission of nancy
        $this->Permissions->deleteAll(['aro_foreign_key IN' => $userId, 'aco_foreign_key' => $resourceOId]);
        $permission = $this->Permissions->find()->select()->where([
            'aro_foreign_key' => $groupLId,
            'aco_foreign_key' => $resourceOId
        ])->first();
        $permission->type = Permission::OWNER;
        $this->Permissions->save($permission);

        $resources = $this->Permissions->findResourcesUserIsOwner($userId)->extract('aco_foreign_key')->toArray();
        $this->assertEmpty($resources);

        // Check groups users
        $resources = $this->Permissions->findResourcesUserIsOwner($userId, true)->extract('aco_foreign_key')->toArray();
        $this->assertEquals(count($resources), 1);
        $this->assertEquals($resources[0], $resourceOId);
    }
}
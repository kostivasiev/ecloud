<?php

namespace Tests\unit\Models;

use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Tests\TestCase;

class OrchestratorBuildTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create();

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testRenderResourceExistsSucceeds()
    {
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        $renderedData = $this->orchestratorBuild->render((object) [
            'vpc_id' => '{vpc.0}',
            'name' => 'test router'
        ]);

        $this->assertEquals('vpc-test', $renderedData->get('vpc_id'));
        $this->assertEquals('test router', $renderedData->get('name'));
    }

    public function testRenderResourceDoesNotExistsFails()
    {
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        $this->expectExceptionMessage('Resource for placeholder {vpc.0} was found in build state, but associated resource vpc-test does not exist');

        $renderedData = $this->orchestratorBuild->render((object) [
            'vpc_id' => '{vpc.0}',
            'name' => 'test router'
        ]);

        $this->assertEquals('vpc-testing', $renderedData->get('vpc_id'));
        $this->assertEquals('test router', $renderedData->get('name'));
    }

    public function testRenderResourceNotFound()
    {
        $this->expectExceptionMessage('Failed to render placeholder {vpc.0}, resource was not found in the current build state.');

        $this->orchestratorBuild->render((object) [
            'vpc_id' => '{vpc.0}',
            'name' => 'test router'
        ]);
    }



}

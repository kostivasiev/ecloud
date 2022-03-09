<?php

namespace Tests\V1\Solutions\Templates;

use App\Exceptions\V1\IntapiServiceException;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use App\Models\V1\SolutionTemplate;
use App\Services\IntapiService;
use App\Services\Kingpin\V1\KingpinService;
use Tests\V1\TestCase;

class SolutionTemplateTest extends TestCase
{
    public Pod $pod;
    public Solution $solution;
    public $solutionTemplate;

    public function setUp(): void
    {
        parent::setUp();
        $this->pod = Pod::factory()->create();
        $this->solution = Solution::factory()->create([
            'ucs_reseller_datacentre_id' => $this->pod->getKey(),
        ]);
        $this->solutionTemplate = \Mockery::mock('alias:'.SolutionTemplate::class)->makePartial();
        $this->solutionTemplate->allows('withName')->andReturnSelf();
        $this->solutionTemplate->solution = $this->solution;
        $this->solutionTemplate->solution_id = $this->solution->getKey();
        $this->solutionTemplate->name = 'TestTemplate';
        $this->solutionTemplate->allows('convertToPublicTemplate')->andReturnTrue();
    }

    // /v1/solutions/{solutionId}/templates
    public function testGetCollectionSuccessful()
    {
        $this->asAdmin()
            ->get(
                sprintf(
                    '/v1/solutions/%d/templates/%s',
                    $this->solution->getKey(),
                    'TestTemplate'
                )
            )->assertStatus(200);
    }

    // /v1/solutions/{solutionId}/templates/{templateName}
    public function testGetResourceSuccessful()
    {
        app()->bind(KingpinService::class, function () {
            $mock = \Mockery::mock(KingpinService::class)->makePartial();
            $mock->allows('getSolutionTemplates')
                ->andReturnTrue();
            $mock->allows('getSolutionTemplate')
                ->andReturnTrue();
            return $mock;
        });
        $this->asAdmin()
            ->get(
                sprintf(
                    '/v1/solutions/%d/templates/%s',
                    $this->solution->getKey(),
                    'TestTemplate'
                )
            )->assertStatus(200);
    }

    public function testGetResourceSolutionNotFound()
    {
        $this->asAdmin()
            ->get('/v1/solutions/001-001/templates/TestTemplate')
            ->assertJsonFragment([
                'title' => 'Solution not found',
                'detail' => 'Solution ID #001-001 not found',
            ])
            ->assertStatus(404);
    }

    public function testDeleteSolutionTemplateSuccessful()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')->andReturnTrue();
            return $mock;
        });
        $this->delete(
            sprintf(
                '/v1/solutions/%d/templates/%s',
                $this->solution->getKey(),
                'TestTemplate'
            ),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(202);
    }

    // /v1/solutions/{solutionId}/templates/{templateName}/move
    public function testRenameSolutionTemplateSuccess()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')->andReturnTrue();
            return $mock;
        });
        $this->post(
            sprintf(
                '/v1/solutions/%d/templates/%s/move',
                $this->solution->getKey(),
                'TestTemplate'
            ),
            [
                'destination' => 'NewTemplateName',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertStatus(202);
    }

    public function testRenameSolutionTemplateFailed()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')
                ->andThrow(new IntapiServiceException('failed'));
            return $mock;
        });
        $this->post(
            sprintf(
                '/v1/solutions/%d/templates/%s/move',
                $this->solution->getKey(),
                'TestTemplate'
            ),
            [
                'destination' => 'NewTemplateName',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertJsonFragment([
            'detail' => 'Failed to schedule template rename',
        ])->assertStatus(500);
    }

    public function testDeleteSolutionTemplateUnsuccessful()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')
                ->andThrow(new IntapiServiceException('failed'));
            return $mock;
        });
        $this->delete(
            sprintf(
                '/v1/solutions/%d/templates/%s',
                $this->solution->getKey(),
                'TestTemplate'
            ),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertJsonFragment([
            'detail' => 'Failed to schedule template deletion',
        ])->assertStatus(500);
    }
}

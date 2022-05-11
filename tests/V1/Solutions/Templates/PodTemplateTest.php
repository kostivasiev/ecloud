<?php

namespace Tests\V1\Solutions\Templates;

use App\Exceptions\V1\IntapiServiceException;
use App\Models\V1\Pod;
use App\Models\V1\PodTemplate;
use App\Models\V1\Solution;
use App\Services\IntapiService;
use Tests\V1\TestCase;

class PodTemplateTest extends TestCase
{
    public Pod $pod;
    public Solution $solution;
    public $podTemplate;

    public function setUp(): void
    {
        parent::setUp();
        $this->pod = Pod::factory()->create();
        $this->solution = Solution::factory()->create([
            'ucs_reseller_datacentre_id' => $this->pod->getKey(),
        ]);
        $this->podTemplate = \Mockery::mock('alias:'.PodTemplate::class)->makePartial();
        $this->podTemplate->allows('withPod')->andReturn([]);
        $this->podTemplate->allows('withFriendlyName')->andReturnSelf();
        $this->podTemplate->allows('convertToPublicTemplate')->andReturnSelf();
        $this->podTemplate->name = $this->pod->getAttribute('name');
        $this->podTemplate->allows('getKey')->andReturn($this->pod->getKey());
    }

    public function testGetPodCollection()
    {
        $this->asAdmin()
            ->get(
                sprintf(
                    '/v1/pods/%s/templates',
                    $this->pod->getKey()
                )
            )->assertStatus(200);
    }

    public function testGetPodResource()
    {
        $this->asAdmin()
            ->get(
                sprintf(
                    '/v1/pods/%s/templates/%s',
                    $this->pod->getKey(),
                    'TestTemplate'
                )
            )->assertStatus(200);
    }

    // /v1/pods/{podId}/templates/{templateName}/move
    public function testPodRenameSuccess()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')->andReturnTrue();
            return $mock;
        });
        $this->podTemplate->allows('isUKFastBaseTemplate')->andReturnFalse();
        $this->post(
            sprintf(
                '/v1/pods/%s/templates/%s/move',
                $this->pod->getKey(),
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

    public function testPodRenameFailsForBaseTemplate()
    {
        $this->podTemplate->allows('isUKFastBaseTemplate')->andReturnTrue();
        $this->post(
            sprintf(
                '/v1/pods/%s/templates/%s/move',
                $this->pod->getKey(),
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
            'title' => 'Forbidden',
            'detail' => 'UKFast Base templates can not be edited',
        ])->assertStatus(403);
    }

    public function testPodRenameFail()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')
                ->andThrow(new IntapiServiceException('failed'));
            return $mock;
        });
        $this->podTemplate->allows('isUKFastBaseTemplate')->andReturnFalse();
        $this->post(
            sprintf(
                '/v1/pods/%s/templates/%s/move',
                $this->pod->getKey(),
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

    public function testPodDeleteSuccess()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')->andReturnTrue();
            return $mock;
        });
        $this->podTemplate->allows('isUKFastBaseTemplate')->andReturnFalse();
        $this->delete(
            sprintf(
                '/v1/pods/%s/templates/%s',
                $this->pod->getKey(),
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

    public function testPodDeleteFail()
    {
        app()->bind(IntapiService::class, function () {
            $mock = \Mockery::mock(IntapiService::class)->makePartial();
            $mock->allows('automationRequest')
                ->andThrow(new IntapiServiceException('failed'));
            return $mock;
        });
        $this->podTemplate->allows('isUKFastBaseTemplate')->andReturnFalse();
        $this->delete(
            sprintf(
                '/v1/pods/%s/templates/%s',
                $this->pod->getKey(),
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
            'detail' => 'Failed to schedule template deletion',
        ])->assertStatus(500);
    }
}
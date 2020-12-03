<?php

namespace Tests\V2\MrrCommitment;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{

    use DatabaseMigrations;

    public function testCreateItem()
    {
        $data = [
            'contact_id' => 1,
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d H:i:s', strtotime('now')),
            'term_end_date' => date('Y-m-d H:i:s', strtotime('2 days')),
        ];
        $this->post(
            '/v2/mrr-commitments',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeInDatabase(
            'mrr_commitments',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }
}

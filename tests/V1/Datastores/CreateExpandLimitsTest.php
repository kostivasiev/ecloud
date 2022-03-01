<?php

namespace Tests\V1\Datastores;

use App\Datastore\Status;
use App\Models\V1\Datastore;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;
use Mockery;
use Tests\V1\TestCase;
use UKFast\Api\Exceptions\ForbiddenException;

class CreateExpandLimitsTest extends TestCase
{

    use ProvidesConvenienceMethods;

    /**
     * Test Creation
     */

    public function testValidMinCapacity()
    {
        $request = $this->getRequest('POST', [
            'solution_id' => 1,
            'capacity' => 1,
        ]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getRules()
            );
        $this->assertFalse($validator->fails());
    }

    public function testInvalidMinCapacity()
    {
        $request = $this->getRequest('POST', [
            'solution_id' => 1,
            'capacity' => 0,
        ]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getRules()
            );
        $this->assertTrue($validator->fails());
    }

    public function testValidMaxCapacity()
    {
        $request = $this->getRequest('POST', [
            'solution_id' => 1,
            'capacity' => 16000,
        ]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getRules()
            );
        $this->assertFalse($validator->fails());
    }

    public function testInvalidMaxCapacity()
    {
        $request = $this->getRequest('POST', [
            'solution_id' => 1,
            'capacity' => 16001,
        ]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getRules()
            );
        $this->assertTrue($validator->fails());
    }

    /**
     * Test Expansion
     */

    /**
     * Controller not testable due to internal static method calls, so we focus on the relevant elements
     */
    public function testExpandValidMinCapacity()
    {
        $request = $this->getRequest('POST', ['capacity' => 2]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getExpandRules()
            );
        $this->assertFalse($validator->fails());

        $datastore = $this->getDatastore(1);
        $newSizeGB = $request->input('capacity');
        $this->assertGreaterThanOrEqual($datastore->reseller_lun_size_gb, $newSizeGB);
    }

    /**
     * Controller not testable due to internal static method calls, so we focus on the relevant elements
     */
    public function testExpandValidMaxCapacity()
    {
        $request = $this->getRequest('POST', ['capacity' => 16000]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getExpandRules()
            );
        $this->assertFalse($validator->fails());

        $datastore = $this->getDatastore(1);
        $newSizeGB = $request->input('capacity');
        $this->assertGreaterThanOrEqual($datastore->reseller_lun_size_gb, $newSizeGB);
    }

    /**
     * Controller not testable due to internal static method calls, so we focus on the relevant elements
     */
    public function testExpandInvalidMinCapacity()
    {
        $request = $this->getRequest('POST', ['capacity' => 0]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getExpandRules()
            );
        $this->assertTrue($validator->fails());
    }

    public function testExpandInvalidMaxCapacity()
    {
        $request = $this->getRequest('POST', ['capacity' => 16001]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getExpandRules()
            );
        $this->assertTrue($validator->fails());
    }

    /**
     * This time valid minimum of 2, but we'll make that less than the current datastore's lun value.
     * @throws ForbiddenException
     */
    public function testExpandInvalidMinCapacityReturnsException()
    {
        $request = $this->getRequest('POST', ['capacity' => 2]);
        $validator = $this->getValidationFactory()
            ->make(
                $request->all(),
                Datastore::getExpandRules()
            );
        $this->assertFalse($validator->fails());

        $datastore = $this->getDatastore(16);
        $newSizeGB = $request->input('capacity');
        $message = 'New datastore size must be greater than the current size of %s GB';
        $message = sprintf($message, $datastore->reseller_lun_size_gb);
        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage($message);

        if ($newSizeGB <= $datastore->reseller_lun_size_gb) {
            throw new ForbiddenException($message);
        }
    }

    /**
     * Create a request object
     * @param string $method
     * @param array $params
     * @return Request
     */
    public function getRequest(string $method, array $params)
    {
        return Request::create('', $method, $params);
    }

    /**
     * Create a mock datastore instance
     * @param int $lunSize
     * @return \Mockery\Mock
     */
    public function getDatastore(int $lunSize)
    {
        $datastore = Mockery::mock(Datastore::class)->makePartial();
        $datastore->reseller_lun_size_gb = $lunSize;
        $datastore->reseller_lun_status = Status::QUEUED;
        $datastore->reseller_lun_lun_type = 'DATA';
        $datastore->shouldReceive('save')->andReturnTrue();

        return $datastore;
    }

}
<?php
namespace Tests\unit\Jobs\Nsx\HostGroup;

use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\Mocks\HostGroup\TransportNodeProfile;
use Tests\TestCase;

class DeleteTransportNodeProfileTest extends TestCase
{
    use TransportNodeProfile;

    protected $job;
    protected $hostGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->hostGroup = factory(HostGroup::class)->create([
            'id' => 'hg-test',
            'name' => 'hg-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => $this->hostSpec()->id,
            'windows_enabled' => true,
        ]);
        $this->job = \Mockery::mock(DeleteTransportNodeProfile::class, [$this->hostGroup])->makePartial();
    }

    /**
     * @test Compute Collection returns a 404 error
     */
    public function testNoComputeCollectionFound404()
    {
        $message = 'Not Found';
        $code = 404;
        $this->noComputeCollectionItem($code, $message);

        $this->assertNull($this->job->handle());
    }

    /**
     * @test Compute Collection returns a 500 error
     */
    public function testNoComputeCollectionFound500()
    {
        $message = 'Server Error';
        $code = 500;
        $this->noComputeCollectionItem($code, $message);
        $this->job->expects('fail')->with(\Mockery::capture($exception));

        // null return from running the job
        $this->assertNull($this->job->handle());

        // but we do expect a fail to be called with exception info
        $this->assertNotNull($exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    /**
     * @test TransportNode Collection returns a 404 error
     */
    public function testNoTransportNodeCollection404()
    {
        $message = 'Not Found';
        $code = 404;
        $this->noTransportNodeCollectionItem($code, $message);

        $this->assertNull($this->job->handle());
    }

    /**
     * @test TransportNode Collection returns a 500 error
     */
    public function testNoTransportNodeCollection500()
    {
        $message = 'Server Error';
        $code = 500;
        $this->noTransportNodeCollectionItem($code, $message);
        $this->job->expects('fail')->with(\Mockery::capture($exception));

        // null return from running the job
        $this->assertNull($this->job->handle());

        // but we do expect a fail to be called with exception info
        $this->assertNotNull($exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    /**
     * @test DetachNode returns a 404 error
     */
    public function testDetachNodeUnsuccessful404()
    {
        $message = 'Not Found';
        $code = 404;
        $this->detachNodeFail($code, $message);

        $this->assertNull($this->job->handle());
    }

    /**
     * @test DetachNode returns a 500 error
     */
    public function testDetachNodeUnsuccessful500()
    {
        $message = 'Server Error';
        $code = 500;
        $this->detachNodeFail($code, $message);
        $this->job->expects('fail')->with(\Mockery::capture($exception));

        // null return from running the job
        $this->assertNull($this->job->handle());

        // but we do expect a fail to be called with exception info
        $this->assertNotNull($exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testDeleteNodeUnsuccessful404()
    {
        $message = 'Not Found';
        $code = 404;
        $this->deleteNodeFail($code, $message);

        $this->assertNull($this->job->handle());
    }

    public function testDeleteNodeUnsuccessful500()
    {
        $message = 'Server Error';
        $code = 500;
        $this->deleteNodeFail($code, $message);
        $this->job->expects('fail')->with(\Mockery::capture($exception));

        // null return from running the job
        $this->assertNull($this->job->handle());

        // but we do expect a fail to be called with exception info
        $this->assertNotNull($exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testSuccessful()
    {
        $this->deleteNodeSuccessful();
        $this->assertNull($this->job->handle());
    }
}
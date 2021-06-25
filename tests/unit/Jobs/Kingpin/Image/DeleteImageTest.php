<?php
namespace Tests\unit\Jobs\Kingpin\Image;

use App\Jobs\Kingpin\Image\DeleteImage;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DeleteImageTest extends TestCase
{
    protected $job;

    public function setUp(): void
    {
        parent::setUp();
        $this->job = \Mockery::mock(DeleteImage::class, [$this->image()])->makePartial();
    }

    public function testNoAvailabilityZonesSkip()
    {
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'No availability zones found for Image img-test, skipping') !== false;
        }));
        // The job should not fail
        $this->job->shouldNotReceive('fail');
        $this->assertNull($this->job->handle());
    }

    public function testDeleteImageDoesNotExist()
    {
        // Attach availability zone to image
        $this->image()->availabilityZones()->sync([$this->availabilityZone()->id]);
        // Attach instance to image
        $this->instance()->image_id = $this->image()->id;
        $this->image()->saveQuietly();
        $this->image()->refresh();

        // Prepare Kingpin response 404
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/template/img-test')
            ->andThrow(new RequestException('Not Found', new Request('delete', '', []), new Response(404)));
        // The job should not fail
        $this->job->shouldNotReceive('fail');

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'Failed to delete Image ' . $this->image()->id . ' in az:' . $this->availabilityZone()->id . '. Image was not found, skipping') !== false;
        }));
        $this->assertNull($this->job->handle());
    }

    public function testDeleteImageServerException()
    {
        $this->expectException(ServerException::class);
        $message = 'Server Error';
        $code = 500;
        // Attach availability zone to image
        $this->image()->availabilityZones()->sync([$this->availabilityZone()->id]);
        // Attach instance to image
        $this->instance()->image_id = $this->image()->id;
        $this->image()->saveQuietly();
        $this->image()->refresh();

        // Prepare Kingpin response 500
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/template/img-test')
            ->andThrow(new ServerException('Server Error', new Request('delete', '', []), new Response(500)));

        $this->assertNull($this->job->handle());
    }
}

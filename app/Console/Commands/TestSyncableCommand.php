<?php

namespace App\Console\Commands;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\TestSyncable;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;

class TestSyncableCommand extends Command
{
    protected $signature = 'test-syncable';

    protected $description = 'Tests syncable stuff';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        echo "--------------\n";
        $syncable = new TestSyncable(['testproperty' => "created1"]);
        $syncable->save();

        // test-47135294 - non polymorph
        // test-8ec49304 - polymorph

        //$syncable = TestSyncable::findOrFail("test-8ec49304");
        print_r($syncable->getStatus());
        echo "\n--------------\n";
        //$syncable->testproperty = "something new polymorph1";
        //$syncable->save();
        sleep(20);

        $syncable->delete();


        //$syncable = TestSyncable::create(['testproperty' => "created1"]);



        /*$model = app()->make(Volume::class);
        $model->fill([
            'name' => 'leetestsyncvolume1',
            'vpc_id' => 'vpc-b8fc8730',
            'availability_zone_id' => 'az-aaaaaaaa',
            'capacity' => 20,
            'iops' => 300
        ]);
        if (!$model->save()) {
            echo "Error saving";
        }*/

        //Volume::findOrFail("vol-19a4b029")->delete();

        echo "\n--------------\n";
    }
}

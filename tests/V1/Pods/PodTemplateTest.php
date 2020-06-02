<?php
namespace Tests\Pods;

use App\Http\Controllers\V1\PodController;
use App\Http\Controllers\V1\TemplateController;
use App\Models\V1\Pod;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PodTemplateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testTemplateRetrieval()
    {
        $templateController = \Mockery::mock(TemplateController::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        /** @var \App\Http\Controllers\V1\PodController $podController */
        $podController = \Mockery::mock(PodController::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        /** @var \Illuminate\Http\Request $request */
        $request = \Mockery::mock(Request::class)->makePartial();
        $request->user = new \StdClass();
        $request->user->isAdministrator = true;
        $pod = $podController::getPodById($request, $this->createPod()->getKey());

        /** @var \App\Models\V1\PodTemplate $podTemplateMocks */
        $podTemplateMock = \Mockery::mock('alias:\App\Models\V1\PodTemplate');

        $podTemplateMock->shouldReceive('withPod')
            ->withAnyArgs()
            ->andReturn($this->getTemplates($pod));

        /**
         * Simulate TemplateController::indexPodTemplate
         */

        $podTemplates = $podTemplateMock::withPod($pod);
        $templates = [];

        foreach ($podTemplates as $template) {
            $templates[] = $template->convertToPublicTemplate();
        }

        $templates = $templateController->filterAdminProperties($request, $templates);

        // Check that template->name matches debian-9 structure (word, hyphen, digit)
        $templateNames = [];
        foreach ($templates as $template) {
            $templateNames[] = $template->name;
            $this->assertTrue((bool) preg_match('/^\w+\-\d+$/i', $template->name));
        }

        // Check that there are no duplicate names
        $this->assertEquals(sizeof($templateNames), sizeof(array_unique($templateNames)));
    }

    public function createPod()
    {
        $pod = factory(Pod::class, 1)->create()->first();
        $pod->save();
        $pod->refresh();
        return $pod;
    }

    /**
     * Get a sequence of templates
     * @param \App\Models\V1\Pod $pod
     * @return array
     */
    public function getTemplates(Pod $pod)
    {
        $dataset = json_decode(json_encode([
            [
                'name'       => 'template-1',
                'capacityGB' => 20,
                'guestOS'    => 'debian-6',
                'actualOS'   => 'debian-6',
                'numCPU'     => 4,
                'ramGB'      => 16,
                'disks'      => [[
                    'name'     => 'debian-6-disk',
                    'capacityGB' => 0,
                ]],
                'platform'   => 'debian 6',
            ],
            [
                'name'       => 'template-2',
                'capacityGB' => 20,
                'guestOS'    => 'debian-7',
                'actualOS'   => 'debian-7',
                'numCPU'     => 4,
                'ramGB'      => 16,
                'disks'      => [[
                    'name'     => 'debian-7-disk',
                    'capacityGB' => 0,
                ]],
                'platform'   => 'debian 7',
            ],
            [
                'name'       => 'template-3',
                'capacityGB' => 20,
                'guestOS'    => 'debian-8',
                'actualOS'   => 'debian-8',
                'numCPU'     => 4,
                'ramGB'      => 16,
                'disks'      => [[
                    'name'     => 'debian-8-disk',
                    'capacityGB' => 0,
                ]],
                'platform'   => 'debian 8',
            ],
            [
                'name'       => 'template-4',
                'capacityGB' => 20,
                'guestOS'    => 'debian-9',
                'actualOS'   => 'debian-9',
                'numCPU'     => 4,
                'ramGB'      => 16,
                'disks'      => [[
                    'name'     => 'debian-9-disk',
                    'capacityGB' => 0,
                ]],
                'platform'   => 'debian 9',
            ]
        ]));
        $templates = [];
        foreach ($dataset as $item) {
            $templates[] = $this->getPodTemplateMock($pod, $item);
        }
        return $templates;
    }

    /**
     * Generate a valid ServerLicense
     * @param $item
     * @return \stdClass
     */
    public function getServerLicense($item)
    {
        $serverLicence = new \stdClass();
        $serverLicence->id = 0;
        $serverLicence->name = '';
        $serverLicence->friendly_name = (string)$item->guestOS;
        $serverLicence->category = 'Linux';
        return $serverLicence;
    }

    /**
     * Replicate the functionality of the AbstractTemplate constructor
     * @param \App\Models\V1\Pod $pod
     * @param $item
     * @return \Mockery\Mock
     */
    public function getPodTemplateMock(Pod $pod, $item)
    {
        /** @var \App\Template\PodTemplate $mock */
        $mock = \Mockery::mock(\App\Template\PodTemplate::class)
            ->makePartial();
        $mock->type = 'Pod';
        $mock->subType = 'Base';
        $mock->name = $item->name;
        $mock->size_gb = (string)$item->capacityGB;
        $mock->guest_os = (string)$item->guestOS;
        $mock->actual_os = trim((string)$item->actualOS);
        $mock->cpu = intval($item->numCPU);
        $mock->ram = intval($item->ramGB);
        $mock->encrypted = $item->encrypted ?? false;

        $hard_drives = array();
        foreach ($item->disks as $hard_drive) {
            $hdd = new \stdClass();
            $hdd->name = (string)$hard_drive->name;
            $hdd->capacitygb = intval($hard_drive->capacityGB);
            $hard_drives[] = $hdd;
        }

        $mock->hard_drives = $hard_drives;

        $mock->pod = $pod;
        $mock->serverLicense = $this->getServerLicense($item);
        /** @var \Mockery\Mock $mock */
        return $mock;
    }
}
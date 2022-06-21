<?php

namespace Tests\Unit\Console\Commands\Instance;

use App\Console\Commands\Instance\SetHostGroupToStandard;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SetHostGroupToStandardTest extends TestCase
{
    public SetHostGroupToStandard $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->instanceModel();
        $this->command = new SetHostGroupToStandard();
    }

    public function testResults()
    {
        $this->kingpinServiceMock()->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_VPC_INSTANCES_URI, $this->vpc()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], $this->getResponseJson());
            });

        $this->command->handle();
    }

    private function getResponseJson()
    {
        return <<<EOF
[
  {
    "id": "i-d27eb379-dev",
    "powerState": "poweredOn",
    "hostname": "i-d27eb379-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:eb:80"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "1b047f75-5ac3-4eeb-9b6f-b45546d7844e",
        "createdAt": "2022-05-24T14:28:11.847222Z",
        "sizeGiB": 30,
        "volumeId": "vol-0ee28728-dev"
      }
    ]
  },
  {
    "id": "i-facb31aa-dev",
    "powerState": "poweredOn",
    "hostname": "i-facb31aa-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:8d:52"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "0f6396a0-ce25-4a69-af4d-0d535ca8731d",
        "createdAt": "2022-06-01T14:02:40.880708Z",
        "sizeGiB": 30,
        "volumeId": "vol-06bceb7c-dev"
      }
    ]
  },
  {
    "id": "i-283cd9a0-dev",
    "powerState": "poweredOn",
    "hostname": "i-283cd9a0-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:91:4c"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "5c533754-9777-4179-b4fd-06f3f122e33c",
        "createdAt": "2022-06-06T09:28:43.083982Z",
        "sizeGiB": 30,
        "volumeId": "vol-1b81ae09-dev"
      }
    ]
  },
  {
    "id": "i-2aa05f24-dev",
    "powerState": "poweredOn",
    "hostname": "i-2aa05f24-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:f2:cb"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "82280aab-687a-46cd-a3f8-a5c199c85b53",
        "createdAt": "2022-06-06T09:29:17.641535Z",
        "sizeGiB": 30,
        "volumeId": "vol-b749bfa3-dev"
      }
    ]
  },
  {
    "id": "i-c9f3c8c2-dev",
    "powerState": "poweredOn",
    "hostname": "i-c9f3c8c2-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:ab:dd"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "8421df2d-b5fe-43e9-845c-05d225b763c3",
        "createdAt": "2022-06-06T09:29:56.344004Z",
        "sizeGiB": 30,
        "volumeId": "vol-18264399-dev"
      }
    ]
  },
  {
    "id": "i-c4727d7e-dev",
    "powerState": "poweredOn",
    "hostname": "i-c4727d7e-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:e8:c3"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "b7e489da-b254-4b52-8cca-225a0a2ae2e3",
        "createdAt": "2022-06-06T09:51:35.144959Z",
        "sizeGiB": 30,
        "volumeId": "vol-29e5e74f-dev"
      }
    ]
  },
  {
    "id": "i-2709be37-dev",
    "powerState": "poweredOn",
    "hostname": "i-2709be37-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:68:8f"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "58a4e03e-de27-41e9-9774-013029b5f2b1",
        "createdAt": "2022-06-06T09:52:14.373605Z",
        "sizeGiB": 30,
        "volumeId": "vol-99071a92-dev"
      }
    ]
  },
  {
    "id": "i-7af041c0-dev",
    "powerState": "poweredOn",
    "hostname": "i-7af041c0-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:49:47"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "03bd6a82-f9d3-4ce0-8ccc-6d8d494291e1",
        "createdAt": "2022-06-06T09:52:48.508713Z",
        "sizeGiB": 30,
        "volumeId": "vol-5b0fa5e5-dev"
      }
    ]
  },
  {
    "id": "i-0aee7e35-dev",
    "powerState": "poweredOn",
    "hostname": "i-0aee7e35-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:0d:ec"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "384d0493-8803-4341-a8a4-85105ae14ae9",
        "createdAt": "2022-06-14T08:36:18.336108Z",
        "sizeGiB": 30,
        "volumeId": "vol-cb05ea27-dev"
      }
    ]
  },
  {
    "id": "i-98258bb1-dev",
    "powerState": "poweredOn",
    "hostname": "i-98258bb1-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:63:c5"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "a8839cd9-9f70-4d50-b973-4566d49a4c8c",
        "createdAt": "2022-06-14T09:49:29.570818Z",
        "sizeGiB": 30,
        "volumeId": "vol-fc6be887-dev"
      },
      {
        "iops": 300,
        "uuid": "95f14ac6-6da1-46a7-a59b-e143e3d0ae7b",
        "createdAt": "2022-06-14T09:55:36.633523Z",
        "sizeGiB": 10,
        "volumeId": "vol-fd387918-dev"
      }
    ]
  },
  {
    "id": "i-7395e06e-dev",
    "powerState": "poweredOn",
    "hostname": "i-7395e06e-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:ca:6a"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "4cb7d5e1-3b89-4a38-8db9-a3986f138f54",
        "createdAt": "2022-06-14T09:50:02.410607Z",
        "sizeGiB": 30,
        "volumeId": "vol-aa620c40-dev"
      },
      {
        "iops": 300,
        "uuid": "4f3a02a1-571d-4c8c-a025-09469f6fb5e9",
        "createdAt": "2022-06-14T09:57:00.461819Z",
        "sizeGiB": 10,
        "volumeId": "vol-652f0087-dev"
      }
    ]
  },
  {
    "id": "i-22f04c49-dev",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:e0:4e"
      }
    ],
    "volumes": [
      {
        "iops": -1,
        "uuid": "6760c29f-894e-4882-97b9-d05d857f0d81",
        "createdAt": "2022-06-15T10:51:11.508581Z",
        "sizeGiB": 7,
        "volumeId": "i-22f04c49-dev"
      }
    ]
  },
  {
    "id": "i-bb18e80c-dev",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:d7:9b"
      }
    ],
    "volumes": [
      {
        "iops": -1,
        "uuid": "b82f7f32-c32d-40db-b549-8edbca8372b1",
        "createdAt": "2022-06-15T10:51:57.180415Z",
        "sizeGiB": 7,
        "volumeId": "i-bb18e80c-dev"
      }
    ]
  },
  {
    "id": "i-ff40d454-dev",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:21:35"
      }
    ],
    "volumes": [
      {
        "iops": -1,
        "uuid": "6b75168e-f415-4bb6-99c3-a1c70d38f998",
        "createdAt": "2022-06-15T10:52:01.507928Z",
        "sizeGiB": 7,
        "volumeId": "i-ff40d454-dev"
      }
    ]
  },
  {
    "id": "i-657b0e63-dev",
    "powerState": "poweredOn",
    "hostname": "i-657b0e63-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:09:ae"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "da0a97f2-dfba-42f7-b85f-a516f2409390",
        "createdAt": "2022-06-15T13:42:28.147273Z",
        "sizeGiB": 30,
        "volumeId": "vol-612aae5a-dev"
      },
      {
        "iops": 300,
        "uuid": "e7e0b623-e380-44a4-ae9c-f661fc0baf12",
        "createdAt": "2022-06-15T13:44:26.658196Z",
        "sizeGiB": 10,
        "volumeId": "vol-eae10fdf-dev"
      }
    ]
  },
  {
    "id": "i-87a6bd94-dev",
    "powerState": "poweredOn",
    "hostname": "i-87a6bd94-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:a7:e0"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "e96680fd-63a0-44a4-8996-51c6ce623f5e",
        "createdAt": "2022-06-16T10:52:13.930453Z",
        "sizeGiB": 40,
        "volumeId": "vol-46c35cf3-dev"
      }
    ]
  },
  {
    "id": "i-b2738897-dev",
    "powerState": "poweredOn",
    "hostname": "i-b2738897-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:6b:2d"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "dc076937-f338-4d17-b7bc-4365b3545263",
        "createdAt": "2022-06-20T10:10:35.288605Z",
        "sizeGiB": 30,
        "volumeId": "vol-af71a876-dev"
      }
    ]
  },
  {
    "id": "i-fc4240de-dev",
    "powerState": "poweredOn",
    "hostname": "i-fc4240de-dev",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:84:27"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "1ceaa5a1-c141-45e7-af01-42678f78f3f2",
        "createdAt": "2022-06-20T10:11:03.319534Z",
        "sizeGiB": 30,
        "volumeId": "vol-5eedc3fb-dev"
      }
    ]
  },
  {
    "id": "img-f696620c",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:d5:e6"
      }
    ],
    "volumes": []
  },
  {
    "id": "img-0ad328a6",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:ec:88"
      }
    ],
    "volumes": []
  },
  {
    "id": "i-93dc1c0b",
    "powerState": "poweredOn",
    "hostname": "i-93dc1c0b",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:9d:21"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "f18bbb1c-1ad3-47ab-801d-138dfc60714f",
        "createdAt": "2021-07-15T07:55:53.438139Z",
        "sizeGiB": 30,
        "volumeId": "vol-f063c0b1"
      }
    ]
  },
  {
    "id": "i-e93b38f4",
    "powerState": "poweredOn",
    "hostname": "i-e93b38f4",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:16:40"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "6bd05825-c7e5-43ae-9897-835079b4157f",
        "createdAt": "2021-07-15T09:49:00.844019Z",
        "sizeGiB": 30,
        "volumeId": "vol-55f8a00f"
      }
    ]
  },
  {
    "id": "i-0953ec64",
    "powerState": "poweredOn",
    "hostname": "i-0953ec64",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:c8:4e"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "d5da49ec-362b-4963-8141-9511c8fd42fe",
        "createdAt": "2021-07-16T16:00:13.058214Z",
        "sizeGiB": 30,
        "volumeId": "vol-07922abb"
      }
    ]
  },
  {
    "id": "i-cc7a50c1",
    "powerState": "poweredOn",
    "hostname": "i-cc7a50c1",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:6c:0e"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "d612c955-d705-488d-b7ed-ccc87e1ed1d5",
        "createdAt": "2021-07-20T09:43:06.033977Z",
        "sizeGiB": 30,
        "volumeId": "vol-d9a64403"
      }
    ]
  },
  {
    "id": "i-0683194d",
    "powerState": "poweredOn",
    "hostname": "i-0683194d",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:8c:dd"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "d295a16c-1d14-4165-ad4e-ff825f23bc6c",
        "createdAt": "2021-07-20T09:59:33.021835Z",
        "sizeGiB": 30,
        "volumeId": "vol-cd800d6d"
      }
    ]
  },
  {
    "id": "i-8853fa78",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "orphaned",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:8b:ca"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "5596f758-fc86-4f40-b146-eaf801f0e44b",
        "createdAt": "2021-07-20T19:04:21.220873Z",
        "sizeGiB": 30,
        "volumeId": "vol-c69b08b7"
      }
    ]
  },
  {
    "id": "i-3014c969",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "orphaned",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:a5:21"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "0d61b1b3-fd84-48d4-b297-89654c0d51e7",
        "createdAt": "2021-07-20T19:16:18.538262Z",
        "sizeGiB": 30,
        "volumeId": "vol-087c2b1a"
      }
    ]
  },
  {
    "id": "i-4b1a2f42",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "orphaned",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:f3:43"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "9b485245-0799-4578-b079-098f239d1dcd",
        "createdAt": "2021-07-20T19:26:32.422141Z",
        "sizeGiB": 30,
        "volumeId": "vol-3e350492"
      }
    ]
  },
  {
    "id": "i-f22cd838",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "orphaned",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:08:f6"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "5e99161c-7627-4ab7-900c-005759f9da8c",
        "createdAt": "2021-07-20T19:32:28.661868Z",
        "sizeGiB": 30,
        "volumeId": "vol-b4bf766f"
      }
    ]
  },
  {
    "id": "i-51510f18",
    "powerState": "poweredOn",
    "hostname": "i-51510f18",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:b6:24"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "54445d87-f718-4dd9-abaa-ec69d87fd2b9",
        "createdAt": "2021-07-22T10:10:35.130502Z",
        "sizeGiB": 30,
        "volumeId": "vol-04a96cb7"
      }
    ]
  },
  {
    "id": "i-f3b33ca7",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "orphaned",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:c2:bf"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "3959919a-d57c-4117-a526-d1e8abe91861",
        "createdAt": "2021-07-28T16:28:06.219169Z",
        "sizeGiB": 30,
        "volumeId": "vol-d74eb7dd"
      }
    ]
  },
  {
    "id": "i-d6e7fac8",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "orphaned",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:8b:d8"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "7123fbe5-11c6-471c-b818-dd517d92773f",
        "createdAt": "2021-07-28T16:31:29.811548Z",
        "sizeGiB": 30,
        "volumeId": "vol-d3a544a7"
      }
    ]
  },
  {
    "id": "i-a5e8b2bb",
    "powerState": "poweredOn",
    "hostname": "i-a5e8b2bb",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:c8:51"
      }
    ],
    "volumes": [
      {
        "iops": 300,
        "uuid": "e89f887c-9fc6-4ddc-acb2-4ecfbabc6145",
        "createdAt": "2021-07-29T11:54:21.726962Z",
        "sizeGiB": 30,
        "volumeId": "vol-4ccf4c76"
      }
    ]
  },
  {
    "id": "i-2553afd2",
    "powerState": "poweredOn",
    "hostname": "i-2553afd2",
    "toolsStatus": "toolsOk",
    "toolsRunningStatus": "guestToolsRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": true,
    "nics": [
      {
        "macAddress": "00:50:56:8a:b1:54"
      }
    ],
    "volumes": [
      {
        "iops": 600,
        "uuid": "0b883948-4256-48b8-951f-ad10b068c72c",
        "createdAt": "2021-08-10T07:19:26.735073Z",
        "sizeGiB": 30,
        "volumeId": "vol-5e2498db"
      }
    ]
  },
  {
    "id": "img-91f9ec13-dev",
    "powerState": "poweredOff",
    "hostname": null,
    "toolsStatus": "toolsNotRunning",
    "toolsRunningStatus": "guestToolsNotRunning",
    "toolsVersionStatus": "guestToolsUnmanaged",
    "connectionState": "connected",
    "hostGroupID": "1001",
    "numCPU": 1,
    "ramMiB": 1024,
    "guestOperationsReady": false,
    "nics": [
      {
        "macAddress": "00:50:56:8a:94:24"
      }
    ],
    "volumes": []
  }
]
EOF;


    }
}

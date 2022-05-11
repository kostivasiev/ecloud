<?php

namespace App\Console\Commands\VPC;

use App\Models\V2\AvailabilityZoneable;
use App\Models\V2\Network;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeleteOrphanedResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpc:delete-orphaned-resources {--T|test-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete orphaned resources';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $routers = Router::all()->mapWithKeys(function ($item) {
            return [$item->id => $item];
        });

        $this->processDeletion($routers, 'vpc');

        $networks = Network::with(['router' => fn($q) => $q->withTrashed()])
            ->get()->mapWithKeys(function ($item) {
                return [$item->id => $item];
            });

        $this->processDeletion($networks, 'router');

        return 0;
    }

    /**
     *
     * @param Iterable $resources
     * @param string $parent
     * @return void
     */
    protected function processDeletion(iterable $resources, string $parent): void
    {
        $resourceType = Str::plural(Str::afterLast($resources->first()::class, '\\'));

        $skipped = [];

        $markedForDeletion = [];

        foreach ($resources as $resource) {
            if (str_ends_with($resource->id, 'aaaaaaaa')) {
                continue;
            }

            $existsOnNsx = 'No';
            $reason = null;

            // Has no tasks
            if (!$this->hasTasks($resource)) {
                $reason = 'No tasks';
            }

            // Has no parent resource
            if (!$resource->$parent()->exists()) {
                $reason = 'No ' . $parent;
            }

            if ($resource instanceof AvailabilityZoneable) {
                // Check if resource exists on NSX
                if ($reason) {
                    try {
                        $endpoint = match ($resource::class) {
                            Router::class => 'policy/api/v1/infra/tier-1s/' . $resource->id,
                            Network::class => 'policy/api/v1/infra/tier-1s/' . $resource?->router?->id . '/segments/' . $resource->id,
                        };

                        switch ($resource::class) {
                            case Network::class:
                                if (empty($resource->$parent)) {
                                    $existsOnNsx = 'Unknown - No soft deleted parent record found';
                                } else {
                                    $resource->availabilityZone->nsxService()->get($endpoint);
                                    $existsOnNsx = 'Yes';
                                }
                                break;
                            case Router::class:
                                $resource->availabilityZone->nsxService()->get($endpoint);
                                $existsOnNsx = 'Yes';
                                break;
                        }
                    } catch (ClientException $e) {
                        if ($e->hasResponse() && $e->getResponse()->getStatusCode() != 404) {
                            $reason = null;
                            $skipped[] = [$resource->id, $resource->name, $e->getMessage()];
                        }
                    }
                }
            }

            if ($reason) {
                $markedForDeletion[] = [$resource->id, $resource->name, $reason, $existsOnNsx];
            }
        }

        if (count($skipped) > 0) {
            $this->warn($resourceType . ' Skipped');
            $this->table(
                ['ID', 'Name', 'Reason'],
                $skipped
            );
            $this->info(count($skipped) . ' ' . $resourceType . ' Skipped');
        }

        if (count($markedForDeletion) > 0) {
            $this->info($resourceType . ' Marked For Deletion');
            $this->table(
                ['ID', 'Name', 'Reason', 'Exists on NSX'],
                $markedForDeletion
            );

            $markedForDeletion = collect($markedForDeletion);

            $this->info('Total: ' . $markedForDeletion->count());
            $existsOnNsxTotal = $markedForDeletion->filter(function ($item) {
                return $item[3] === 'Yes';
            })->count();
            $this->info('Total with resources on NSX: ' . $existsOnNsxTotal);

            $undetermined = $markedForDeletion->filter(function ($item) {
                return $item[3] === 'Unknown - No soft deleted parent record found';
            })->count();
            $this->info('Total with undetermined resources on NSX: ' . $undetermined);

            if ($this->confirm('Delete orphaned ' . strtolower($resourceType) . ' with no resource on NSX? (This mill mark the record deleted in the database only)',
                true)) {
                $deleted = 0;

                foreach ($markedForDeletion as [$id, $name, $reason, $exists]) {
                    if ($exists === false) {
                        $this->info('Deleting ' . $id);
                        if (!$this->option('test-run')) {
                            $resources->get($id)->delete();
                        }
                        $deleted++;
                    }
                }
                $this->info('Total ' . $deleted . ' Deleted');
            } else {
                $this->info('Records were not deleted');
            }
        } else {
            $this->info($resourceType . ' marked For Deletion: 0');
        }

        $this->line('----------------------------------------------------------');
    }

    /**
     * @param $resource
     * @return bool
     */
    protected function hasTasks($resource)
    {
        return $resource->tasks()->count() > 0;
    }
}

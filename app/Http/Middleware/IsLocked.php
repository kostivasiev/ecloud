<?php

namespace App\Http\Middleware;

use App\Models\V2\Instance;
use App\Models\V2\OrchestratorConfig;
use Closure;

/**
 * Class IsLocked
 * @package App\Http\Middleware
 *
 * Is an instance locked from updating
 */
class IsLocked
{
    protected array $parameters;

    public function __construct()
    {
        $this->parameters = [
            Instance::class => 'instanceId',
            OrchestratorConfig::class => 'orchestratorConfigId',
        ];
    }

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        foreach ($this->parameters as $class => $parameter) {
            if (array_key_exists($parameter, $request->route()[2])) {
                $model = $class::forUser($request->user())
                    ->findOrFail($request->route($parameter));
                if ($request->user()->isScoped() && $model->locked === true) {
                    return response()->json([
                        'errors' => [
                            [
                                'title' => 'Forbidden',
                                'detail' => 'The specified' . $this->getClassAsWords($model) . ' is locked',
                                'status' => 403,
                            ]
                        ]
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    private function getClassAsWords($className)
    {
        return ucwords(implode(' ', preg_split('/(?=[A-Z])/', class_basename($className))));
    }
}

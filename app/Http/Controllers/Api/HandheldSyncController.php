<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandheldSyncController extends Controller
{
    /**
     * Config-driven reconciliation: entity_type → server table lookup.
     * Adding a new entity = add one entry here.
     */
    private const ENTITY_CONFIG = [
        'sale' => [
            'table' => 'sales_invoices',
            'match_column' => 'client_uuid',
            'server_id_column' => 'id',
            'company_column' => 'company_id',
        ],
        'visit' => [
            'table' => 'customer_visits',
            'match_column' => 'client_uuid',
            'server_id_column' => 'id',
            'company_column' => 'company_id',
        ],
        'car_expense' => [
            'table' => 'vehicle_daily_expenses',
            'match_column' => 'uuid',
            'server_id_column' => 'id',
            'company_column' => 'company_id',
        ],
        'settlement' => [
            'table' => 'settlements',
            'match_column' => 'uuid',
            'server_id_column' => 'id',
            'company_column' => 'company_id',
        ],
        'return_order' => [
            'table' => 'return_orders',
            'match_column' => 'uuid',
            'server_id_column' => 'id',
            'company_column' => 'company_id',
        ],
    ];

    /**
     * POST /api/handheld/sync/reconcile
     *
     * Accepts a list of entity types with their UUIDs, checks the server
     * database for each, and returns which ones are synced vs missing.
     *
     * Request body:
     * {
     *   "entities": [
     *     { "type": "sale", "uuids": ["uuid1", "uuid2"] },
     *     { "type": "visit", "uuids": ["uuid3"] }
     *   ]
     * }
     *
     * Response:
     * {
     *   "sale": {
     *     "synced": [{"uuid": "uuid1", "server_id": 123}],
     *     "missing": ["uuid2"]
     *   },
     *   "visit": { ... }
     * }
     */
    public function reconcile(Request $request)
    {
        $request->validate([
            'entities' => 'required|array',
            'entities.*.type' => 'required|string|in:sale,visit,car_expense,settlement,return_order',
            'entities.*.uuids' => 'required|array|max:5000',
            'entities.*.uuids.*' => 'string|max:100',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;
        $entities = $request->input('entities');
        $response = [];

        foreach ($entities as $entity) {
            $type = $entity['type'];
            $uuids = $entity['uuids'];
            $config = self::ENTITY_CONFIG[$type] ?? null;

            if (!$config) {
                $response[$type] = ['synced' => [], 'missing' => $uuids];
                continue;
            }

            $synced = [];
            $missing = collect($uuids);

            // Chunk to prevent enormous WHERE IN clauses
            collect($uuids)->chunk(500)->each(
                function ($chunk) use ($companyId, $config, &$synced, &$missing) {
                    $found = DB::table($config['table'])
                        ->where($config['company_column'], $companyId)
                        ->whereNotNull($config['match_column'])
                        ->whereIn($config['match_column'], $chunk->toArray())
                        ->pluck($config['server_id_column'], $config['match_column'])
                        ->toArray();

                    foreach ($found as $uuid => $serverId) {
                        $synced[] = [
                            'uuid' => $uuid,
                            'server_id' => (int) $serverId,
                        ];
                    }

                    $syncedUuids = array_column($synced, 'uuid');
                    $missing = $missing->reject(
                        fn ($u) => in_array($u, $syncedUuids)
                    );
                }
            );

            $response[$type] = [
                'synced' => $synced,
                'missing' => $missing->values()->toArray(),
            ];
        }

        Log::info('handheld.sync.reconcile', [
            'company_id' => $companyId,
            'entity_counts' => array_map(fn ($e) => count($e['uuids']), $entities),
            'synced_counts' => array_map(fn ($r) => count($r['synced']), $response),
            'missing_counts' => array_map(fn ($r) => count($r['missing']), $response),
        ]);

        return response()->json($response);
    }
}

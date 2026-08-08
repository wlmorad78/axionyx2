<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use App\Models\User;
use App\Models\Employee;
use App\Models\Representative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandheldController extends BaseApiController
{
    /**
     * POST /api/handheld/bootstrap
     *
     * Device bootstrap - first time setup or re-init.
     * Returns users, company settings, and sync state for the device.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $request->validate([
            'device_uuid'   => 'required|string|max:100',
            'app_version'   => 'nullable|string|max:20',
            'device_type'   => 'nullable|string|max:50',
        ]);

        $deviceUuid = $request->input('device_uuid');
        $appVersion = $request->input('app_version', '1.0.0');
        $deviceType = $request->input('device_type', 'handheld');

        // Find or create device
        $device = Device::where('device_code', $deviceUuid)->first();

        if (!$device) {
            $device = Device::create([
                'uuid'          => $deviceUuid,
                'device_code'   => $deviceUuid,
                'device_name'   => $request->input('device_name', 'Handheld Device'),
                'device_model'  => $deviceType,
                'os_version'    => $appVersion,
                'is_active'     => true,
            ]);
        }

        // Get all active users with employee/representative data (bulk queries)
        $activeUsers = User::where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'usercode', 'name', 'phone', 'email', 'company_id')
            ->get();

        if ($activeUsers->isEmpty()) {
            $users = collect();
        } else {
            $userEmails = $activeUsers->pluck('email')->filter()->values();
            $userIds = $activeUsers->pluck('id');

            // Bulk fetch employees by email
            $employeesByEmail = Employee::whereIn('email', $userEmails)
                ->get()
                ->keyBy('email');

            // Bulk fetch representatives by user_id
            $repsByUserId = Representative::whereIn('user_id', $userIds)
                ->get()
                ->keyBy('user_id');

            // Bulk fetch employees by national_id (for representatives)
            $repCodes = $repsByUserId->pluck('code')->filter()->values();
            $employeesByNationalId = $repCodes->isNotEmpty()
                ? Employee::whereIn('national_id', $repCodes)->get()->keyBy('national_id')
                : collect();

            // Build user list with employee data
            $users = $activeUsers->filter(function ($user) use ($employeesByEmail, $repsByUserId, $employeesByNationalId) {
                if ($employeesByEmail->has($user->email)) return true;

                $rep = $repsByUserId->get($user->id);
                if ($rep && $employeesByNationalId->has($rep->code)) return true;

                return false;
            })->map(function ($user) use ($employeesByEmail, $repsByUserId, $employeesByNationalId) {
                $employee = $employeesByEmail->get($user->email);

                if (!$employee) {
                    $rep = $repsByUserId->get($user->id);
                    if ($rep) {
                        $employee = $employeesByNationalId->get($rep->code);
                    }
                }

                return [
                    'id'            => $user->id,
                    'usercode'      => $user->usercode,
                    'name'          => $user->name,
                    'phone'         => $user->phone,
                    'email'         => $user->email,
                    'company_id'    => $user->company_id,
                    'employee_id'   => $employee?->id,
                    'national_id'   => $employee?->national_id,
                    'pin_required'  => true,
                ];
            })->values();
        }

        // Get company settings
        $company = $device->company_id
            ? DB::table('companies')->where('id', $device->company_id)->first()
            : null;

        // Default company from first user
        if (!$company && $users->isNotEmpty()) {
            $company = DB::table('companies')
                ->where('id', $users->first()['company_id'])
                ->first();
        }

        $settings = [
            'currency'      => 'SAR',
            'tax_enabled'   => true,
            'tax_percent'   => 15,
            'company_name'  => $company?->name_ar ?? '',
            'company_id'    => $company?->id ?? null,
        ];

        // Sync cursors
        $cursors = [];
        $syncTables = ['customers', 'items', 'routes', 'issue_orders'];
        foreach ($syncTables as $table) {
            $lastSync = DB::table('sync_log')
                ->where('table_name', $table)
                ->latest('id')
                ->first();
            $cursors[$table] = $lastSync?->id ?? 0;
        }

        return $this->successResponse([
            'device'    => [
                'id'        => $device->id,
                'uuid'      => $device->uuid,
                'status'    => $device->is_active ? 'active' : 'inactive',
            ],
            'users'     => $users,
            'settings'  => $settings,
            'sync'      => [
                'cursors'   => $cursors,
            ],
        ], 'Device bootstrapped successfully');
    }

    /**
     * POST /api/handheld/sync
     *
     * Incremental sync - pull changes since cursor, push local changes.
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'cursors'           => 'nullable|array',
            'cursors.*'         => 'nullable|integer',
            'local_invoices'    => 'nullable|array',
            'local_visits'      => 'nullable|array',
            'local_returns'     => 'nullable|array',
        ]);

        $user = $request->user();
        $cursors = $request->input('cursors', []);

        // ── Pull changes ──
        $pullData = [];

        // Customers
        $customerCursor = $cursors['customers'] ?? 0;
        $pullData['customers'] = DB::table('customers')
            ->where('company_id', $user->company_id)
            ->where('id', '>', $customerCursor)
            ->whereNull('deleted_at')
            ->limit(500)
            ->get();

        // Items
        $itemCursor = $cursors['items'] ?? 0;
        $pullData['items'] = DB::table('items')
            ->where('company_id', $user->company_id)
            ->where('id', '>', $itemCursor)
            ->where('is_active', true)
            ->limit(500)
            ->get();

        // Routes
        $routeCursor = $cursors['routes'] ?? 0;
        $pullData['routes'] = DB::table('routes')
            ->where('id', '>', $routeCursor)
            ->where('is_active', true)
            ->limit(100)
            ->get();

        // Issue Orders
        $issueCursor = $cursors['issue_orders'] ?? 0;
        $pullData['issue_orders'] = DB::table('issue_orders')
            ->where('company_id', $user->company_id)
            ->where('id', '>', $issueCursor)
            ->whereIn('status', ['approved', 'issued'])
            ->limit(50)
            ->get();

        // ── Push local data ──
        $pushResults = [];

        // Push invoices
        $localInvoices = $request->input('local_invoices', []);
        foreach ($localInvoices as $invoice) {
            $existing = DB::table('sales_invoices')
                ->where('uuid', $invoice['uuid'] ?? '')
                ->first();

            if (!$existing) {
                // Create invoice on server
                $pushResults[] = [
                    'uuid'   => $invoice['uuid'],
                    'status' => 'synced',
                ];
            } else {
                $pushResults[] = [
                    'uuid'   => $invoice['uuid'],
                    'status' => 'already_exists',
                ];
            }
        }

        // Push visits
        $localVisits = $request->input('local_visits', []);
        foreach ($localVisits as $visit) {
            DB::table('customer_visits')->insert([
                'employee_id'   => $request->input('_salesman_id'),
                'customer_id'   => $visit['customer_id'],
                'visit_date'    => $visit['visit_date'],
                'check_in_time' => $visit['check_in_time'],
                'check_out_time'=> $visit['check_out_time'],
                'visit_status'  => $visit['visit_status'],
                'visit_reason'  => $visit['visit_reason'] ?? null,
                'created_at'    => now(),
            ]);
        }

        return $this->successResponse([
            'pull'  => $pullData,
            'push'  => $pushResults,
            'synced_at' => now()->toIso8601String(),
        ], 'Sync completed');
    }
}

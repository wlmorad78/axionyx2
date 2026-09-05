<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'db_file' => 'required|file',
            'version' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $companyId = $user->company_id;
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $salesmanId = $employee?->id ?? $user->id;

        $folder = "databases/{$companyId}/{$salesmanId}";
        $version = $request->input('version', 1);
        $timestamp = now()->format('Y_m_d_His');
        $fileName = "db_v{$version}_{$timestamp}.sqlite";
        $filePath = "{$folder}/{$fileName}";

        Storage::disk('local')->put($filePath, file_get_contents($request->file('db_file')));

        $fileSize = Storage::disk('local')->size($filePath);

        $backupId = DB::table('mobile_database_backups')->insertGetId([
            'company_id' => $companyId,
            'salesman_id' => $salesmanId,
            'version' => $version,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'uploaded_by' => $user->id,
            'status' => 'uploaded',
            'notes' => $request->input('notes'),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message' => 'تم رفع قاعدة البيانات بنجاح',
            'backup_id' => $backupId,
            'version' => $version,
            'file_name' => $fileName,
        ]);
    }

    public function latest(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $salesmanId = $employee?->id ?? $user->id;

        $backup = DB::table('mobile_database_backups')
            ->where('company_id', $companyId)
            ->where('salesman_id', $salesmanId)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();

        if (!$backup) {
            return response()->json(['version' => 0, 'available' => false]);
        }

        return response()->json([
            'version' => $backup->version,
            'file_name' => $backup->file_name,
            'file_size' => $backup->file_size,
            'uploaded_at' => $backup->created_at,
            'notes' => $backup->notes,
            'available' => true,
        ]);
    }

    public function download(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        $employee = DB::table('employees')->where('id', $user->id)->first();
        $salesmanId = $employee?->id ?? $user->id;

        $version = $request->input('version');

        $query = DB::table('mobile_database_backups')
            ->where('company_id', $companyId)
            ->where('salesman_id', $salesmanId)
            ->orderByDesc('version')
            ->orderByDesc('id');

        if ($version) {
            $query->where('version', $version);
        }

        $backup = $query->first();

        if (!$backup) {
            return response()->json(['message' => 'لا توجد نسخة متاحة'], 404);
        }

        $fullPath = Storage::disk('local')->path($backup->file_path);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'الملف غير موجود على السيرفر'], 404);
        }

        DB::table('mobile_database_backups')
            ->where('id', $backup->id)
            ->update(['status' => 'downloaded', 'updated_at' => now()]);

        return response()->file($fullPath, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$backup->file_name}\"",
        ]);
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $backups = DB::table('mobile_database_backups')
            ->where('company_id', $companyId)
            ->leftJoin('employees', 'mobile_database_backups.salesman_id', '=', 'employees.id')
            ->orderByDesc('mobile_database_backups.version')
            ->orderByDesc('mobile_database_backups.id')
            ->get([
                'mobile_database_backups.*',
                DB::raw("TRIM(COALESCE(employees.first_name_ar,'') || ' ' || COALESCE(employees.second_name_ar,'') || ' ' || COALESCE(employees.third_name_ar,'') || ' ' || COALESCE(employees.last_name_ar,'')) as salesman_name"),
            ]);

        return response()->json(['data' => $backups]);
    }

    public function adminPage()
    {
        $backups = DB::table('mobile_database_backups')
            ->leftJoin('employees', 'mobile_database_backups.salesman_id', '=', 'employees.id')
            ->orderByDesc('mobile_database_backups.version')
            ->orderByDesc('mobile_database_backups.id')
            ->get([
                'mobile_database_backups.*',
                DB::raw("TRIM(COALESCE(employees.first_name_ar,'') || ' ' || COALESCE(employees.second_name_ar,'') || ' ' || COALESCE(employees.third_name_ar,'') || ' ' || COALESCE(employees.last_name_ar,'')) as salesman_name"),
            ]);

        $html = '<!DOCTYPE html><html dir="rtl" lang="ar"><head><meta charset="UTF-8"><title>نسخ قاعدة البيانات المحمولة</title>';
        $html .= '<style>body{font-family:sans-serif;background:#1a1a2e;color:#fff;padding:20px}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{padding:10px;border:1px solid #333;text-align:center}th{background:#16213e}tr:nth-child(even){background:#1a1a2e}tr:nth-child(odd){background:#0f3460}a.btn{display:inline-block;padding:6px 16px;background:#10b981;color:#fff;text-decoration:none;border-radius:6px;font-size:13px}a.btn:hover{background:#059669}h1{color:#10b981}</style>';
        $html .= '</head><body><h1>📦 نسخ قاعدة البيانات المحمولة</h1>';
        $html .= '<table><tr><th>#</th><th>المندوب</th><th>الإصدار</th><th>الملف</th><th>الحجم</th><th>الحالة</th><th>التاريخ</th><th>تحميل</th></tr>';

        foreach ($backups as $b) {
            $size = $b->file_size > 1048576
                ? round($b->file_size / 1048576, 2) . ' MB'
                : round($b->file_size / 1024, 1) . ' KB';
            $statusColor = match($b->status) {
                'uploaded' => '#10b981',
                'downloaded' => '#f59e0b',
                'applied' => '#3b82f6',
                default => '#666',
            };
            $statusLabel = match($b->status) {
                'uploaded' => 'مرفوع',
                'downloaded' => 'تم التحميل',
                'applied' => 'تم التطبيق',
                default => $b->status,
            };
            $html .= "<tr>";
            $html .= "<td>{$b->id}</td>";
            $html .= "<td>{$b->salesman_name}</td>";
            $html .= "<td>v{$b->version}</td>";
            $html .= "<td>{$b->file_name}</td>";
            $html .= "<td>{$size}</td>";
            $html .= "<td style='color:{$statusColor}'>{$statusLabel}</td>";
            $html .= "<td>" . substr($b->created_at, 0, 16) . "</td>";
            $html .= "<td><a class='btn' href='/admin/database-backups/download/{$b->id}'>تحميل</a></td>";
            $html .= "</tr>";
        }

        $html .= '</table></body></html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function adminDownload($id)
    {
        $backup = DB::table('mobile_database_backups')->where('id', $id)->first();
        if (!$backup) {
            return response()->json(['message' => 'غير موجود'], 404);
        }
        $fullPath = Storage::disk('local')->path($backup->file_path);
        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'الملف غير موجود'], 404);
        }
        DB::table('mobile_database_backups')->where('id', $id)->update(['status' => 'downloaded']);
        return response()->file($fullPath, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$backup->file_name}\"",
        ]);
    }

    public function adminUpload(Request $request)
    {
        $request->validate([
            'db_file' => 'required|file',
            'backup_id' => 'required|integer',
        ]);

        $backup = DB::table('mobile_database_backups')->where('id', $request->input('backup_id'))->first();
        if (!$backup) {
            return response()->json(['message' => 'غير موجود'], 404);
        }

        $fullPath = Storage::disk('local')->path($backup->file_path);
        file_put_contents($fullPath, file_get_contents($request->file('db_file')));

        $newVersion = $backup->version + 1;
        $folder = dirname($backup->file_path);
        $timestamp = now()->format('Y_m_d_His');
        $newFileName = "db_v{$newVersion}_{$timestamp}.sqlite";
        $newFilePath = "{$folder}/{$newFileName}";
        Storage::disk('local')->put($newFilePath, file_get_contents($request->file('db_file')));

        DB::table('mobile_database_backups')->insert([
            'company_id' => $backup->company_id,
            'salesman_id' => $backup->salesman_id,
            'version' => $newVersion,
            'file_name' => $newFileName,
            'file_path' => $newFilePath,
            'file_size' => Storage::disk('local')->size($newFilePath),
            'uploaded_by' => null,
            'status' => 'uploaded',
            'notes' => "تعديل من لوحة التحكم",
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'message' => "تم رفع النسخة المعدلة بنجاح - الإصدار: {$newVersion}",
            'version' => $newVersion,
        ]);
    }
}

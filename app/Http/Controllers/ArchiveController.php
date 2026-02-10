<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchiveController extends Controller
{
    private string $root = 'quinos-archive';

    private array $months = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];

    public function index()
    {
        // halaman upload + shortcut browse root
        return view('archive.upload', [
            'months' => $this->months,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'branch'  => 'required|in:sq,gresik,lantai12',
            'year'    => 'nullable|digits:4',
            'month'   => 'nullable|in:' . implode(',', array_keys($this->months)),
            'files'   => 'required',
            'files.*' => 'file|max:51200', // 50MB per file
        ]);

        $branch = $request->input('branch');
        $year   = $request->input('year');
        $month  = $request->input('month'); // sudah '01'..'12' atau null

        // folder target:
        // quinos-archive/{branch}/{year}/{month}
        $dir = "{$this->root}/{$branch}";
        if ($year)  $dir .= "/{$year}";
        if ($month) $dir .= "/{$month}";

        $saved = 0;
        foreach ((array) $request->file('files') as $file) {
            $original = $file->getClientOriginalName();
            $safeOriginal = preg_replace('/[^A-Za-z0-9._-]/', '_', $original);

            $filename = date('Ymd_His') . '_' . substr(uniqid(), -5) . '_' . $safeOriginal;

            Storage::disk('public')->putFileAs($dir, $file, $filename);
            $saved++;
        }

        return redirect()
            ->route('archive.browse', ['branch' => $branch, 'year' => $year, 'month' => $month])
            ->with('ok', "Berhasil upload {$saved} file ke {$dir}");
    }

    public function browse(?string $branch = null, ?string $year = null, ?string $month = null)
    {
        $disk = Storage::disk('public');

        $path = $this->root;
        if ($branch) $path .= '/' . $branch;
        if ($year)   $path .= '/' . $year;
        if ($month)  $path .= '/' . str_pad($month, 2, '0', STR_PAD_LEFT);

        $folders = $disk->directories($path);
        $files   = $disk->files($path);

        sort($folders);
        usort($files, fn($a, $b) => $disk->lastModified($b) <=> $disk->lastModified($a));

        return view('archive.browse', [
            'path'      => $path,
            'branch'    => $branch,
            'year'      => $year,
            'month'     => $month,
            'monthName' => $month ? ($this->months[str_pad($month, 2, '0', STR_PAD_LEFT)] ?? null) : null,
            'months'    => $this->months,
            'folders'   => $folders,
            'files'     => $files,
        ]);
    }

    public function download(string $path)
    {
        $path = ltrim($path, '/');

        if (!str_starts_with($path, $this->root . '/')) {
            abort(403, 'Forbidden path.');
        }

        if (str_contains($path, '..')) {
            abort(403, 'Forbidden path.');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->download($disk->path($path), basename($path));
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = ltrim($request->input('path'), '/');

        // security: cegah hapus di luar folder quinos-archive dan cegah traversal
        if (!str_starts_with($path, $this->root . '/')) {
            abort(403, 'Path tidak valid');
        }
        if (str_contains($path, '..')) {
            abort(403, 'Path tidak valid');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return back()->with('ok', 'File sudah tidak ada.');
        }

        $disk->delete($path);

        return back()->with('ok', 'File berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Téléversement fragmenté et reprenable (faible connexion) :
 * 1) POST /uploads → uuid ; 2) GET /uploads/{uuid} → octets reçus ; 3) POST /uploads/{uuid}/chunk (X-Offset) jusqu'à la fin.
 */
class UploadController extends Controller
{
    public function init(Request $request)
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1', 'max:'.(config('vwajen.limits.video_mb') * 1048576)],
            'mime' => ['nullable', 'string', 'max:100'],
        ]);
        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        abort_unless(in_array($ext, ['mp4', 'webm', 'mov', 'm4v', '3gp', 'mkv', 'mp3', 'm4a', 'ogg', 'wav'], true), 422, __('Format non pris en charge.'));

        // Reprise : un upload identique non terminé est réutilisé.
        $upload = Upload::where('user_id', $request->user()->id)->where('filename', $data['filename'])->where('size', $data['size'])
            ->where('status', 'uploading')->where('created_at', '>=', now()->subDay())->first();

        if (! $upload) {
            $uuid = (string) Str::uuid();
            $upload = Upload::create([
                'uuid' => $uuid, 'user_id' => $request->user()->id, 'filename' => $data['filename'], 'mime' => $data['mime'] ?? null,
                'size' => $data['size'], 'path' => 'chunks/'.$uuid.'.part',
            ]);
            Storage::disk('local')->put($upload->path, '');
        }

        return response()->json(['uuid' => $upload->uuid, 'received' => (int) $upload->received, 'chunk' => config('vwajen.limits.chunk_kb') * 1024]);
    }

    public function status(Request $request, string $uuid)
    {
        $upload = Upload::where('uuid', $uuid)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(['received' => (int) $upload->received, 'size' => (int) $upload->size, 'status' => $upload->status]);
    }

    public function chunk(Request $request, string $uuid)
    {
        $upload = Upload::where('uuid', $uuid)->where('user_id', $request->user()->id)->where('status', 'uploading')->firstOrFail();
        $offset = (int) $request->header('X-Offset', -1);
        if ($offset !== (int) $upload->received) {
            return response()->json(['received' => (int) $upload->received, 'error' => 'offset_mismatch'], 409);
        }

        $data = $request->getContent();
        abort_if(strlen($data) === 0 || strlen($data) > config('vwajen.limits.chunk_kb') * 1024 * 2, 422);
        abort_if($upload->received + strlen($data) > $upload->size, 422);

        $fh = fopen(Storage::disk('local')->path($upload->path), 'ab');
        fwrite($fh, $data);
        fclose($fh);

        $upload->received += strlen($data);
        if ($upload->received >= $upload->size) {
            $upload->status = 'complete';
        }
        $upload->save();

        return response()->json(['received' => (int) $upload->received, 'status' => $upload->status]);
    }
}

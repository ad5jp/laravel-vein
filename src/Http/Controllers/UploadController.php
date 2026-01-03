<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UploadController extends Controller
{
    public function uploadSingle(Request $request): JsonResponse
    {
        $uploaded_file = $request->file('upload');

        if (!$uploaded_file->isValid()) {
            abort(400, $uploaded_file->getErrorMessage());
        }

        try {
            // TODO リサイズ処理
            $path = $uploaded_file->store(trim(config('vein.temporary_path'), '/'), config('vein.temporary_disk'));

            $service = new UploadService();
            $preview = $service->forPreview($uploaded_file->getContent(), $uploaded_file->getClientMimeType(), $uploaded_file->getClientOriginalName());

            $json = [
                'tmp_path' => $path,
                'file_name' => $uploaded_file->getClientOriginalName(),
                'file_size' => $uploaded_file->getSize(),
                'mime_type' => $uploaded_file->getClientMimeType(),
            ];

            return response()->json([
                'preview' => $preview,
                'value' => json_encode($json),
            ]);
        } catch (Throwable $e) {
            report($e);
            abort(400, $e->getMessage());
        }
    }
}

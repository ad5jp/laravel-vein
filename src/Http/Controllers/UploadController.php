<?php

declare(strict_types=1);

namespace AD5jp\Vein\Http\Controllers;

use AD5jp\Vein\Form\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UploadController extends Controller
{
    public function uploadSingle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'upload' => [
                'required',
                'file',
                'mimes:'.implode(',', config('vein.upload_extensions')),
                'max:'.config('vein.upload_max_kilobytes'),
            ],
        ], [], ['upload' => 'ファイル']);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first('upload'),
            ], 422);
        }

        $uploaded_file = $request->file('upload');

        try {
            // TODO リサイズ処理
            $path = $uploaded_file->store(trim(config('vein.temporary_path'), '/'), config('vein.temporary_disk'));

            $service = new UploadService;
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
            // 例外文にはサーバー内部のパスが載るため、そのままは返さない
            report($e);

            return response()->json([
                'message' => 'ファイルの保存に失敗しました。',
            ], 500);
        }
    }
}

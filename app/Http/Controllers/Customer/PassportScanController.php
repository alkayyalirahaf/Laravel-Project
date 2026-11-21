<?php

namespace App\Http\Controllers\Customer;

use App\Services\PassportExtraction;
use App\Services\PassportExtractionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PassportScanController
{
    public function __invoke(Request $request, PassportExtraction $extractor): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'passport_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'consent' => ['required', 'accepted'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'fields' => [],
                'warnings' => ['Choose a supported passport image and confirm consent before scanning.'],
                'errors' => $validator->errors(),
            ], 422);
        }

        $customerId = $request->user()?->customer?->getKey();
        abort_unless(is_numeric($customerId) && (int) $customerId > 0, 403);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'passport-scan-');
        if (! is_string($temporaryPath)) {
            return $this->unavailableResponse();
        }

        try {
            $uploadedPath = $validator->validated()['passport_image']->getRealPath();
            if (! is_string($uploadedPath) || ! @copy($uploadedPath, $temporaryPath)) {
                return $this->unavailableResponse();
            }

            return response()->json($extractor->extract($temporaryPath, (int) $customerId));
        } catch (PassportExtractionException $exception) {
            $status = $exception->reason === PassportExtractionException::UNREADABLE ? 422 : 503;

            return response()->json([
                'available' => false,
                'fields' => [],
                'warnings' => [$exception->getMessage()],
            ], $status);
        } catch (Throwable) {
            return $this->unavailableResponse();
        } finally {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private function unavailableResponse(): JsonResponse
    {
        return response()->json([
            'available' => false,
            'fields' => [],
            'warnings' => ['Passport scanning is temporarily unavailable. Please enter the details manually.'],
        ], 503);
    }
}

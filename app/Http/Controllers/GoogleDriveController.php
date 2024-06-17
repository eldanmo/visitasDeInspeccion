<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoogleDriveController extends Controller
{
    public function uploadFile(Request $request)
    {
        $client = new Client();
        $client->setAuthConfig(getenv('GOOGLE_DRIVE_CREDENTIALS_PATH'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setScopes(Drive::DRIVE);
        $client->fetchAccessTokenWithAssertion();
        $service = new Drive($client);

        $file = $request->file('archivo');
        $fileName = $file->getClientOriginalName();
        $fileContent = file_get_contents($file->getRealPath());

        $fileMetadata = new DriveFile([
            'name' => $fileName,
            'parents' => [getenv('Hy_YBKDNjaQhu_umiG14bhxXZ8y')]
        ]);

        $createdFile = $service->files->create($fileMetadata, [
            'data' => $fileContent,
            'mimeType' => 'application/octet-stream',
            'uploadType' => 'multipart'
        ]);

        return response()->json($createdFile);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Google\Client;
use Google\Service\Drive;

class GoogleDriveService extends Model
{
    use HasFactory;

    private $client;
    private $driveService;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(getenv('GOOGLE_APPLICATION_CREDENTIALS'));
        $this->client->addScope(Drive::DRIVE_FILE);

        $this->driveService = new Drive($this->client);
    }

    public function uploadFile($filePath, $googleDriveFolderId = null)
    {

    }

    public function downloadFile($fileId, $destinationPath)
    {

    }
}

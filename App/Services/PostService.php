<?php

namespace App\Services;

use App\Controllers\DatabaseController;
use App\Jobs\FaceInfoJob\Publish;
use Carbon\Carbon;
use Psr\Http\Message\UploadedFileInterface;

class PostService
{
    /**
     * @var array
     */
    private array $result;

    public function __construct(?string $name, ?UploadedFileInterface $photo)
    {
        if (empty($name) || empty($photo)) {
            $this->result = ['status' => 'error', 'message' => 'Имя или файл не может быть пустым'];
            return 0;
        }

        $connection = (new DatabaseController())->getConnection();
        $statement = $connection->prepare("SELECT * FROM client_requests WHERE client_name = :name AND client_filename = :filename");
        $statement->execute([
            'name' => $name,
            'filename' => $photo->getClientFilename()
        ]);

        $request = $statement->fetch();
        if (!empty($request)) {
            $this->result = [
                'status' => $request['task_status'],
                'task' => $request['id'],
                'result' => $request['task_result']
            ];
            return 0;
        }

        $photo->moveTo('/var/www/public/storage/' . $photo->getClientFilename());

        $statement = $connection->prepare("INSERT INTO client_requests (task_status, client_name, client_filename, client_mime_type, created_at, updated_at) 
                VALUES ('received', :name, :filename, :mime_type, :created, :updated)");
        $statement->execute([
            'name' => $name,
            'filename' => $photo->getClientFilename(),
            'mime_type' => $photo->getClientMediaType(),
            'created' => Carbon::now(),
            'updated' => Carbon::now()
        ]);

        $statement = $connection->prepare("SELECT * FROM client_requests WHERE client_name = :name AND client_filename = :filename");
        $statement->execute([
            'name' => $name,
            'filename' => $photo->getClientFilename()
        ]);

        $request = $statement->fetch();
        $this->result = [
            'status' => $request['task_status'],
            'task' => $request['id'],
            'result' => $request['task_result']
        ];

        $publisher = new Publish();
        $publisher->dispatch($request['id']);

        return 0;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
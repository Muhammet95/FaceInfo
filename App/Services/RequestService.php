<?php

namespace App\Services;

use App\Controllers\DatabaseController;
use CURLFile;
use PDO;

class RequestService
{
    const SUCCESS = 'success';
    const ERROR = 'error';
    const RETRY = 'retry';
    /**
     * @var int
     */
    private int $id;
    /**
     * @var PDO
     */
    private PDO $connection;

    /**
     * @var string
     */
    private $url = 'http://merlinface.com:12345/api/';
    /**
     * @var
     */
    private $retry_id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->connection = (new DatabaseController())->getConnection();
    }

    public function process()
    {
        $statement = $this->connection->prepare("SELECT * FROM client_requests WHERE id = :id");
        $statement->execute([
            'id' => $this->id
        ]);
        $request = $statement->fetch();

        if (empty($request))
            return 0;

        $name = $request['client_name'];
        $filename = $request['client_filename'];
        $mime = $request['client_mime_type'];

        $response = $this->getRequest($name, $filename, $mime);
        $status = self::ERROR;
        if (is_string($response) && !empty($response))
            $status = $this->handleResponse($response);

        while ($status === self::RETRY) {
            sleep(5);
            $response = $this->retryRequest();
            $status = $this->handleResponse($response);
        }

        return 0;
    }

    /**
     * @param string $name
     * @param string $filename
     * @param string $mime
     * @return bool|string
     */
    protected function getRequest(string $name, string $filename, string $mime)
    {
        $data = [
            'name' => $name,
            'photo' => new CURLFile("/var/www/public/storage/$filename", $mime)
        ];
        return $this->doRequest($data);
    }

    public function handleResponse(string $response)
    {
        $data = json_decode($response, true);
        if (empty($data['status']))
            return self::ERROR;

        if (($data['status'] === 'ready' || $data['status'] === 'success') && !empty($data['result'])) {

            $statement = $this->connection->prepare("UPDATE client_requests 
                SET task_status = 'ready', task_result = :result WHERE id = :id");
            $statement->execute([
                'result' => $data['result'],
                'id' => $this->id
            ]);
            $statement->fetch();
            return self::SUCCESS;
        }

        if ($data['status'] === 'wait' && !empty($data['retry_id'])) {

            $statement = $this->connection->prepare("UPDATE client_requests 
                SET task_status = :status, task_retry_id = :retry_id WHERE id = :id");
            $statement->execute([
                'status' => $data['status'],
                'retry_id' => $data['retry_id'],
                'id' => $this->id
            ]);
            $statement->fetch();
            $this->retry_id = $data['retry_id'];

            return self::RETRY;
        }

        return self::ERROR;
    }

    /**
     * @return bool|string
     */
    protected function retryRequest()
    {
        $data = [
            'retry_id' => $this->retry_id,
        ];
        return $this->doRequest($data);
    }

    /**
     * @param $data
     * @return bool|string
     */
    protected function doRequest($data)
    {
        $headers = array("Content-Type:multipart/form-data");

        $options = array(
            CURLOPT_URL => $this->url,
            CURLOPT_HEADER => false,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        );

        $curl = curl_init($this->url);
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);
        echo "RESPONSE: $response\n";
        return $response;
    }
}
<?php

namespace App\Services;

use App\Controllers\DatabaseController;

class GetService
{
    /**
     * @var array|string[]
     */
    private array $result;

    /**
     * @param int $task_id
     */
    public function __construct(int $task_id)
    {
        if (empty($task_id)) {
            $this->result = ['status' => 'not_found', 'result' => null];
            return 0;
        }

        $connection = (new DatabaseController())->getConnection();
        $statement = $connection->prepare("SELECT * FROM client_requests WHERE id = :task_id");
        $statement->execute([
            'task_id' => $task_id
        ]);

        $request = $statement->fetch();

        if (empty($request)) {
            $this->result = ['status' => 'not_found', 'result' => null];
            return 0;
        }

        if ($request['task_status'] === 'ready') {
            $this->result = ['status' => 'ready', 'result' => $request['task_result']];
            return 0;
        }

        $this->result = ['status' => 'wait', 'result' => null];
        return 0;
    }

    /**
     * @return array|string[]
     */
    public function getResult()
    {
        return $this->result;
    }
}
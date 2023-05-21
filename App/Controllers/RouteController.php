<?php

namespace App\Controllers;

use App\Services\GetService;
use App\Services\PostService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RouteController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return Response
     */
    public function get(Request $request, Response $response, $args)
    {
        try {
            $task_id = $request->getQueryParams()['task_id'];

            $service = new GetService((int)$task_id);
            $result = $service->getResult();

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $exception) {
            $response->getBody()->write(json_encode(['message' => 'Ошибка запроса']));
            return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function post(Request $request, Response $response, $args)
    {
        try {
            $name = $request->getParsedBody()['name'];
            $photo = $request->getUploadedFiles()['photo'];

            $service = new PostService($name, $photo);
            $result = $service->getResult();

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $exception) {
            $response->getBody()->write(json_encode(['message' => $exception->getMessage()]));
            return $response->withStatus(500);
        }
    }
}
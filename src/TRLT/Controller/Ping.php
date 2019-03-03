<?php

namespace TRLT\Controller;

use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class Ping
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response)
    {
        $response = $response->withBody(new \Slim\Http\Body(fopen('php://temp', 'r+')));
        $response->getBody()->write('pong');

        return $response;
    }

}

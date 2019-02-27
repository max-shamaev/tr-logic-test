<?php

namespace TRLTTest\Controller;

class ImagesTest extends \PHPUnit\Framework\TestCase
{

    protected $testImages = [
        'https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_272x92dp.png',
        'https://upload.wikimedia.org/wikipedia/en/a/a9/Example.jpg',
        'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6e/802d1aq_Wiki_Example.gif/800px-802d1aq_Wiki_Example.gif',
    ];

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneSuccessJSONURL()
    {
        $response = $this->buildJSONRequest([$this->testImages[0]]);

        $this->checkJSONResponse($response, ['ok'], [$this->testImages[0]]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneFailJSONURL()
    {
        // Empty request
        $response = $this->buildJSONRequest();

        $this->assertEquals(400, $response->getStatusCode());

        $response->getBody()->rewind();
        $this->assertEquals('', $response->getBody()->getContents());

        // Request with wrong URL
        $response = $this->buildJSONRequest(['http://example.com/fail']);

        $this->assertEquals(400, $response->getStatusCode());

        $this->checkJSONResponse($response, ['wrong_image'], [null]);

        // Request with wrong file type
        $response = $this->buildJSONRequest(['https://www.w3schools.com/html/html_examples.asp']);

        $this->assertEquals(400, $response->getStatusCode());

        $this->checkJSONResponse($response, ['wrong_image_type'], [null]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageManySuccessJSONURL()
    {
        $response = $this->buildJSONRequest($this->testImages);

        $this->assertEquals(200, $response->getStatusCode());

        $results = array_map(function() { return 'ok'; }, $this->testImages);
        $this->checkJSONResponse($response, $results, $this->testImages);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageManyFailJSONURL()
    {
        $response = $this->buildJSONRequest([$this->testImages[0], $this->testImages[1] . '___']);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'wrong_image'], [$this->testImages[0], null]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneSuccessJSONBase64()
    {
        $response = $this->buildJSONRequest(
            [
                'data:image/png;base64,' . base64_encode(file_get_contents($this->testImages[0])),
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok'], [$this->testImages[0]]);

        // Without MIME type
        $response = $this->buildJSONRequest(
            [
                'data:base64,' . base64_encode(file_get_contents($this->testImages[0])),
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok'], [$this->testImages[0]]);

        // With wrong MIME type
        $response = $this->buildJSONRequest(
            [
                'data:application/windows;base64,' . base64_encode(file_get_contents($this->testImages[0])),
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok'], [$this->testImages[0]]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneFailJSONBase64()
    {
        $response = $this->buildJSONRequest(
            [
                'data:image/png;base64,123' . base64_encode(file_get_contents($this->testImages[0])),
            ]
        );

        $this->assertEquals(400, $response->getStatusCode());

        $this->checkJSONResponse($response, ['wrong_image'], [null]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageManySuccessJSONBase64()
    {
        $images = [];
        foreach ($this->testImages as $v) {
            $images[] = 'data:base64,' . base64_encode(file_get_contents($v));
        }

        $response = $this->buildJSONRequest($images);

        $this->assertEquals(200, $response->getStatusCode());

        $results = array_map(function() { return 'ok'; }, $this->testImages);
        $this->checkJSONResponse($response, $results, $this->testImages);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageManyFailJSONBase64()
    {
        $images = [
            'data:base64,' . base64_encode(file_get_contents($this->testImages[0])),
            'data:base64,123' . base64_encode(file_get_contents($this->testImages[1])),
        ];

        $response = $this->buildJSONRequest($images);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'wrong_image'], [$this->testImages[0], null]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneFailJSONRaw()
    {
        $response = $this->buildJSONRequest(
            [
                file_get_contents($this->testImages[0]),
            ]
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneSuccessFormFile()
    {
        $response = $this->buildFormFileRequest([$this->testImages[0]]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok'], [$this->testImages[0]]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageOneFailFormFile()
    {

        // Empty request
        $response = $this->buildFormFileRequest();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());

        // Request with wrong file type
        $response = $this->buildFormFileRequest(['https://www.w3schools.com/html/html_examples.asp']);

        $this->assertEquals(400, $response->getStatusCode());

        $this->checkJSONResponse($response, ['wrong_image_type'], [null]);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageManySuccessFormFile()
    {
        $response = $this->buildFormFileRequest($this->testImages);

        $this->assertEquals(200, $response->getStatusCode());

        $results = array_map(function() { return 'ok'; }, $this->testImages);
        $this->checkJSONResponse($response, $results, $this->testImages);
    }

    /**
     * @covers \TRLT\Controller\Images::uploadImage
     */
    public function testUploadImageManyFailFormFile()
    {
        // Wrong size
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_INI_SIZE]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'wrong_image_size'], [$this->testImages[0], null]);

        // Wrong size
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_FORM_SIZE]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'wrong_image_size'], [$this->testImages[0], null]);

        // Partial
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_PARTIAL]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'cannot_save'], [$this->testImages[0], null]);

        // No file
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_NO_FILE]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'cannot_save'], [$this->testImages[0], null]);

        // No temporary directory
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_NO_TMP_DIR]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'wrong_image'], [$this->testImages[0], null]);

        // Cannot write
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_CANT_WRITE]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'cannot_save'], [$this->testImages[0], null]);

        // Wrong extension
        $response = $this->buildFormFileRequest([$this->testImages[0], $this->testImages[1]], [null, UPLOAD_ERR_EXTENSION]);

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkJSONResponse($response, ['ok', 'wrong_image_type'], [$this->testImages[0], null]);
    }

    /**
     * @param string[] $files
     *
     * @return \Slim\Http\Response
     */
    protected function buildJSONRequest(array $files = [])
    {
        $uri = \Slim\Http\Uri::createFromString('http://example.com/images');
        $headers = new \Slim\Http\Headers(
            [
                'Content-Type' => ['application/json']
            ]
        );
        $fp = fopen('php://temp', 'r+');
        if ($files) {
            fwrite($fp, json_encode($files));
        }
        $body = new \Slim\Http\Stream($fp);

        $request = new \Slim\Http\Request(
            'POST',
            $uri,
            $headers,
            [],
            [],
            $body,
            []
        );

        $response = new \Slim\Http\Response();

        $controller = new \TRLT\Controller\Images();

        return $controller->uploadImage($request, $response);
    }

    /**
     * @param string[] $files
     * @param string[] $statuses
     *
     * @return \Slim\Http\Response
     */
    protected function buildFormFileRequest(array $files = [], array $statuses = [])
    {
        $uri = \Slim\Http\Uri::createFromString('http://example.com/images');
        $headers = new \Slim\Http\Headers(
            [
                'Content-Type' => ['multipart/form-data']
            ]
        );

        $body = new \Slim\Http\Stream(fopen('php://temp', 'r+'));

        $uploadFiles = [];
        foreach ($files as $i => $file) {

            $path = null;

            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', $file);
            if ($res->getStatusCode() == 200) {
                $filename = basename($file);
                $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
                if (!file_put_contents($path, $res->getBody()->getContents())) {
                    $path = null;
                }
            }

            $this->assertInternalType('string', $path);

            $data = getimagesize($path);
            $uploadFiles[] = new \Slim\Http\UploadedFile(
                $path,
                $filename,
                $data['mime'],
                filesize($path),
                $statuses[$i] ?? UPLOAD_ERR_OK
            );
        }

        $request = new \Slim\Http\Request(
            'POST',
            $uri,
            $headers,
            [],
            [],
            $body,
            $uploadFiles
        );

        $response = new \Slim\Http\Response();

        $controller = new \TRLT\Controller\Images();

        return $controller->uploadImage($request, $response);
    }

    /**
     * @param \Slim\Http\Response $response
     * @param string[]            $results
     * @param string[]            $files
     */
    protected function checkJSONResponse(\Slim\Http\Response $response, array $results, array $files)
    {
        $this->assertEquals(['Content-Type' => ['application/json;charset=utf-8']], $response->getHeaders());

        $response->getBody()->rewind();
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertInternalType('array', $data);
        $this->assertEquals(['result', 'ids'], array_keys($data));

        $this->assertEquals(count($data['result']), count($results));
        $this->assertEquals(count($data['ids']), count($results));

        foreach ($results as $k => $v) {
            $this->assertEquals($v, $data['result'][$k]);

            if (is_null($files[$k])) {
                $this->assertNull($data['ids'][$k]);

            } else {
                $filename = $data['ids'][$k];
                $path = $GLOBALS['app']->getContainer()['directories']['images'] . DIRECTORY_SEPARATOR . $filename;

                $this->assertEquals(pathinfo($files[$k], PATHINFO_EXTENSION), pathinfo($path, PATHINFO_EXTENSION));
                $this->assertTrue(file_exists($path));
                $this->assertEquals(file_get_contents($path), file_get_contents($files[$k]));
            }
        }
    }

}

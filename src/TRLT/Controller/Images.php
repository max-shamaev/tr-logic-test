<?php

namespace TRLT\Controller;

use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

class Images
{

    const STATE_OK               = 'ok';
    const STATE_DUPLICATE        = 'duplicate';
    const STATE_WRONG_IMAGE      = 'wrong_image';
    const STATE_WRONG_IMAGE_TYPE = 'wrong_image_type';
    const STATE_WRONG_IMAGE_SIZE = 'wrong_image_size';
    const STATE_CANNOT_SAVE      = 'cannot_save';

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function uploadImage(Request $request, Response $response)
    {
        switch ($this->detectContentType($request)) {
            case 'application/vnd.api+json':
            case 'application/json':
                $response = $this->uploadImagesFromJSON($request, $response);
                break;

            case 'application/x-www-form-urlencoded':
            case 'multipart/form-data':
                $response = $this->uploadImagesFromForm($request, $response);
                break;

            default:
                $response = $response->withStatus(415);
        }

        return $response;
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    protected function uploadImagesFromJSON(Request $request, Response $response)
    {
        $request = $request->withHeader('Content-Type', 'application/json');

        $body = $request->getParsedBody();

        if (is_array($body) && !empty($body)) {
            $states = [];
            $ids = [];
            foreach ($body as $image) {
                if (!empty($image) && is_string($image)) {
                    list($state, $id) = $this->uploadImageFromString($image);
                    $states[] = $state;
                    $ids[] = $id;
                }
            }

            $response = $this->prepareResponseWithStates($response, $states)
                ->withJson(['result' => $states, 'ids' => $ids]);

        } else {
            $response = $response->withStatus(400);
        }

        return $response;
    }

    /**
     * @param string $image
     *
     * @return array
     */
    protected function uploadImageFromString($image)
    {
        if (preg_match('/^(?:https?|ftp):\/\//Ss', $image) && filter_var($image, FILTER_VALIDATE_URL)) {

            // String is URL
            list($state, $result) = $this->uploadImageFromURL($image);

        } elseif (preg_match('/^data:(?:[\w\/]*;)?base64,(.+)$/Ss', $image, $match)) {

            // String is base64 encoded image
            list($state, $result) = $this->uploadImageFromBase64($match[1]);

        } else {
            $state = static::STATE_WRONG_IMAGE;
            $result = null;
        }

        return [$state, $result];
    }

    /**
     * @param string $url
     *
     * @return array
     */
    protected function uploadImageFromURL($url)
    {
        $state = static::STATE_OK;
        $result = null;

        $path = null;
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('GET', $url);
            if ($res->getStatusCode() == 200) {
                $path = tempnam(sys_get_temp_dir(), 'images');
                if (!file_put_contents($path, $res->getBody()->getContents())) {
                    $state = static::STATE_CANNOT_SAVE;
                }

            } else {
                $state = static::STATE_WRONG_IMAGE;
            }

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $state = static::STATE_WRONG_IMAGE;
        }

        if ($state == static::STATE_OK) {
            list($state, $result) = $this->processFromTemporaryPath($path);
        }

        return [$state, $result];
    }

    /**
     * @param string $image
     *
     * @return array
     */
    protected function uploadImageFromBase64($image)
    {
        $image = base64_decode($image, true);
        if ($image) {
            list($state, $result) = $this->uploadImageFromRaw($image);

        } else {
            $result = null;
            $state = static::STATE_WRONG_IMAGE;
        }

        return [$state, $result];
    }

    /**
     * @param string $image
     *
     * @return array
     */
    protected function uploadImageFromRaw($image)
    {
        $state = static::STATE_OK;
        $result = null;

        $path = tempnam(sys_get_temp_dir(), 'images');
        if (!file_put_contents($path, $image)) {
            $state = static::STATE_CANNOT_SAVE;
        }
        unset($image);

        if ($state == static::STATE_OK) {
            list($state, $result) = $this->processFromTemporaryPath($path);
        }

        return [$state, $result];
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    protected function uploadImagesFromForm(Request $request, Response $response)
    {
        $states = [];
        $ids = [];

        /** @var \Slim\Http\UploadedFile $file */
        foreach ($request->getUploadedFiles() as $file) {
            $id = null;
            switch ($file->getError()) {
                case UPLOAD_ERR_OK:
                    list($state, $id) = $this->uploadImageFromUploadedFile($file);
                    break;

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                $state = static::STATE_WRONG_IMAGE_SIZE;
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $state = static::STATE_WRONG_IMAGE_TYPE;
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $state = static::STATE_WRONG_IMAGE;
                    break;

                default:
                    $state = static::STATE_CANNOT_SAVE;
            }

            $states[] = $state;
            $ids[] = $id;
        }

        return $this->prepareResponseWithStates($response, $states)
            ->withJson(['result' => $states, 'ids' => $ids]);
    }

    /**
     * @param \Slim\Http\UploadedFile $file
     *
     * @return array
     */
    protected function uploadImageFromUploadedFile(\Slim\Http\UploadedFile $file)
    {
        $state = static::STATE_OK;
        $result = null;

        $path = tempnam(sys_get_temp_dir(), 'images');
        try {
            $file->moveTo($path);

        } catch (\Exception $e) {
            $state = static::STATE_CANNOT_SAVE;
        }

        if ($state == static::STATE_OK) {
            list($state, $result) = $this->processFromTemporaryPath($path);
        }

        return [$state, $result];
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function processFromTemporaryPath($path)
    {
        $result = null;

        $target_path = null;
        $extension = $this->detectExtensionByPath($path);
        if ($extension) {
            list($target_path) = $this->buildTargetPath($extension);
            $state = static::STATE_OK;

        } else {
            $state = static::STATE_WRONG_IMAGE_TYPE;
        }

        if ($state == static::STATE_OK) {
            if (rename($path, $target_path)) {
                $result = basename($target_path);

            } else {
                $state = static::STATE_CANNOT_SAVE;
            }
        }

        if (file_exists($path)) {
            unlink($path);
        }

        return [$state, $result];
    }

    protected function buildTargetPath($extension)
    {
        $path = null;
        $fp = null;
        $dir = $GLOBALS['app']->getContainer()->directories['images'] . DIRECTORY_SEPARATOR;
        do {
            $path = $dir . md5(uniqid('', true)) . '.' . $extension;
            if (file_exists($path)) {
                $path = null;

            } else {
                $fp = fopen($path, 'x');
                if ($fp && flock($fp, LOCK_EX)) {
                    ftruncate($fp, 0);

                } else {
                    var_dump($path);
                    $path = null;
                }
            }

        } while (!$path);

        return [$path, $fp];
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    protected function detectExtensionByPath($path)
    {
        static $extensions = [
            IMAGETYPE_BMP      => 'bmp',
            IMAGETYPE_GIF      => 'gif',
            IMAGETYPE_ICO      => 'ico',
            IMAGETYPE_IFF      => 'iff',
            IMAGETYPE_JB2      => 'jb2',
            IMAGETYPE_JP2      => 'jp2',
            IMAGETYPE_JPC      => 'jpc',
            IMAGETYPE_JPEG     => 'jpg',
            IMAGETYPE_JPEG2000 => 'jpg',
            IMAGETYPE_JPX      => 'jpx',
            IMAGETYPE_PNG      => 'png',
            IMAGETYPE_PSD      => 'psd',
            IMAGETYPE_SWC      => 'swc',
            IMAGETYPE_SWF      => 'swf',
            IMAGETYPE_TIFF_II  => 'tiff',
            IMAGETYPE_TIFF_MM  => 'tiff',
            IMAGETYPE_WBMP     => 'bmp',
            IMAGETYPE_XBM      => 'xbm',
        ];

        $info = getimagesize($path);

        return $info && !empty($info[2]) && isset($extensions[$info[2]])
            ? $extensions[$info[2]]
            : null;
    }

    /**
     * @param Response $response
     * @param string[] $states
     *
     * @return Response
     */
    protected function prepareResponseWithStates(Response $response, array $states)
    {
        $has_one_ok = false;

        foreach ($states as $state) {
            if ($state == static::STATE_OK) {
                $has_one_ok = true;
                break;
            }
        }

        if ($has_one_ok) {
            $response = $response->withStatus(200);

        } else {
            $response = $response->withStatus(400);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function detectContentType(Request $request)
    {
        $types = $request->getHeader('Content-Type');
        $type = $types ? $types[0] : null;

        return $type;
    }

}

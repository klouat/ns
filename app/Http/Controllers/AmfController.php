<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SabreAMF_Message;
use SabreAMF_InputStream;
use SabreAMF_OutputStream;
use Illuminate\Support\Facades\Log;

class AmfController extends Controller
{
    public function handle(Request $request)
    {
        $content = $request->getContent();

        if (empty($content)) {
            return response('AMF Gateway Ready', 200);
        }

        try {
            $stream = new SabreAMF_InputStream($content);
            $amfRequest = new SabreAMF_Message();
            $amfRequest->deserialize($stream);
        } catch (\Exception $e) {
            return response('Invalid AMF Data', 500);
        }

        $amfResponse = new SabreAMF_Message();
        $amfResponse->setEncoding($amfRequest->getEncoding());

        $bodies = $amfRequest->getBodies();
        foreach ($bodies as $requestBody) {
            $responseBody = $this->handleBody($requestBody);
            $amfResponse->addBody($responseBody);
        }

        $outputStream = new SabreAMF_OutputStream();
        $amfResponse->serialize($outputStream);
        $output = $outputStream->getRawData();

        return response($output)
            ->header('Content-Type', 'application/x-amf');
    }

    private function handleBody($requestBody)
    {
        $target = $requestBody['target'];
        $responseTarget = $requestBody['response'];
        $data = $requestBody['data'];

        try {
            // Write directly to stderr to ensure visibility in console
            file_put_contents('php://stderr', "\n>>> AMF Service Target: {$target}\n", FILE_APPEND);
            Log::info("AMF Service: {$target}", ['data' => $data]);
            
            $result = $this->dispatchService($target, $data);

            return [
                'target'   => $responseTarget . '/onResult',
                'response' => null,
                'data'     => $result
            ];
        } catch (\Throwable $e) {
            $errorMsg = "AMF Error {$target}: " . $e->getMessage();
            file_put_contents('php://stderr', "!!! {$errorMsg}\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            Log::error($errorMsg);
            
            return [
                'target'   => $responseTarget . '/onStatus',
                'response' => null,
                'data'     => [
                    'description' => $e->getMessage(),
                    'details'     => $e->getTraceAsString(),
                    'level'       => 'error',
                    'code'        => $e->getCode()
                ]
            ];
        }
    }

    private function dispatchService($target, $data)
    {
        $parts = explode('.', $target);
        $baseName = ucfirst($parts[0]);

        if (str_ends_with($baseName, 'Service')) {
            $serviceName = $baseName;
        } else {
            $serviceName = $baseName . 'Service';
        }

        if (isset($parts[1])) {
            $methodName = $parts[1];
        } else {
            $methodName = 'index';
        }

        $fullClassName = "App\\Services\\Amf\\" . $serviceName;
        file_put_contents('php://stderr', ">>> Despatching to: {$fullClassName}@{$methodName}\n", FILE_APPEND);
        Log::info("Despatching to: {$fullClassName}@{$methodName}");
        if (class_exists($fullClassName) == false) {
            $fullClassName = "App\\Services\\" . $serviceName;

            if (class_exists($fullClassName) == false) {
                throw new \Exception("Service not found.");
            }
        }

        $serviceInstance = app($fullClassName);

        if (method_exists($serviceInstance, $methodName) == false) {
            throw new \Exception("Method not found.");
        }

        if (is_array($data)) {
            $params = $data;
        } else {
            $params = [$data];
        }

        return call_user_func_array([$serviceInstance, $methodName], $params);
    }
}
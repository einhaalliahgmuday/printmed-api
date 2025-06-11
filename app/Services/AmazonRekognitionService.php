<?php

namespace App\Services;

// require 'vendor/autoload.php';

use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;
use Exception;

use function PHPUnit\Framework\isEmpty;

class AmazonRekognitionService
{
    private $rekognition;

    public function __construct() {
        $this->rekognition = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-1'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY')
            ]
        ]);
    }

    public function detectFaces($imageBytes) {
        try {
            $result = $this->rekognition->detectFaces([
                'Image' => [
                    'Bytes' => $imageBytes
                ],
                'Attributes' => ['ALL'] // FACE_OCCLUDED, EYE-DIRECTION, DEFAULT
            ]);

            return $result['FaceDetails'] ?? [];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getAwsErrorMessage()
            ];
        }
    }

    public function compareFaces($sourceImageBytes, $targetImageBytes) {
        try {
            $result = $this->rekognition->compareFaces([
                'SourceImage' => ['Bytes' => $sourceImageBytes],
                'TargetImage' => ['Bytes' => $targetImageBytes],
                'SimilarityThreshold' => 99.9
            ]);

            return [
                'success' => true,
                'result' => $result['FaceMatches'] ?? []
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'error_code' => $e->getAwsErrorCode(),
                'message' => $e->getAwsErrorMessage()
            ];
        }
    }

    public function indexFaces($imageBytes, string $externalImageId, string $collectionId) {
        try {
            $result = $this->rekognition->indexFaces([
                'Image' => [
                    'Bytes' => $imageBytes
                ],
                'CollectionId' => $collectionId,
                'ExternalImageId' => $externalImageId,
                'MaxFaces' => 1,
                'DetectionAttributes' => [],
                'QualityFilter' => 'HIGH'
            ]);

            return [
                'success' => true,
                'result' => $result['FaceRecords'][0] ?? []
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function searchFacesByImage($imageBytes, string $collectionId) {
        try {
            $result = $this->rekognition->searchFacesByImage([
                'CollectionId' => $collectionId,
                'FaceMatchThreshold' => 99.9,
                'Image' => [
                    'Bytes' => $imageBytes
                ],
                'MaxFaces' => 1,
                'QualityFilter' => 'HIGH'
            ]);

            return [
                'success' => true,
                'result' => $result['FaceMatches'][0] ?? []
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getAwsErrorCode()
            ];
        }
    }

    public function deleteFaceFromCollection(string $faceId, string $collectionId) {
        try {
            $result = $this->rekognition->deleteFaces([
                'CollectionId' => $collectionId,
                'FaceIds' => [
                    $faceId
                ]
            ]);

            return [
                'success' => count($result['DeletedFaces']) < 0 ? false : true
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function listFacesFromCollection(string $collectionId) {
        try {
            $result = $this->rekognition->listFaces([
                'CollectionId' => $collectionId
            ]);

            return [
                'success' => true,
                'result' => $result['Faces'] ?? []
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

     public function createCollection(string $collectionId) {
        try {
            $result = $this->rekognition->createCollection([
                'CollectionId' => $collectionId
            ]);

            return [
                'success' => true,
                'result' => $result
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getCollectionDescription(string $collectionId) {
        try {
            return $this->rekognition->describeCollection([
                'CollectionId' => $collectionId
            ]);
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deleteCollection(string $collectionId) {
        try {
            $result = $this->rekognition->deleteCollection([
                'CollectionId' => $collectionId
            ]);

            return [
                'success' => $result['StatusCode'] == 200 ? true : false
            ];
        } catch(AwsException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
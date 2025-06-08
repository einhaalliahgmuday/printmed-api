<?php

namespace App\Http\Controllers;

use App\Services\AmazonRekognitionService;
use Illuminate\Http\Request;

class RekognitionController extends Controller
{
    private $rekognitionService;

    public function __construct() {
        $this->rekognitionService = new AmazonRekognitionService();
    }

    public function createPatientsCollection() {
        $result = $this->rekognitionService->createCollection('patients');

        if($result['success'] == true) {
            return response()->json($result['result']);
        } else {
            return response()->json($result['message']);
        }
    }

    public function listFacesFromPatientsCollection() {
        $result = $this->rekognitionService->listFacesFromCollection('patients');

        if($result['success'] == true) {
            return response()->json($result['result']);
        } else {
            return response()->json($result['message']);
        }
    }
}

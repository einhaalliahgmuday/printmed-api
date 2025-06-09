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

    public function deletePatientsCollection() {
        $result = $this->rekognitionService->deleteCollection('patients');

        if($result['success'] == true) {
            return response()->json("success");
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

    public function searchFacesByImage(Request $request) {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,png|max:2048|dimensions:min_width=640,min_height=480'
        ]);

        $imageBytes = file_get_contents($request->file('photo')->getRealPath());
        $result = $this->rekognitionService->searchFacesByImage($imageBytes, 'patients');

        if($result['success'] == true) {
            return response()->json($result['result']['Face']['FaceId']);
        } else {
            return response()->json($result['message']);
        }
    }
}

<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\Rekognition\RekognitionClient;
use Aws\Exception\AwsException;

class Rekognition {
    private $client;
    
    public function __construct() {
        try {
            $this->client = new RekognitionClient([
                'version' => 'latest',
                'region'  => AWS_REGION,
                'credentials' => [
                    'key'    => AWS_KEY,
                    'secret' => AWS_SECRET,
                ],
            ]);
        } catch (Exception $e) {
            error_log("Rekognition client error: " . $e->getMessage());
            throw new Exception("Failed to initialize AWS Rekognition client.");
        }
    }
    
    /**
     * Create a collection if it doesn't exist
     * 
     * @return bool Success or failure
     */
    public function createCollectionIfNotExists() {
        try {
            // First check if the collection exists
            $collections = $this->client->listCollections();
            
            if (in_array(AWS_COLLECTION_ID, $collections['CollectionIds'])) {
                return true; // Collection already exists
            }
            
            // Create the collection
            $result = $this->client->createCollection([
                'CollectionId' => AWS_COLLECTION_ID,
            ]);
            
            return $result['StatusCode'] == 200;
        } catch (AwsException $e) {
            error_log("AWS Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Index a face in the AWS Rekognition collection
     * 
     * @param string $imagePath Path to the image file
     * @param string $externalId External ID to associate with the face (student ID)
     * @return array|bool Face ID if successful, false if failed
     */
    public function indexFace($imagePath, $externalId) {
        try {
            // Ensure the collection exists
            $this->createCollectionIfNotExists();
            
            // Read the image file
            $image = file_get_contents($imagePath);
            
            // First detect faces to get the count
            $detectResult = $this->client->detectFaces([
                'Image' => [
                    'Bytes' => $image,
                ],
                'Attributes' => ['ALL']
            ]);
            
            if (empty($detectResult['FaceDetails'])) {
                error_log("No face detected in the image.");
                return false;
            }
            
            // Index the faces
            $result = $this->client->indexFaces([
                'CollectionId' => AWS_COLLECTION_ID,
                'ExternalImageId' => (string)$externalId,
                'Image' => [
                    'Bytes' => $image,
                ],
                'MaxFaces' => count($detectResult['FaceDetails']),
                'QualityFilter' => 'AUTO',
                'DetectionAttributes' => ['ALL'],
            ]);
            
            // Check if faces were detected and indexed
            if (count($result['FaceRecords']) > 0) {
                $faceIds = [];
                foreach ($result['FaceRecords'] as $record) {
                    $faceIds[] = [
                        'faceId' => $record['Face']['FaceId'],
                        'boundingBox' => $record['Face']['BoundingBox'],
                        'confidence' => $record['Face']['Confidence']
                    ];
                }
                return $faceIds;
            } else {
                error_log("No face detected in the image.");
                return false;
            }
        } catch (AwsException $e) {
            error_log("AWS Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Search for faces in the collection
     * 
     * @param string $imagePath Path to the image file
     * @param float $threshold Similarity threshold (0-100)
     * @return array|bool Match results if successful, false if failed
     */
    public function searchFace($imagePath, $threshold = 70) {
        try {
            // Read the image file
            $image = file_get_contents($imagePath);
            
            // First detect all faces in the image
            $detectResult = $this->client->detectFaces([
                'Image' => [
                    'Bytes' => $image,
                ],
                'Attributes' => ['ALL']
            ]);
            
            if (!$detectResult || empty($detectResult['FaceDetails'])) {
                return false;
            }
            
            $allMatches = [];
            $numFaces = count($detectResult['FaceDetails']);
            
            // Try the combined approach first for all faces
            $result = $this->client->searchFacesByImage([
                'CollectionId' => AWS_COLLECTION_ID,
                'Image' => [
                    'Bytes' => $image,
                ],
                'MaxFaces' => $numFaces, // Try to match all faces
                'FaceMatchThreshold' => $threshold,
            ]);
            
            if (!empty($result['FaceMatches'])) {
                $allMatches = $result['FaceMatches'];
            }
            
            // If we detected multiple faces but didn't get enough matches and GD library is available, try individual face approach
            if ($numFaces > 1 && count($allMatches) < $numFaces && function_exists('imagecreatefromstring')) {
                // We need to create a separate image for each face
                $imgResource = imagecreatefromstring($image);
                if ($imgResource) {
                    $imgWidth = imagesx($imgResource);
                    $imgHeight = imagesy($imgResource);
                    
                    foreach ($detectResult['FaceDetails'] as $index => $faceDetail) {
                        $boundingBox = $faceDetail['BoundingBox'];
                        
                        // Calculate actual pixel values from bounding box ratios
                        $left = max(0, floor($boundingBox['Left'] * $imgWidth));
                        $top = max(0, floor($boundingBox['Top'] * $imgHeight));
                        $width = min($imgWidth - $left, ceil($boundingBox['Width'] * $imgWidth));
                        $height = min($imgHeight - $top, ceil($boundingBox['Height'] * $imgHeight));
                        
                        // Add padding around the face (20% of face width/height)
                        $padding_x = ceil($width * 0.2);
                        $padding_y = ceil($height * 0.2);
                        
                        $crop_left = max(0, $left - $padding_x);
                        $crop_top = max(0, $top - $padding_y);
                        $crop_width = min($imgWidth - $crop_left, $width + (2 * $padding_x));
                        $crop_height = min($imgHeight - $crop_top, $height + (2 * $padding_y));
                        
                        // Create a new image for this face
                        $faceCrop = imagecreatetruecolor($crop_width, $crop_height);
                        imagecopy($faceCrop, $imgResource, 0, 0, $crop_left, $crop_top, $crop_width, $crop_height);
                        
                        // Save the face image temporarily
                        $tempFile = tempnam(sys_get_temp_dir(), 'face_') . '.jpg';
                        imagejpeg($faceCrop, $tempFile, 90);
                        imagedestroy($faceCrop);
                        
                        // Search for this individual face
                        $faceImage = file_get_contents($tempFile);
                        $faceResult = $this->client->searchFacesByImage([
                            'CollectionId' => AWS_COLLECTION_ID,
                            'Image' => [
                                'Bytes' => $faceImage,
                            ],
                            'MaxFaces' => 1,
                            'FaceMatchThreshold' => $threshold,
                        ]);
                        
                        // Add any matches to our results
                        if (!empty($faceResult['FaceMatches'])) {
                            $allMatches = array_merge($allMatches, $faceResult['FaceMatches']);
                        }
                        
                        // Clean up
                        unlink($tempFile);
                    }
                    
                    imagedestroy($imgResource);
                }
            } 
            // Alternative approach without GD library - try using AWS Rekognition's built-in face detection
            else if ($numFaces > 1 && count($allMatches) < $numFaces) {
                // For each face detected, try to use AWS's detected face locations
                foreach ($detectResult['FaceDetails'] as $faceDetail) {
                    // Use the face's bounding box information directly with AWS
                    try {
                        // Try each face individually with the whole image
                        $faceResult = $this->client->searchFacesByImage([
                            'CollectionId' => AWS_COLLECTION_ID,
                            'Image' => [
                                'Bytes' => $image,
                            ],
                            'MaxFaces' => 1,
                            'FaceMatchThreshold' => $threshold,
                        ]);
                        
                        // Add any matches to our results
                        if (!empty($faceResult['FaceMatches'])) {
                            $allMatches = array_merge($allMatches, $faceResult['FaceMatches']);
                        }
                    } catch (Exception $e) {
                        error_log("Error processing individual face: " . $e->getMessage());
                        // Continue with next face
                        continue;
                    }
                }
            }
            
            // Remove duplicate matches (same person detected multiple times)
            $uniqueMatches = [];
            $seenFaceIds = [];
            
            foreach ($allMatches as $match) {
                $faceId = $match['Face']['FaceId'];
                if (!in_array($faceId, $seenFaceIds)) {
                    $seenFaceIds[] = $faceId;
                    $uniqueMatches[] = $match;
                }
            }
            
            return $uniqueMatches;
            
        } catch (AwsException $e) {
            error_log("AWS Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a face from the collection
     * 
     * @param string $faceId Face ID to delete
     * @return bool Success or failure
     */
    public function deleteFace($faceId) {
        try {
            $result = $this->client->deleteFaces([
                'CollectionId' => AWS_COLLECTION_ID,
                'FaceIds' => [$faceId],
            ]);
            
            return count($result['DeletedFaces']) > 0;
        } catch (AwsException $e) {
            error_log("AWS Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List all faces in the collection
     * 
     * @return array|bool List of faces if successful, false if failed
     */
    public function listFaces() {
        try {
            $result = $this->client->listFaces([
                'CollectionId' => AWS_COLLECTION_ID,
                'MaxResults' => 1000,
            ]);
            
            return $result['Faces'];
        } catch (AwsException $e) {
            error_log("AWS Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Detect faces in an image without searching the collection
     * 
     * @param string $imagePath Path to the image file
     * @return array|bool Face details if successful, false if failed
     */
    public function detectFaces($imagePath) {
        try {
            // Read the image file
            $image = file_get_contents($imagePath);
            
            // Detect faces
            return $this->client->detectFaces([
                'Image' => [
                    'Bytes' => $image,
                ],
                'Attributes' => ['ALL']
            ]);
        } catch (AwsException $e) {
            error_log("AWS Error: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            return false;
        }
    }
}
?> 
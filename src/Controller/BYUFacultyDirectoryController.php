<?php

/**
 * @file
 * Contains \Drupal\byu_faculty_directory\Controller\BYUFacultyDirectoryController.
 */

namespace Drupal\byu_faculty_directory\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for byu_faculty_directory routes.
 */
class BYUFacultyDirectoryController extends ControllerBase {

    const ParentMode = 0;
    const ChildMode = 1;

    /**
     * Callback for `byu-faculty-directory/all-faculty` API method.
     * Method: GET
     * Params: applicationKey - configured in Drupal module admin configuration
     * Request Body: n/a
     * Return: JSON with array 'data' consisting of Drupal nodes (BYU Faculty Member) of all faculty.
     */
    public function all_faculty( Request $request ) {
        //Don't do anything in child mode
        $current_mode = \Drupal::config('byu_faculty_directory.config')->get('module_mode');
        if ($current_mode != BYUFacultyDirectoryController::ParentMode){
            $response['error'] = 'Resource not found.';
            return new JsonResponse($response, 404);
        }

        if (!BYUFacultyDirectoryController::verify_api_key($request)) {
            $response['error'] = 'Invalid application key.';
            return new JsonResponse($response, 401);
        }

        //Get all faculty members
        $faculty_members = BYUFacultyDirectoryController::load_faculty_members('type', 'byu_faculty_member');

        $response['data'] = $faculty_members;
        return new JsonResponse($response);
    }

    /**
     * Callback for `byu-faculty-directory/filtered-faculty` API method.
     * Method: POST
     * Params: applicationKey - configured in Drupal module admin configuration
     * Request Body: JSON with array 'departments' consisting of names of departments to filter by
     * Return: JSON with array 'data' consisting of Drupal nodes (BYU Faculty Member) of filtered faculty
     */
    public function filtered_faculty(Request $request) {
        //Don't do anything in child mode
        $current_mode = \Drupal::config('byu_faculty_directory.config')->get('module_mode');
        if ($current_mode != BYUFacultyDirectoryController::ParentMode){
            $response['error'] = 'Resource not found.';
            return new JsonResponse($response, 404);
        }

        if (!BYUFacultyDirectoryController::verify_api_key($request)) {
            $response['error'] = 'Invalid application key.';
            return new JsonResponse($response, 401);
        }

        //Check for 'application/json' content type
        if (0 !== strpos($request->headers->get('Content-Type'), 'application/json')) {
            $response['error'] = 'Invalid content type - must be application/json.';
            return new JsonResponse($response, 400);
        }

        //Check for valid input (array of department names)

        $data = json_decode(json_decode($request->getContent(), true), true);
        $departments = $data['departments'];
        if (!$data['departments']) {
            $response['error'] = 'Invalid request body.';
            return new JsonResponse($response, 400);
        }

        //Get faculty members that match the specified department names
        $faculty_members = array();
        foreach($departments as $department) {
            $query_result = BYUFacultyDirectoryController::load_faculty_members('field_department', $department);
            $faculty_members = array_merge($faculty_members, $query_result);
        }

        $response['data'] = $faculty_members;
        return new JsonResponse($response);
    }

    /**
     * Checks the application key given in a Request with the application key stored in the Drupal configuration
     */
    public function verify_api_key(Request $request) {
        $dept_api_key = \Drupal::config('byu_faculty_directory.config')->get('dept_api_key');
        return (strcmp($request->get('applicationKey'), $dept_api_key) != 0) ? false : true;
    }

    /**
     * Loads all faculty members matching the provided field and field value, and returns them in an array
     */
    public function load_faculty_members(string $field, string $field_value){
        $faculty_members = array();
        $nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties([$field => $field_value]);
        foreach($nodes as $node){
            $faculty_members[] = $node->toArray();
        }
        return $faculty_members;
    }
}
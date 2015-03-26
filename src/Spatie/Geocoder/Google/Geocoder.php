<?php

namespace Spatie\Geocoder\Google;

use GuzzleHttp\Client;
use Spatie\Geocoder\GeocoderInterface;

class Geocoder implements GeocoderInterface
{
    /**
     * @var client
     */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the coordinates for a query.
     *
     * @param string $query
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCoordinatesForQuery($query)
    {
        if ($query == '') {
            return false;
        }

        $response = $this->client->get('https://maps.googleapis.com/maps/api/geocode/json?address='.$query.'&sensor=false');

        $this->guardAgainstExternalErrors($response);

        return $this->parseResponse($response);
    }

    /**
     * Return the response to a usable array
     *
     * @param $response
     * @return array
     */
    protected function parseResponse($response)
    {
        $fullResponse = $response->json();

        if (count($fullResponse['results'])) {
            return [
                'lat'      => $fullResponse['results'][0]['geometry']['location']['lat'],
                'lng'      => $fullResponse['results'][0]['geometry']['location']['lng'],
                'accuracy' => $fullResponse['results'][0]['geometry']['location_type'],
            ];
        }

        return $this->populateResultWithErrors($this->setErrorMessages($fullResponse), $response);
    }

    /**
     * Populate the result with set errorMessages
     *
     * @param $errors
     * @param $response
     * @return array
     */
    protected function populateResultWithErrors($errors, $response)
    {
        $geocoderResult = [
            'lat'        => 0,
            'lng'        => 0,
            'accuracy'   => $errors['status'].' WITH STATUSCODE '.$response->getStatusCode(),
        ];

        if (isset($errors['message'])) {
            $geocoderResult['errorMessage'] = $errors['message'];
        }

        return $geocoderResult;
    }

    /**
     * Set the errors from the JSON response to an array
     *
     * @param $fullResponse
     * @return array
     */
    protected function setErrorMessages($fullResponse)
    {
        $errors = [];

        if (array_key_exists('status', $fullResponse)) {
            $errors['status'] = $fullResponse['status'];
        }

        if (array_key_exists('error_message', $fullResponse)) {
            $errors['message'] = $fullResponse['error_message'];
        }

        return $errors;
    }

    /**
     * Throw exception if the client can't connect to the google api
     *
     * @param $response
     * @throws \Exception
     */
    private function guardAgainstExternalErrors($response)
    {
        // https://developers.google.com/analytics/devguides/reporting/core/v3/coreErrors ?

        // != 200 => Every error | == 503 back-end error
        if ($response->getStatusCode() == 503) {
            throw new \Exception('Could not connect to googleapis.com/maps/api. STATUSCODE = '.$response->getStatusCode());
        }
    }
}

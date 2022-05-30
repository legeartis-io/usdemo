<?php

namespace UnifiedSearch\controllers;

use Exception;
use Legeartis\UnifiedSearch\responseObjects\SearchResult;
use Legeartis\UnifiedSearch\responseObjects\Vehicle;

/**
 * @property SearchResult result
 * @property Vehicle[] foundedVehicles
 * @property array tasks
 * @property bool query
 * @property float|int totalPages
 * @property Vehicle indexStart
 * @property bool foundTags
 * @property bool foundVendorCodes
 * @property array options
 * @property array tags
 * @property array languages
 * @property Vehicle vehicleInfo
 * @property bool searchAvailable
 * @property int page
 * @property  string vin
 * @property string unitDataUrlTemplate
 * @property string indexationError
 */
class searchController extends Controller
{
    public function show()
    {
        $query = $this->input->getString('query', '');
        $us = $this->getUS();
        $size = $this->input->getInt('size', 20);
        $page = $this->input->getInt('page', 0);
        $skip = $page * $size;
        $options = $this->input->get('options');
        $tags = $this->input->get('tags');
        $ssd = $this->input->getString('ssd');
        $vehicleId = $this->input->getString('vid');
        $catalog = $this->input->getString('c');
        $format = $this->input->getString('format', false);
        if ($options) {
            foreach ($options as $key => $option) {
                $options[$key] = $option ? 'true' : 'false';
            }
        }

        $languages = [];

        $this->query = '';

        if (strlen($query) || $tags && $options && $options['tagToggler']) {
            $this->searchAvailable = true;
            $this->result = $us->search($query, $tags && $options && $options['tagToggler'] ? $tags : [], [], $skip, $size);

            $this->foundTags = false;
            $this->foundVendorCodes = false;
            if (!empty($this->result->getDetails())) {
                foreach ($this->result->getDetails() as $detail) {
                    foreach ($detail->getOffers() as $language => $offers) {
                        $languages[$language] = $language;
                    }
                    if (is_array($detail->getTags()) && count($detail->getTags())) {
                        $this->foundTags = true;
                    }
                    if (is_array($detail->getVendorCodes()) && count($detail->getVendorCodes())) {
                        $this->foundVendorCodes = true;
                    }
                }

                $this->languages = $languages;
            }

            $this->foundedVehicles = [];
            if (!empty($this->result->getDetectedVehicleContext())) {
                $this->foundedVehicles = $us->identify($this->result->getDetectedVehicleContext()->getVin())->getVehicles();
                $this->vehicleInfo = $this->foundedVehicles[0];

                if (!$this->result->getDetectedVehicleContext()->isIndexed() && $this->foundedVehicles) {
                    $vhCount = count($this->foundedVehicles);
                    if ($vhCount > 1) {
                        if (!$ssd) {
                            $this->redirect('search', 'select', ['vin' => $this->result->getDetectedVehicleContext()->getVin(), 'query' => $query, 'format' => $format]);
                        } else {
                            $this->vehicleInfo = $this->indexStart = $us->indexVin($catalog, $ssd, $vehicleId);
                        }
                    } else {
                        $catalogReference = $this->vehicleInfo ? $this->vehicleInfo->getCatalogReference() : null;
                        $this->vehicleInfo = $this->indexStart = $us->indexVin($catalogReference->getCatalog(), $catalogReference->getSsd(), $catalogReference->getVehicleId());
                    }
                } else {
                    if ($ssd) {
                        $this->vehicleInfo = $this->indexStart = $us->indexVin($catalog, $ssd, $vehicleId);
                    } else {
                        $this->unitDataUrlTemplate = $this->createUrl('unit', 'show', '', [
                            'vin' => '$vin$',
                            'detail_id' => '$detail_id$',
                            'oem' => '$oem$',
                        ]);
                    }
                }

            }

            $this->query = $query;
            $this->totalPages = !empty($this->result->getTotal()) ? ceil($this->result->getTotal() / $size) : 0;
        }

        $this->options = $options;
        $this->tags = $tags;
        $this->page = $page;
        $this->pathway->addItem('Unified Search', $this->getBaseUrl());
        $this->pathway->addItem($this->getLanguage()->t('SEARCH_DEMO'), $this->createUrl('search', 'show'));

        $this->render('search', 'view.twig', true, $format === 'json');
    }

    public function getVinProgress()
    {
        $us = $this->getUS();
        $indexedAutoId = $this->input->getString('indexedAutoId');
        if (!$indexedAutoId) {
            $this->responseJson((object)['indexationProgress' => 100]);
        }
        try {
            $vehicle = $us->getVehicle($indexedAutoId);
            $this->responseJson((object)['indexationProgress' => $vehicle->getIndexationProgress()->getIndexationPercent() ?: 100]);
        } catch (Exception $ex) {
            $this->responseJson((object)['indexationProgress' => 100]);
        }
    }

    public function select()
    {
        $vin = $this->input->getString('vin');
        $us = $this->getUS();
        $format = $this->input->getString('format', false);

        $this->pathway->addItem('Unified Search', $this->getBaseUrl());
        $this->pathway->addItem($this->getLanguage()->t('SEARCH_DEMO'), $this->createUrl('search', 'show'));
        $this->pathway->addItem('Select modification - ' . $vin, '');

        $this->foundedVehicles = $us->identify($vin, 'ru_RU')->getVehicles();
        $this->query = $this->input->getString('query');
        $this->vin = $vin;
        $this->render('search', 'select.twig', true, $format === 'json');
    }

    public function getAutocomplete()
    {
        $us = $this->getUS();
        $query = $this->input->getString('query');

        if ($query && strlen($query) > 3) {
            $res = $us->completeQuery($query);
            $this->responseJson($res);
        }

        $this->responseJson(['queryCompletions' => []]);
    }
}
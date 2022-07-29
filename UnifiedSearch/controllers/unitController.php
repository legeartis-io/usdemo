<?php

namespace UnifiedSearch\controllers;

use Exception;
use GuayaquilLib\objects\oem\AttributeObject;
use GuayaquilLib\objects\oem\ImageMapObject;
use GuayaquilLib\objects\oem\PartListObject;
use GuayaquilLib\objects\oem\QuickDetailListObject;
use GuayaquilLib\objects\oem\UnitObject;
use GuayaquilLib\objects\oem\VehicleObject;
use GuayaquilLib\Oem;
use GuayaquilLib\ServiceOem;
use Legeartis\UnifiedSearch\responseObjects\Vehicle;

/**
 * @property array details
 * @property array units
 * @property string oem
 * @property Vehicle vehicleInfo
 * @property string vin
 * @property UnitObject unit
 * @property ImageMapObject imagemap
 * @property array detailImageCodes
 * @property array oemServiceData
 * @property |null highlightCode
 */
class unitController extends Controller
{
    public function show()
    {
        $vin = $this->input->getString('vin');
        $detailId = $this->input->getInt('detail_id');
        $oem = $this->input->getString('oem');
        $format = $this->input->getString('format');
        $indexedAutoId = $this->input->getString('indexedAutoId');

        $us = $this->getUS();

        $vehicles = $us->identify($vin)->getVehicles();
        /** @var Vehicle $vehicle */
        $vehicle = reset($vehicles);

        $searchResult = $us->originalApplicableDetails($indexedAutoId, $detailId);
        if (empty($searchResult->getOriginalDetails())) {
            return null;
        }

        $oemServiceData = [];

        foreach ($searchResult->getOriginalDetails() as $originalDetail) {
            $originalOem = $originalDetail->getOem();

            if (array_key_exists('locale', $this->config['OEMService'])) {
                $locale = $this->config['OEMService']['locale'];
            } else {
                $locale = 'ru_RU';
            }

            $service = new ServiceOem($this->config['OEMService']['login'], $this->config['OEMService']['key']);
            try {
                $oemServiceData[] = $service->findPartInVehicle($vehicle->getCatalogReference()->getCatalog(), $vehicle->getCatalogReference()->getSsd(), $originalOem, $locale);
            } catch (Exception $ex) {
                $this->renderError('500', $ex->getMessage(), $format);
            }
        }

        $this->details = $searchResult->getOriginalDetails();
        $this->oemServiceData = $oemServiceData;
        $this->oem = $oem;
        $this->vehicleInfo = $vehicle;
        $this->vin = $vin;
        $this->indexedAutoId = $indexedAutoId;

        $this->checkAmountNodes($indexedAutoId, $this->oemServiceData[0], $vin, $vehicle, $oem, $format);

        $this->pathway->addItem('Unified Search', $this->getBaseUrl());
        $this->pathway->addItem($this->getLanguage()->t('SEARCH_DEMO'), $this->createUrl('search', 'show'));
        $this->pathway->addItem($oem, '');

        $this->render('unit', 'view.twig', true, $format);
    }

    /**
     * @param $indexedAutoId
     * @param QuickDetailListObject $webSerbiceData
     * @param $vin
     * @param $vehicle
     * @param $oem
     * @param null $format
     */
    protected function checkAmountNodes($indexedAutoId, QuickDetailListObject $webSerbiceData, $vin, Vehicle $vehicle, $oem, $format = null)
    {
        $nodes = [];

        foreach ($webSerbiceData->getCategories() as $category) {
            if (empty($category->getUnits())) {
                continue;
            }
            foreach ($category->getUnits() as $unit) {
                $nodes[] = $unit;
            }
        }

        if (count($nodes) == 1) {
            /** @var $unit UnitObject */
            $unit = reset($nodes);

            $this->redirect('unit', 'unit', [
                'vin' => $vin,
                'oem' => $oem,
                'unitid' => $unit->getUnitId(),
                'ssd' => $unit->getSsd(),
                'catalog' => $vehicle->getCatalogReference()->getCatalog(),
                'vehicleId' => $vehicle->getCatalogReference()->getVehicleId(),
                'format' => $format,
                'indexedAutoId' => $indexedAutoId
            ]);
        }
    }

    /**
     * @param $name
     * @param AttributeObject[] $attrs
     * @return mixed|string
     */
    private function getAttr($name, array $attrs)
    {
        foreach ($attrs as $attr) {
            if ($attr->getName() === $name) {
                return $attr->getValue();
            }
        }
        return '';
    }

    public function unit()
    {
        $indexedAutoId = $this->input->getString('indexedAutoId');
        $vin = $this->input->getString('vin');
        $oem = $this->input->getString('oem');
        $unitId = $this->input->getString('unitid');
        $ssd = $this->input->getString('ssd');
        $catalog = $this->input->getString('catalog');
        $vehicleId = $this->input->getString('vehicleId');
        $format = $this->input->getString('format');
        $locale = $this->getLocalization();

        $service = new ServiceOem($this->config['OEMService']['login'], $this->config['OEMService']['key']);
        /** @var PartListObject $detailsList */
        /** @var UnitObject $unit */
        /** @var ImageMapObject $imageMap */
        /** @var VehicleObject $vehicleInfo */
        try {
            list($unit, $detailsList, $imageMap, $catalogInfo, $vehicleInfo) = $service->queryButch([
                Oem::getUnitInfo($catalog, $ssd, $unitId, $locale),
                Oem::listPartsByUnit($catalog, $ssd, $unitId, $locale),
                Oem::listImageMapByUnit($catalog, $ssd, $unitId),
                Oem::getCatalogInfo($catalog, $locale),
                Oem::getVehicleInfo($catalog, $vehicleId, $ssd, $locale),
            ]);
        } catch (Exception $ex) {
            $this->renderError('500', $ex->getMessage(), $format);
        }

        $oems = [];
        $detailImageCodes = [];
        $this->highlightCode = null;

        if (!empty($detailsList->getParts())) {
            foreach ($detailsList->getParts() as $detail) {
                if (!empty($detail->getOem())) {
                    $oems[] = $detail->getOem();
                }
            }
        }

        $us = $this->getUS();
        $searchByOems = $us->detailsByCatalogOems($indexedAutoId, $oems, $vin, true, true);
        $details = [];
        foreach ($detailsList->getParts() as $original) {
            foreach ($searchByOems->getDetailsByOem() as $detail) {
                if ($detail->getOem() == $original->getOem() && count($detail->getDetails())) {
                    foreach ($detail->getDetails() as $item) {
                        $detailImageCodes[$item->getOem() . $item->getPrimaryBrand()] = $original->getCodeOnImage();
                        $item->amount = $this->getAttr('amount', $original->getAttributes());
                        $item->note = $this->getAttr('note', $original->getAttributes());
                        $item->code = $original->getCodeOnImage();
                        $details[$original->getCodeOnImage()][$item->getOem() . $item->getPrimaryBrand()] = $item;
                    }
                    $this->highlightCode = 'i' . $original->getCodeOnImage();
                }
            }
        }

        $this->pathway->addItem('Unified Search', $this->getBaseUrl());
        $this->pathway->addItem($this->getLanguage()->t('SEARCH_DEMO'), $this->createUrl('search', 'show'));
        $this->pathway->addItem($unit->getName(), '');

//        if (!empty($imageMap)) {
//            foreach ($imageMap->getMapObjects() as $mapItem) {
//                $detailOem = !empty($details['i' . $mapItem->getCode()]) ? reset($details['i' . $mapItem->getCode()])->oem : null;
//                $mapItem->oem = $detailOem;
//                if (!empty($details['i' . $mapItem->getCode()]) && is_array($details['i' . $mapItem->getCode()])) {
//
//                    foreach ($details['i' . $mapItem->getCode()] as $detail) {
//                        if ($detail->oem === $oem) {
//                            $highlightCode = $detail->code;
//                        }
//                    }
//                }
//            }
//        }

        $this->unit = $unit;
        $this->details = $details;
        $this->oem = $oem;
        $this->imagemap = $imageMap;
        $this->detailImageCodes = $detailImageCodes;
        $this->vehicleInfo = $vehicleInfo;

        $this->render('unit', 'unit.twig', true, $format);
    }
}